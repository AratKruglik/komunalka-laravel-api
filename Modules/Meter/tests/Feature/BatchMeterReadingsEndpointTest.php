<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
    $this->meter = Meter::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
        'initial_reading' => 100,
    ]);
});

describe('POST /api/v1/meter-readings/batch (store)', function () {
    it('creates readings with consumption calculation', function () {
        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->id,
                    'reading_value' => 150,
                    'reading_date' => '2026-02-01',
                ],
            ],
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.readings.0.consumption', 50)
            ->assertJsonPath('data.readings.0.reading_value', 150);
    });

    it('returns 422 when reading value is less than previous', function () {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'reading_value' => 200,
            'reading_date' => '2026-01-01',
        ]);

        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->id,
                    'reading_value' => 150,
                    'reading_date' => '2026-02-01',
                ],
            ],
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), $payload)
            ->assertUnprocessable();
    });

    it('auto-detects tariff when service provider exists', function () {
        $currency = Currency::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        Tariff::factory()->create([
            'service_provider_id' => $serviceProvider->id,
            'utility_type_id' => $this->utilityType->id,
            'currency_id' => $currency->id,
            'base_rate' => '2.5000',
            'service_fee' => '10.0000',
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
        ]);

        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'service_provider_id' => $serviceProvider->id,
            'initial_reading' => 0,
        ]);

        $payload = [
            'readings' => [
                [
                    'meter_id' => $meter->id,
                    'reading_value' => 100,
                    'reading_date' => '2026-02-01',
                ],
            ],
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), $payload)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['readings', 'tariff_calculations']]);
    });
});

describe('GET /api/v1/meter-readings/address/{addressId} (byAddress)', function () {
    it('returns readings by address', function () {
        MeterReading::factory()->count(3)->create([
            'meter_id' => $this->meter->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.by-address', $this->address->getKey()))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data');
    });

    it('filters by date range', function () {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'reading_date' => '2026-01-15',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'reading_date' => '2026-02-15',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'reading_date' => '2026-03-15',
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.by-address', ['addressId' => $this->address->getKey(), 'from' => '2026-02-01', 'to' => '2026-02-28']))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/v1/meter-readings/{id} (show)', function () {
    it('returns a reading', function () {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.show', $reading->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $reading->id)
            ->assertJsonStructure(['data' => ['id', 'reading_value', 'reading_date', 'consumption']]);
    });

    it('returns 404 for non-owned reading', function () {
        $otherAddress = Address::factory()->create();
        $otherMeter = Meter::factory()->create(['address_id' => $otherAddress->id]);
        $reading = MeterReading::factory()->create(['meter_id' => $otherMeter->id]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.show', $reading->getKey()))
            ->assertNotFound();
    });
});

describe('DELETE /api/v1/meter-readings/{id} (destroy)', function () {
    it('deletes reading', function () {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.meter-readings.destroy', $reading->getKey()))
            ->assertSuccessful();

        $this->assertDatabaseMissing('meter_readings', ['id' => $reading->id]);
    });

    it('returns 404 for non-owned reading', function () {
        $otherAddress = Address::factory()->create();
        $otherMeter = Meter::factory()->create(['address_id' => $otherAddress->id]);
        $reading = MeterReading::factory()->create(['meter_id' => $otherMeter->id]);

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.meter-readings.destroy', $reading->getKey()))
            ->assertNotFound();
    });
});
