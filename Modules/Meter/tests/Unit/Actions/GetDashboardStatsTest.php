<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\GetDashboardStats;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

mutates(GetDashboardStats::class);

describe('GetDashboardStats', function (): void {
    it('returns correct stats for user with data', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $readingDate = CarbonImmutable::now();
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => $readingDate,
            'consumption' => 120.5,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => $readingDate->subDays(5),
            'consumption' => 80.0,
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetDashboardStats::run($addressIds);

        expect($result)
            ->toBeArray()
            ->addressCount->toBe(1)
            ->meterCount->toBe(1)
            ->lastReadingDate->toBe($readingDate->toDateString())
            ->totalMonthlyConsumption->toBe(200.5);
    });

    it('returns zeros for user without data', function (): void {
        $user = User::factory()->create();

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetDashboardStats::run($addressIds);

        expect($result)
            ->toBeArray()
            ->addressCount->toBe(0)
            ->meterCount->toBe(0)
            ->lastReadingDate->toBeNull()
            ->totalMonthlyConsumption->toBe(0.0);
    });

    it('counts only active meters', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
            'is_active' => true,
        ]);
        Meter::factory()->inactive()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetDashboardStats::run($addressIds);

        expect($result)->meterCount->toBe(1);
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
            'reading_date' => CarbonImmutable::now(),
            'consumption' => 100.0,
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetDashboardStats::run($addressIds);

        expect($result)
            ->addressCount->toBe(0)
            ->meterCount->toBe(0)
            ->lastReadingDate->toBeNull()
            ->totalMonthlyConsumption->toBe(0.0);
    });

    it('only sums consumption from current month', function (): void {
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
            'reading_date' => CarbonImmutable::now(),
            'consumption' => 50.0,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now()->subMonths(2),
            'consumption' => 200.0,
        ]);

        $addressIds = $user->addresses()->pluck('addresses.id');
        $result = GetDashboardStats::run($addressIds);

        expect($result)->totalMonthlyConsumption->toBe(50.0);
    });
});
