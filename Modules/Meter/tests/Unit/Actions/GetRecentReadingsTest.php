<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\GetRecentReadings;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

mutates(GetRecentReadings::class);

describe('GetRecentReadings', function (): void {
    it('returns expected structure with eager loaded relations', function (): void {
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
        ]);

        $result = GetRecentReadings::run($user);

        expect($result)->toHaveCount(1)
            ->and($result->first()->relationLoaded('meter'))->toBeTrue()
            ->and($result->first()->meter->relationLoaded('utilityType'))->toBeTrue()
            ->and($result->first()->meter->relationLoaded('address'))->toBeTrue();
    });

    it('returns readings ordered by date descending', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $olderReading = MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now()->subDays(10),
        ]);
        $newerReading = MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now(),
        ]);

        $result = GetRecentReadings::run($user);

        expect($result->first()->getKey())->toBe($newerReading->getKey())
            ->and($result->last()->getKey())->toBe($olderReading->getKey());
    });

    it('returns empty collection for user without data', function (): void {
        $user = User::factory()->create();

        $result = GetRecentReadings::run($user);

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
            'reading_date' => CarbonImmutable::now(),
        ]);

        $result = GetRecentReadings::run($user);

        expect($result)->toBeEmpty();
    });

    it('respects the limit parameter', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        MeterReading::factory()->count(5)->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now(),
        ]);

        $result = GetRecentReadings::run($user, limit: 3);

        expect($result)->toHaveCount(3);
    });

    it('defaults to 10 readings limit', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        MeterReading::factory()->count(15)->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now(),
        ]);

        $result = GetRecentReadings::run($user);

        expect($result)->toHaveCount(10);
    });
});
