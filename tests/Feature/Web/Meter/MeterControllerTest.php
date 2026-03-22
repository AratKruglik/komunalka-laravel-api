<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('MeterController', function (): void {
    it('lists meters', function (): void {
        Meter::factory()->count(2)->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.index'))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('filters meters by address', function (): void {
        Meter::factory()->count(2)->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $otherAddress = Address::factory()->create();
        $this->user->addresses()->attach($otherAddress->getKey(), ['is_primary' => false]);
        Meter::factory()->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.by-address', $this->address->getKey()))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('returns only active meters', function (): void {
        Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'is_active' => true,
        ]);

        Meter::factory()->inactive()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.active'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });

    it('renders show with meter data', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.show', $meter->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $meter->getKey())
            ->assertJsonStructure([
                'data' => ['id', 'serial_number', 'name', 'is_active', 'address_id', 'utility_type'],
            ]);
    });

    it('creates meter and redirects', function (): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'serial_number' => 'M-123456',
            'name' => 'Лічильник газу',
            'initial_reading' => 0,
            'is_active' => true,
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Лічильник газу')
            ->assertJsonPath('data.address_id', $this->address->getKey());

        $this->assertDatabaseHas('meters', [
            'name' => 'Лічильник газу',
            'serial_number' => 'M-123456',
        ]);
    });

    it('validates required fields on create', function (string $field): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'serial_number' => 'M-123456',
            'name' => 'Test Meter',
            'initial_reading' => 0,
        ];
        unset($payload[$field]);

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['address_id', 'utility_type_id', 'serial_number', 'name', 'initial_reading']);

    it('updates meter and redirects', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Старий лічильник',
        ]);

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.meter.update', $meter->getKey()), ['name' => 'Новий лічильник'])
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Новий лічильник');

        $this->assertDatabaseHas('meters', [
            'id' => $meter->getKey(),
            'name' => 'Новий лічильник',
        ]);
    });

    it('deletes meter and redirects', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.meter.destroy', $meter->getKey()))
            ->assertSuccessful();

        $this->assertDatabaseMissing('meters', ['id' => $meter->getKey()]);
    });

    it('requires authentication', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))->assertUnauthorized();
    })->with([
        ['getJson', 'api.meter.index', []],
        ['getJson', 'api.meter.active', []],
        ['getJson', 'api.meter.show', [1]],
        ['getJson', 'api.meter.by-address', [1]],
        ['postJson', 'api.meter.store', []],
        ['putJson', 'api.meter.update', [1]],
        ['deleteJson', 'api.meter.destroy', [1]],
    ]);

    it('prevents access to other users meters', function (): void {
        $otherAddress = Address::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $otherAddress->getKey(),
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.show', $meter->getKey()))
            ->assertNotFound();

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.meter.update', $meter->getKey()), ['name' => 'Updated'])
            ->assertNotFound();

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.meter.destroy', $meter->getKey()))
            ->assertNotFound();
    });

    it('prevents creating meter for non-owned address', function (): void {
        $otherAddress = Address::factory()->create();

        $payload = [
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'serial_number' => 'M-999999',
            'name' => 'Test Meter',
            'initial_reading' => 0,
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.store'), $payload)
            ->assertNotFound();
    });
});
