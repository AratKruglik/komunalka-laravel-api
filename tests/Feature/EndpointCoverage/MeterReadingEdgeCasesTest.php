<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
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

describe('POST /api/v1/meter-readings/batch (edge cases)', function () {
    it('creates readings for multiple meters in one batch', function () {
        $meter2 = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'initial_reading' => 0,
        ]);

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->id,
                        'reading_value' => 200,
                        'reading_date' => '2026-02-01',
                    ],
                    [
                        'meter_id' => $meter2->id,
                        'reading_value' => 50,
                        'reading_date' => '2026-02-01',
                    ],
                ],
            ])->assertCreated()
            ->assertJsonCount(2, 'data.readings');
    });

    it('rejects meter not owned by user', function () {
        $otherAddress = Address::factory()->create();
        $otherMeter = Meter::factory()->create([
            'address_id' => $otherAddress->id,
            'utility_type_id' => $this->utilityType->id,
            'initial_reading' => 0,
        ]);

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $otherMeter->id,
                        'reading_value' => 100,
                        'reading_date' => '2026-02-01',
                    ],
                ],
            ])->assertNotFound();
    });

    it('handles inactive meter reading gracefully', function () {
        $inactiveMeter = Meter::factory()->inactive()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'initial_reading' => 0,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $inactiveMeter->id,
                        'reading_value' => 100,
                        'reading_date' => '2026-02-01',
                    ],
                ],
            ]);

        expect($response->status())->not->toBe(500);
    });

    it('rejects empty readings array', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), [
                'readings' => [],
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('readings');
    });
});

describe('GET /api/v1/meter-readings/address/{addressId} (edge cases)', function () {
    it('returns 404 for non-owned address', function () {
        $otherAddress = Address::factory()->create();

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.by-address', ['addressId' => $otherAddress->id]))
            ->assertNotFound();
    });
});

describe('POST /api/v1/service-providers (edge cases)', function () {
    it('validates utility_type_id exists', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.service-providers.store'), [
                'address_id' => $this->address->id,
                'utility_type_id' => 99999,
                'name' => 'Test Provider',
                'account_number' => 'ACC-TEST',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('utility_type_id');
    });

    it('validates tariff currency_id exists', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.service-providers.store'), [
                'address_id' => $this->address->id,
                'utility_type_id' => $this->utilityType->id,
                'name' => 'Test Provider',
                'account_number' => 'ACC-TEST',
                'tariffs' => [
                    [
                        'utility_type_id' => $this->utilityType->id,
                        'currency_id' => 99999,
                        'base_rate' => '1.0000',
                        'effective_from' => '2026-01-01',
                        'effective_to' => '2026-12-31',
                    ],
                ],
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('tariffs.0.currency_id');
    });
});
