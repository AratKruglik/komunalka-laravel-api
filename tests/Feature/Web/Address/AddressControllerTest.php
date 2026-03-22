<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->region = Region::factory()->create();
    $this->addressType = AddressType::factory()->create();
});

describe('AddressController', function (): void {
    it('lists user addresses', function (): void {
        $addresses = Address::factory()->count(3)->create();
        foreach ($addresses as $address) {
            $this->user->addresses()->attach($address->getKey(), ['is_primary' => false]);
        }

        $this->actingAs($this->user)
            ->get(route('addresses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Addresses/Index')
                ->has('addresses.data', 3),
            );
    });

    it('renders edit page with address data', function (): void {
        $address = Address::factory()->create([
            'region_id' => $this->region->getKey(),
            'address_type_id' => $this->addressType->getKey(),
            'city' => 'Львів',
        ]);
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user)
            ->get(route('addresses.edit', $address->getKey()))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Addresses/Edit')
                ->has('address')
                ->has('regions')
                ->has('addressTypes'),
            );
    });

    it('creates address and redirects', function (): void {
        $payload = [
            'region_id' => $this->region->getKey(),
            'address_type_id' => $this->addressType->getKey(),
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '1',
            'apartment_number' => '10',
            'zip_code' => '01001',
            'is_primary' => true,
        ];

        $this->actingAs($this->user)
            ->post(route('addresses.store'), $payload)
            ->assertRedirect(route('addresses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('addresses', ['city' => 'Київ', 'street' => 'Хрещатик']);
        $this->assertDatabaseHas('address_user', [
            'user_id' => $this->user->getKey(),
            'is_primary' => true,
        ]);
    });

    it('shows validation errors on invalid data', function (string $field): void {
        $payload = [
            'region_id' => $this->region->getKey(),
            'address_type_id' => $this->addressType->getKey(),
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '1',
        ];
        unset($payload[$field]);

        $this->actingAs($this->user)
            ->post(route('addresses.store'), $payload)
            ->assertSessionHasErrors($field);
    })->with(['region_id', 'address_type_id', 'city', 'street', 'building_number']);

    it('validates region_id and address_type_id exist', function (): void {
        $payload = [
            'region_id' => 9999,
            'address_type_id' => 9999,
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '1',
        ];

        $this->actingAs($this->user)
            ->post(route('addresses.store'), $payload)
            ->assertSessionHasErrors(['region_id', 'address_type_id']);
    });

    it('updates address and redirects', function (): void {
        $address = Address::factory()->create([
            'region_id' => $this->region->getKey(),
            'address_type_id' => $this->addressType->getKey(),
        ]);
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $newRegion = Region::factory()->create();
        $payload = [
            'region_id' => $newRegion->getKey(),
            'address_type_id' => $this->addressType->getKey(),
            'city' => 'Одеса',
            'street' => 'Дерибасівська',
            'building_number' => '10',
            'apartment_number' => null,
            'zip_code' => '65000',
            'notes' => 'Updated',
            'is_primary' => true,
        ];

        $this->actingAs($this->user)
            ->put(route('addresses.update', $address->getKey()), $payload)
            ->assertRedirect(route('addresses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
            'city' => 'Одеса',
        ]);
    });

    it('deletes address and redirects', function (): void {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user)
            ->delete(route('addresses.destroy', $address->getKey()))
            ->assertRedirect(route('addresses.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('addresses', ['id' => $address->getKey()]);
        $this->assertDatabaseMissing('address_user', [
            'user_id' => $this->user->getKey(),
            'address_id' => $address->getKey(),
        ]);
    });

    it('requires authentication for all routes', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))->assertRedirect(route('login'));
    })->with([
        ['get', 'addresses.index', []],
        ['get', 'addresses.edit', [1]],
        ['post', 'addresses.store', []],
        ['put', 'addresses.update', [1]],
        ['delete', 'addresses.destroy', [1]],
    ]);

    it('prevents access to other users addresses', function (): void {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user)
            ->get(route('addresses.edit', $address->getKey()))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->put(route('addresses.update', $address->getKey()), [
                'region_id' => $this->region->getKey(),
                'address_type_id' => $this->addressType->getKey(),
                'city' => 'Test',
                'street' => 'Test',
                'building_number' => '1',
            ])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('addresses.destroy', $address->getKey()))
            ->assertNotFound();
    });
});
