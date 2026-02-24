<?php

declare(strict_types=1);

use Modules\Address\Actions\CreateAddress;
use Modules\Address\Actions\PatchAddress;
use Modules\Address\DTOs\CreateAddressData;
use Modules\Address\DTOs\PatchAddressData;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

it('partially updates only provided fields', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $addressType = AddressType::factory()->create();

    $address = app(CreateAddress::class)->handle($user->id, new CreateAddressData(
        regionId: $region->id,
        addressTypeId: $addressType->id,
        city: 'Київ',
        street: 'Хрещатик',
        buildingNumber: '1',
        apartmentNumber: null,
        zipCode: null,
        notes: null,
        isPrimary: false,
    ));

    $patched = app(PatchAddress::class)->handle($user->id, $address, new PatchAddressData(
        regionId: null,
        addressTypeId: null,
        city: 'Харків',
        street: null,
        buildingNumber: null,
        apartmentNumber: null,
        zipCode: null,
        notes: null,
        isPrimary: null,
    ));

    expect($patched->city)->toBe('Харків')
        ->and($patched->street)->toBe('Хрещатик');
});

it('toggles primary and clears previous', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $addressType = AddressType::factory()->create();

    $address1 = app(CreateAddress::class)->handle($user->id, new CreateAddressData(
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

    $address2 = app(CreateAddress::class)->handle($user->id, new CreateAddressData(
        regionId: $region->id,
        addressTypeId: $addressType->id,
        city: 'Львів',
        street: 'Головна',
        buildingNumber: '5',
        apartmentNumber: null,
        zipCode: null,
        notes: null,
        isPrimary: false,
    ));

    app(PatchAddress::class)->handle($user->id, $address2, new PatchAddressData(
        regionId: null,
        addressTypeId: null,
        city: null,
        street: null,
        buildingNumber: null,
        apartmentNumber: null,
        zipCode: null,
        notes: null,
        isPrimary: true,
    ));

    $this->assertDatabaseHas('address_user', [
        'address_id' => $address1->id,
        'is_primary' => false,
    ]);
    $this->assertDatabaseHas('address_user', [
        'address_id' => $address2->id,
        'is_primary' => true,
    ]);
});
