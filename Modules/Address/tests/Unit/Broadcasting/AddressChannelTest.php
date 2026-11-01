<?php

declare(strict_types=1);

use Modules\Address\Broadcasting\AddressChannel;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;

mutates(AddressChannel::class);

it('authorizes only users linked to the address', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->getKey(), ['is_primary' => true]);
    $channel = app(AddressChannel::class);

    expect($channel->join($user, (string) $address->getKey()))->toBeTrue()
        ->and($channel->join(User::factory()->create(), (string) $address->getKey()))->toBeFalse()
        ->and($channel->join($user, 'abc'))->toBeFalse();
});
