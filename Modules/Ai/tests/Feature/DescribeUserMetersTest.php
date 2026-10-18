<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Ai\Actions\DescribeUserMeters;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

mutates(DescribeUserMeters::class);

describe('DescribeUserMeters', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        $this->user = User::factory()->create();
        $this->address = Address::factory()->create(['city' => 'Львів', 'street' => 'Зелена', 'building_number' => '7', 'apartment_number' => null]);
        $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
        $this->type = UtilityType::factory()->create(['slug' => 'electricity', 'unit' => 'kWh']);
        $this->action = app(DescribeUserMeters::class);
    });

    it('lists active supported meters of the user with last reading', function (): void {
        $meter = Meter::factory()->create(['address_id' => $this->address->getKey(), 'utility_type_id' => $this->type->getKey(), 'initial_reading' => 5]);
        MeterReading::factory()->create(['meter_id' => $meter->getKey(), 'reading_value' => 42, 'reading_date' => '2026-06-01']);

        $result = $this->action->handle($this->user);

        expect($result)->toHaveCount(1)
            ->and($result[0])->toMatchArray([
                'meter_id' => $meter->getKey(),
                'utility_type' => 'electricity',
                'unit' => 'kWh',
                'address' => 'Львів, Зелена 7',
                'tariff_zones' => [],
                'last_reading' => ['value' => 42.0, 'date' => '2026-06-01'],
            ]);
    });

    it('excludes inactive, foreign and unsupported meters', function (): void {
        Meter::factory()->inactive()->create(['address_id' => $this->address->getKey(), 'utility_type_id' => $this->type->getKey()]);
        Meter::factory()->create(['utility_type_id' => $this->type->getKey()]);
        Meter::factory()->create(['address_id' => $this->address->getKey(), 'utility_type_id' => UtilityType::factory()->create(['slug' => 'internet'])->getKey()]);

        expect($this->action->handle($this->user))->toBe([]);
    });

    it('describes tariff zones with per-zone last readings', function (): void {
        $provider = ServiceProvider::factory()->create(['utility_type_id' => $this->type->getKey()]);
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->type->getKey(),
            'service_provider_id' => $provider->getKey(),
        ]);
        $t1 = Tariff::factory()->create(['service_provider_id' => $provider->getKey(), 'utility_type_id' => $this->type->getKey(), 'name' => 'T1', 'effective_from' => '2026-01-01', 'effective_to' => null]);
        Tariff::factory()->create(['service_provider_id' => $provider->getKey(), 'utility_type_id' => $this->type->getKey(), 'name' => 'T2', 'effective_from' => '2026-01-01', 'effective_to' => null]);
        MeterReading::factory()->create(['meter_id' => $meter->getKey(), 'tariff_id' => $t1->getKey(), 'reading_value' => 77, 'reading_date' => '2026-06-01']);

        $result = $this->action->handle($this->user)[0];

        expect($result['tariff_zones'])->toHaveCount(2)
            ->and($result['tariff_zones'][0])->toMatchArray(['tariff_id' => $t1->getKey(), 'name' => 'T1', 'last_reading' => ['value' => 77.0, 'date' => '2026-06-01']])
            ->and($result['tariff_zones'][1]['last_reading'])->toBeNull()
            ->and($result['last_reading'])->toBeNull();
    });

    it('appends the apartment number to the address label', function (): void {
        $address = Address::factory()->make(['city' => 'Київ', 'street' => 'Шевченка', 'building_number' => '3', 'apartment_number' => '12']);

        expect($this->action->addressLabel($address))->toBe('Київ, Шевченка 3, кв. 12');
    });
});
