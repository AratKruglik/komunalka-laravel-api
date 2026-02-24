<?php

declare(strict_types=1);

use Modules\Address\Actions\CreateAddress;
use Modules\Address\Actions\DeleteAddress;
use Modules\Address\DTOs\CreateAddressData;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

it('soft deletes address and removes pivot', function () {
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

    app(DeleteAddress::class)->handle($user->id, $address);

    $this->assertSoftDeleted('addresses', ['id' => $address->id]);
    $this->assertDatabaseMissing('address_user', [
        'user_id' => $user->id,
        'address_id' => $address->id,
    ]);
});
