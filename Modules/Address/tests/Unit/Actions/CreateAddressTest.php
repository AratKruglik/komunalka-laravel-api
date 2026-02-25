<?php

declare(strict_types=1);

use Modules\Address\Actions\CreateAddress;
use Modules\Address\DTO\CreateAddressData;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

it('creates address and attaches to user via pivot', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $addressType = AddressType::factory()->create();

    $data = new CreateAddressData(
        regionId: $region->id,
        addressTypeId: $addressType->id,
        city: 'Київ',
        street: 'Хрещатик',
        buildingNumber: '1',
        apartmentNumber: '10',
        zipCode: '01001',
        notes: null,
        isPrimary: false,
    );

    $address = app(CreateAddress::class)->handle($user->id, $data);

    expect($address->city)->toBe('Київ');
    $this->assertDatabaseHas('addresses', ['city' => 'Київ']);
    $this->assertDatabaseHas('address_user', [
        'user_id' => $user->id,
        'address_id' => $address->id,
        'is_primary' => false,
    ]);
});

it('sets primary and clears previous primary', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $addressType = AddressType::factory()->create();

    $first = app(CreateAddress::class)->handle($user->id, new CreateAddressData(
        regionId: $region->id,
        addressTypeId: $addressType->id,
        city: 'Київ',
        street: 'Хрещатик',
        buildingNumber: '1',
        apartmentNumber: null,
        zipCode: null,
        notes: null,
        isPrimary: true,
    ));

    $second = app(CreateAddress::class)->handle($user->id, new CreateAddressData(
        regionId: $region->id,
        addressTypeId: $addressType->id,
        city: 'Львів',
        street: 'Головна',
        buildingNumber: '5',
        apartmentNumber: null,
        zipCode: null,
        notes: null,
        isPrimary: true,
    ));

    $this->assertDatabaseHas('address_user', [
        'user_id' => $user->id,
        'address_id' => $first->id,
        'is_primary' => false,
    ]);
    $this->assertDatabaseHas('address_user', [
        'user_id' => $user->id,
        'address_id' => $second->id,
        'is_primary' => true,
    ]);
});
