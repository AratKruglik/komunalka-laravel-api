<?php

declare(strict_types=1);

use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

describe('E2E: address CRUD lifecycle', function () {
    it('creates, lists, updates, toggles primary, deletes addresses', function () {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $firstAddress = $this->actingAs($user, 'api')
            ->postJson(route('api.address.store'), [
                'region_id' => $region->id,
                'address_type_id' => $addressType->id,
                'city' => 'Київ',
                'street' => 'Хрещатик',
                'building_number' => '1',
                'is_primary' => true,
            ])->assertSuccessful();

        $firstId = $firstAddress->json('data.id');

        $this->assertDatabaseHas('address_user', [
            'user_id' => $user->id,
            'address_id' => $firstId,
            'is_primary' => true,
        ]);

        $secondAddress = $this->actingAs($user, 'api')
            ->postJson(route('api.address.store'), [
                'region_id' => $region->id,
                'address_type_id' => $addressType->id,
                'city' => 'Львів',
                'street' => 'Головна',
                'building_number' => '5',
                'is_primary' => false,
            ])->assertSuccessful();

        $secondId = $secondAddress->json('data.id');

        $this->actingAs($user, 'api')
            ->getJson(route('api.address.index'))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');

        $this->actingAs($user, 'api')
            ->putJson(route('api.address.update', $firstId), [
                'region_id' => $region->id,
                'address_type_id' => $addressType->id,
                'city' => 'Одеса',
                'street' => 'Дерибасівська',
                'building_number' => '10',
            ])->assertSuccessful()
            ->assertJsonPath('data.city', 'Одеса');

        $this->actingAs($user, 'api')
            ->patchJson(route('api.address.patch', $secondId), ['is_primary' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('address_user', [
            'user_id' => $user->id,
            'address_id' => $secondId,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('address_user', [
            'user_id' => $user->id,
            'address_id' => $firstId,
            'is_primary' => false,
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson(route('api.address.destroy', $firstId))
            ->assertSuccessful();

        $this->actingAs($user, 'api')
            ->getJson(route('api.address.index'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user, 'api')
            ->getJson(route('api.address.show', $firstId))
            ->assertNotFound();
    });
});
