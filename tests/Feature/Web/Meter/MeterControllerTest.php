<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
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

        $this->actingAs($this->user)
            ->get(route('meters.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 2),
            );
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

        $this->actingAs($this->user)
            ->get(route('meters.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 2),
            );
    });

    it('renders edit page with meter data', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.edit', $meter->getKey()))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Meters/Edit')
                ->has('meter'),
            );
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

        $this->actingAs($this->user)
            ->post(route('meters.store'), $payload)
            ->assertRedirect(route('meters.index'))
            ->assertSessionHas('success');

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

        $this->actingAs($this->user)
            ->post(route('meters.store'), $payload)
            ->assertSessionHasErrors($field);
    })->with(['address_id', 'utility_type_id', 'serial_number', 'name', 'initial_reading']);

    it('updates meter and redirects', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Старий лічильник',
        ]);

        $this->actingAs($this->user)
            ->put(route('meters.update', $meter->getKey()), ['name' => 'Новий лічильник'])
            ->assertRedirect(route('meters.index'))
            ->assertSessionHas('success');

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

        $this->actingAs($this->user)
            ->delete(route('meters.destroy', $meter->getKey()))
            ->assertRedirect(route('meters.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('meters', ['id' => $meter->getKey()]);
    });

    it('requires authentication', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))->assertRedirect(route('login'));
    })->with([
        ['get', 'meters.index', []],
        ['get', 'meters.edit', [1]],
        ['post', 'meters.store', []],
        ['put', 'meters.update', [1]],
        ['delete', 'meters.destroy', [1]],
    ]);

    it('prevents access to other users meters', function (): void {
        $otherAddress = Address::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $otherAddress->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.edit', $meter->getKey()))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->put(route('meters.update', $meter->getKey()), ['name' => 'Updated'])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('meters.destroy', $meter->getKey()))
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

        $this->actingAs($this->user)
            ->post(route('meters.store'), $payload)
            ->assertNotFound();
    });
});
