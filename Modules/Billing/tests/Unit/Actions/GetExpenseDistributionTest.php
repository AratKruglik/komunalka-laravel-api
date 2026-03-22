<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Actions\GetExpenseDistribution;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

mutates(GetExpenseDistribution::class);

describe('GetExpenseDistribution', function (): void {
    it('returns expected structure', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create(['display_name' => 'Електрика']);
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $tariff = Tariff::factory()->create([
            'utility_type_id' => $utilityType->getKey(),
            'base_rate' => 2.5,
            'service_fee' => 10.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'tariff_id' => $tariff->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonth(),
            'consumption' => 100.0,
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetExpenseDistribution::run($addressIds);

        expect($result)->toHaveCount(1)
            ->and($result->first())
            ->toHaveKeys(['type', 'displayName', 'amount', 'percentage']);
    });

    it('returns correct data for user with readings and tariffs', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create([
            'slug' => 'electricity',
            'display_name' => 'Електрика',
        ]);
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $tariff = Tariff::factory()->create([
            'utility_type_id' => $utilityType->getKey(),
            'base_rate' => 2.0,
            'service_fee' => 5.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'tariff_id' => $tariff->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonth(),
            'consumption' => 100.0,
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetExpenseDistribution::run($addressIds);

        expect($result)->toHaveCount(1)
            ->and($result->first())
            ->toMatchArray([
                'type' => 'electricity',
                'displayName' => 'Електрика',
                'amount' => 205.0,
                'percentage' => 100.0,
            ]);
    });

    it('returns empty collection for user without data', function (): void {
        $user = User::factory()->create();

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetExpenseDistribution::run($addressIds);

        expect($result)->toBeEmpty();
    });

    it('does not return other users data', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $tariff = Tariff::factory()->create([
            'utility_type_id' => $utilityType->getKey(),
            'base_rate' => 5.0,
            'service_fee' => 10.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'tariff_id' => $tariff->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonth(),
            'consumption' => 200.0,
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetExpenseDistribution::run($addressIds);

        expect($result)->toBeEmpty();
    });

    it('calculates percentage distribution across utility types', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $electricity = UtilityType::factory()->create(['slug' => 'electricity', 'display_name' => 'Електрика']);
        $gas = UtilityType::factory()->create(['slug' => 'gas', 'display_name' => 'Газ']);

        $electricMeter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $electricity->getKey(),
        ]);
        $gasMeter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $gas->getKey(),
        ]);

        $electricTariff = Tariff::factory()->create([
            'utility_type_id' => $electricity->getKey(),
            'base_rate' => 2.0,
            'service_fee' => 0.0,
        ]);
        $gasTariff = Tariff::factory()->create([
            'utility_type_id' => $gas->getKey(),
            'base_rate' => 8.0,
            'service_fee' => 0.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $electricMeter->getKey(),
            'tariff_id' => $electricTariff->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonth(),
            'consumption' => 100.0,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $gasMeter->getKey(),
            'tariff_id' => $gasTariff->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonth(),
            'consumption' => 100.0,
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetExpenseDistribution::run($addressIds);

        expect($result)->toHaveCount(2);

        $totalPercentage = $result->sum('percentage');
        expect($totalPercentage)->toBe(100.0);

        $electricEntry = $result->firstWhere('type', 'electricity');
        $gasEntry = $result->firstWhere('type', 'gas');

        expect($electricEntry['amount'])->toBe(200.0)
            ->and($gasEntry['amount'])->toBe(800.0)
            ->and($electricEntry['percentage'])->toBe(20.0)
            ->and($gasEntry['percentage'])->toBe(80.0);
    });
});
