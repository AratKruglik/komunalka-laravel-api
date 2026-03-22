<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
    $this->meter = Meter::factory()->create([
        'address_id' => $this->address->getKey(),
        'utility_type_id' => $this->utilityType->getKey(),
        'initial_reading' => 100,
    ]);
});

describe('ReadingController', function (): void {
    it('lists readings by address', function (): void {
        MeterReading::factory()->count(3)->create([
            'meter_id' => $this->meter->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.by-address', $this->address->getKey()))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data');
    });

    it('filters readings by date range', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_date' => '2026-01-15',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_date' => '2026-02-15',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_date' => '2026-03-15',
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.by-address', [
                'addressId' => $this->address->getKey(),
                'from' => '2026-02-01',
                'to' => '2026-02-28',
            ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });

    it('shows a single reading', function (): void {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.show', $reading->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $reading->getKey())
            ->assertJsonStructure([
                'data' => ['id', 'reading_value', 'reading_date', 'consumption'],
            ]);
    });

    it('creates batch readings', function (): void {
        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->getKey(),
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

    it('validates batch reading fields', function (): void {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('readings');
    });

    it('validates individual reading fields in batch', function (): void {
        $payload = [
            'readings' => [
                [
                    'reading_value' => 150,
                    'reading_date' => '2026-02-01',
                ],
            ],
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('readings.0.meter_id');
    });

    it('rejects reading value less than previous', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 200,
            'reading_date' => '2026-01-01',
        ]);

        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->getKey(),
                    'reading_value' => 150,
                    'reading_date' => '2026-02-01',
                ],
            ],
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter-readings.store'), $payload)
            ->assertUnprocessable();
    });

    it('deletes reading', function (): void {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.meter-readings.destroy', $reading->getKey()))
            ->assertSuccessful();

        $this->assertDatabaseMissing('meter_readings', ['id' => $reading->getKey()]);
    });

    it('requires authentication', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))->assertUnauthorized();
    })->with([
        ['getJson', 'api.meter-readings.by-address', [1]],
        ['getJson', 'api.meter-readings.show', [1]],
        ['postJson', 'api.meter-readings.store', []],
        ['deleteJson', 'api.meter-readings.destroy', [1]],
    ]);

    it('prevents access to other users readings', function (): void {
        $otherAddress = Address::factory()->create();
        $otherMeter = Meter::factory()->create(['address_id' => $otherAddress->getKey()]);
        $reading = MeterReading::factory()->create(['meter_id' => $otherMeter->getKey()]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.show', $reading->getKey()))
            ->assertNotFound();

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.meter-readings.destroy', $reading->getKey()))
            ->assertNotFound();
    });
});
