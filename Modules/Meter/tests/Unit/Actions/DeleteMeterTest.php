<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\DeleteMeter;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('deletes a meter', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->id, ['is_primary' => true]);
    $utilityType = UtilityType::factory()->create();

    $meter = Meter::factory()->create([
        'address_id' => $address->id,
        'utility_type_id' => $utilityType->id,
    ]);

    app(DeleteMeter::class)->handle($user->id, $meter->id);

    $this->assertDatabaseMissing('meters', ['id' => $meter->id]);
});

it('aborts for non-owned meter', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->id, ['is_primary' => true]);

    $otherAddress = Address::factory()->create();
    $meter = Meter::factory()->create(['address_id' => $otherAddress->id]);

    app(DeleteMeter::class)->handle($user->id, $meter->id);
})->throws(HttpException::class);
