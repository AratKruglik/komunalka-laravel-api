<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->region = Region::factory()->create();
    $this->addressType = AddressType::factory()->create();
});

describe('GET /address (index)', function () {
    it('returns paginated addresses for authenticated user', function () {
        $addresses = Address::factory()->count(3)->create();
        foreach ($addresses as $address) {
            $this->user->addresses()->attach($address->id, ['is_primary' => false]);
        }

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index'))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'city', 'street', 'building_number', 'region', 'address_type']]]);
    });

    it('does not return addresses of other users', function () {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->id, ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index'))
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('supports sorting by is_primary', function () {
        $address1 = Address::factory()->create();
        $address2 = Address::factory()->create();
        $this->user->addresses()->attach($address1->id, ['is_primary' => false]);
        $this->user->addresses()->attach($address2->id, ['is_primary' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index', ['sort_by' => 'is_primary', 'desc' => 'true']))
            ->assertSuccessful();

        expect($response->json('data.0.is_primary'))->toBeTrue();
    });

    it('returns 401 for unauthenticated request', function () {
        $this->getJson(route('api.address.index'))->assertUnauthorized();
    });
});

describe('GET /address/{id} (show)', function () {
    it('returns address with nested region and address type', function () {
        $address = Address::factory()->create([
            'region_id' => $this->region->id,
            'address_type_id' => $this->addressType->id,
        ]);
        $this->user->addresses()->attach($address->id, ['is_primary' => true]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.show', $address->id))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $address->id)
            ->assertJsonPath('data.region.id', $this->region->id)
            ->assertJsonPath('data.address_type.id', $this->addressType->id)
            ->assertJsonPath('data.is_primary', true);
    });

    it('returns 404 for address owned by another user', function () {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->id, ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.show', $address->id))
            ->assertNotFound();
    });

    it('returns 404 for non-existent address', function () {
        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.show', 999))
            ->assertNotFound();
    });
});

describe('POST /address (store)', function () {
    it('creates a new address with pivot', function () {
        $payload = [
            'region_id' => $this->region->id,
            'address_type_id' => $this->addressType->id,
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
            ->assertJsonPath('data.region.id', $this->region->id);

        $this->assertDatabaseHas('addresses', ['city' => 'Київ', 'street' => 'Хрещатик']);
        $this->assertDatabaseHas('address_user', [
            'user_id' => $this->user->id,
            'is_primary' => true,
        ]);
    });

    it('clears previous primary when creating new primary address', function () {
        $existing = Address::factory()->create();
        $this->user->addresses()->attach($existing->id, ['is_primary' => true]);

        $payload = [
            'region_id' => $this->region->id,
            'address_type_id' => $this->addressType->id,
            'city' => 'Львів',
            'street' => 'Головна',
            'building_number' => '5',
            'is_primary' => true,
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.address.store'), $payload)
            ->assertSuccessful();

        $this->assertDatabaseHas('address_user', [
            'user_id' => $this->user->id,
            'address_id' => $existing->id,
            'is_primary' => false,
        ]);
    });

    it('validates required fields', function (string $field) {
        $payload = [
            'region_id' => $this->region->id,
            'address_type_id' => $this->addressType->id,
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

    it('returns Ukrainian validation messages', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.address.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('city');
    });

    it('validates region_id exists', function () {
        $payload = [
            'region_id' => 9999,
            'address_type_id' => $this->addressType->id,
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '1',
        ];

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.address.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('region_id');
    });
});

describe('PUT /address/{id} (update)', function () {
    it('updates all fields of an address', function () {
        $address = Address::factory()->create([
            'region_id' => $this->region->id,
            'address_type_id' => $this->addressType->id,
        ]);
        $this->user->addresses()->attach($address->id, ['is_primary' => false]);

        $newRegion = Region::factory()->create();
        $payload = [
            'region_id' => $newRegion->id,
            'address_type_id' => $this->addressType->id,
            'city' => 'Одеса',
            'street' => 'Дерибасівська',
            'building_number' => '10',
            'apartment_number' => null,
            'zip_code' => '65000',
            'notes' => 'Updated',
            'is_primary' => true,
        ];

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.address.update', $address->id), $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Одеса')
            ->assertJsonPath('data.region.id', $newRegion->id);

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'city' => 'Одеса']);
    });

    it('returns 404 for address owned by another user', function () {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->id, ['is_primary' => false]);

        $payload = [
            'region_id' => $this->region->id,
            'address_type_id' => $this->addressType->id,
            'city' => 'Одеса',
            'street' => 'Дерибасівська',
            'building_number' => '10',
        ];

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.address.update', $address->id), $payload)
            ->assertNotFound();
    });
});

describe('PATCH /address/{id} (patch)', function () {
    it('partially updates address fields', function () {
        $address = Address::factory()->create([
            'city' => 'Київ',
            'region_id' => $this->region->id,
            'address_type_id' => $this->addressType->id,
        ]);
        $this->user->addresses()->attach($address->id, ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->patchJson(route('api.address.patch', $address->id), ['city' => 'Харків'])
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Харків');

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'city' => 'Харків']);
    });

    it('toggles primary address', function () {
        $address1 = Address::factory()->create();
        $address2 = Address::factory()->create();
        $this->user->addresses()->attach($address1->id, ['is_primary' => true]);
        $this->user->addresses()->attach($address2->id, ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->patchJson(route('api.address.patch', $address2->id), ['is_primary' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('address_user', [
            'user_id' => $this->user->id,
            'address_id' => $address2->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('address_user', [
            'user_id' => $this->user->id,
            'address_id' => $address1->id,
            'is_primary' => false,
        ]);
    });

    it('returns 404 for address owned by another user', function () {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->id, ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->patchJson(route('api.address.patch', $address->id), ['city' => 'Тест'])
            ->assertNotFound();
    });
});

describe('DELETE /address/{id} (destroy)', function () {
    it('soft deletes address and removes pivot', function () {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->id, ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.address.destroy', $address->id))
            ->assertSuccessful();

        $this->assertSoftDeleted('addresses', ['id' => $address->id]);
        $this->assertDatabaseMissing('address_user', [
            'user_id' => $this->user->id,
            'address_id' => $address->id,
        ]);
    });

    it('returns 404 for address owned by another user', function () {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->id, ['is_primary' => false]);

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.address.destroy', $address->id))
            ->assertNotFound();
    });
});
