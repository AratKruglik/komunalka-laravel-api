<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\GetConsumptionHistory;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

mutates(GetConsumptionHistory::class);

describe('GetConsumptionHistory', function (): void {
    it('returns expected structure', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonth(),
            'consumption' => 100.0,
        ]);

        $result = GetConsumptionHistory::run($user);

        expect($result)->toHaveCount(1)
            ->and($result->first())
            ->toHaveKeys(['month', 'utilityType', 'value']);
    });

    it('returns correct data for user with readings', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create(['slug' => 'electricity']);
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $readingDate = CarbonImmutable::now()->subMonth();
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => $readingDate,
            'consumption' => 150.0,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => $readingDate->addDays(5),
            'consumption' => 50.0,
        ]);

        $result = GetConsumptionHistory::run($user);

        expect($result)->toHaveCount(1)
            ->and($result->first())
            ->toMatchArray([
                'month' => $readingDate->format('Y-m'),
                'utilityType' => 'electricity',
                'value' => 200.0,
            ]);
    });

    it('returns empty collection for user without data', function (): void {
        $user = User::factory()->create();

        $result = GetConsumptionHistory::run($user);

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
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonth(),
            'consumption' => 100.0,
        ]);

        $result = GetConsumptionHistory::run($user);

        expect($result)->toBeEmpty();
    });

    it('groups data by month and utility type', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $electricity = UtilityType::factory()->create(['slug' => 'electricity']);
        $gas = UtilityType::factory()->create(['slug' => 'gas']);

        $electricMeter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $electricity->getKey(),
        ]);
        $gasMeter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $gas->getKey(),
        ]);

        $month = CarbonImmutable::now()->subMonth();
        MeterReading::factory()->create([
            'meter_id' => $electricMeter->getKey(),
            'reading_date' => $month,
            'consumption' => 100.0,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $gasMeter->getKey(),
            'reading_date' => $month,
            'consumption' => 30.0,
        ]);

        $result = GetConsumptionHistory::run($user);

        expect($result)->toHaveCount(2);

        $types = $result->pluck('utilityType')->sort()->values()->all();
        expect($types)->toBe(['electricity', 'gas']);
    });

    it('excludes readings older than specified months', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonths(2),
            'consumption' => 100.0,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonths(8),
            'consumption' => 200.0,
        ]);

        $result = GetConsumptionHistory::run($user, months: 6);

        expect($result)->toHaveCount(1);
    });
});
