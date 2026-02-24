<?php

declare(strict_types=1);

use Modules\Address\Actions\CreateAddress;
use Modules\Address\Actions\UpdateAddress;
use Modules\Address\DTOs\CreateAddressData;
use Modules\Address\DTOs\UpdateAddressData;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

it('updates all fields of an address', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $newRegion = Region::factory()->create();
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

    $updated = app(UpdateAddress::class)->handle($user->id, $address, new UpdateAddressData(
        regionId: $newRegion->id,
        addressTypeId: $addressType->id,
        city: 'Одеса',
        street: 'Дерибасівська',
        buildingNumber: '10',
        apartmentNumber: '5',
        zipCode: '65000',
        notes: 'Updated',
        isPrimary: true,
    ));

    expect($updated->city)->toBe('Одеса')
        ->and($updated->region_id)->toBe($newRegion->id);

    $this->assertDatabaseHas('address_user', [
        'user_id' => $user->id,
        'address_id' => $address->id,
        'is_primary' => true,
    ]);
});
