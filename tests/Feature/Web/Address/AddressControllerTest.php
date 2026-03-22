<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

beforeEach(function (): void {
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

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index'))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'city', 'street', 'building_number', 'region', 'address_type']],
            ]);
    });

    it('renders show with reference data', function (): void {
        $address = Address::factory()->create([
            'region_id' => $this->region->getKey(),
            'address_type_id' => $this->addressType->getKey(),
        ]);
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.show', $address->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $address->getKey())
            ->assertJsonPath('data.region.id', $this->region->getKey())
            ->assertJsonPath('data.address_type.id', $this->addressType->getKey())
            ->assertJsonPath('data.is_primary', true);
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

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.address.store'), $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Київ')
            ->assertJsonPath('data.street', 'Хрещатик')
            ->assertJsonPath('data.region.id', $this->region->getKey());

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

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.address.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['region_id', 'address_type_id', 'city', 'street', 'building_number']);

    it('validates region_id and address_type_id exist', function (): void {
        $payload = [
            'region_id' => 9999,
            'address_type_id' => 9999,
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '1',
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.address.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['region_id', 'address_type_id']);
    });

    it('renders edit form with address data', function (): void {
        $address = Address::factory()->create([
            'region_id' => $this->region->getKey(),
            'address_type_id' => $this->addressType->getKey(),
            'city' => 'Львів',
        ]);
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.show', $address->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Львів')
            ->assertJsonPath('data.id', $address->getKey());
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

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.address.update', $address->getKey()), $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Одеса')
            ->assertJsonPath('data.region.id', $newRegion->getKey());

        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
            'city' => 'Одеса',
        ]);
    });

    it('partially updates address via patch', function (): void {
        $address = Address::factory()->create([
            'city' => 'Київ',
            'region_id' => $this->region->getKey(),
            'address_type_id' => $this->addressType->getKey(),
        ]);
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->patchJson(route('api.address.patch', $address->getKey()), ['city' => 'Харків'])
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Харків');

        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
            'city' => 'Харків',
        ]);
    });

    it('deletes address and redirects', function (): void {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.address.destroy', $address->getKey()))
            ->assertSuccessful();

        $this->assertSoftDeleted('addresses', ['id' => $address->getKey()]);
        $this->assertDatabaseMissing('address_user', [
            'user_id' => $this->user->getKey(),
            'address_id' => $address->getKey(),
        ]);
    });

    it('requires authentication for all routes', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))->assertUnauthorized();
    })->with([
        ['getJson', 'api.address.index', []],
        ['getJson', 'api.address.show', [1]],
        ['postJson', 'api.address.store', []],
        ['putJson', 'api.address.update', [1]],
        ['patchJson', 'api.address.patch', [1]],
        ['deleteJson', 'api.address.destroy', [1]],
    ]);

    it('prevents access to other users addresses', function (): void {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.show', $address->getKey()))
            ->assertNotFound();

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.address.update', $address->getKey()), [
                'region_id' => $this->region->getKey(),
                'address_type_id' => $this->addressType->getKey(),
                'city' => 'Test',
                'street' => 'Test',
                'building_number' => '1',
            ])
            ->assertNotFound();

        $this->actingAs($this->user, 'api')
            ->patchJson(route('api.address.patch', $address->getKey()), ['city' => 'Test'])
            ->assertNotFound();

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.address.destroy', $address->getKey()))
            ->assertNotFound();
    });
});
