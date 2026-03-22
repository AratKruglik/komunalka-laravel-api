<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
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

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('readings'),
            );
    });

    it('renders readings index page', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('addresses'),
            );
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

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertRedirect(route('readings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 150,
            'consumption' => 50,
        ]);
    });

    it('validates batch reading fields', function (): void {
        $this->actingAs($this->user)
            ->post(route('readings.store'), [])
            ->assertSessionHasErrors('readings');
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

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertSessionHasErrors('readings.0.meter_id');
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

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertUnprocessable();
    });

    it('deletes reading', function (): void {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('readings.destroy', $reading->getKey()))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('meter_readings', ['id' => $reading->getKey()]);
    });

    it('requires authentication', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))->assertRedirect(route('login'));
    })->with([
        ['get', 'readings.index', []],
        ['post', 'readings.store', []],
        ['delete', 'readings.destroy', [1]],
    ]);

    it('prevents access to other users readings', function (): void {
        $otherAddress = Address::factory()->create();
        $otherMeter = Meter::factory()->create(['address_id' => $otherAddress->getKey()]);
        $reading = MeterReading::factory()->create(['meter_id' => $otherMeter->getKey()]);

        $this->actingAs($this->user)
            ->delete(route('readings.destroy', $reading->getKey()))
            ->assertNotFound();
    });
});
