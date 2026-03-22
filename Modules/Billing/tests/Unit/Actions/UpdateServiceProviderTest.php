<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Actions\UpdateServiceProvider;
use Modules\Billing\DTO\UpdateServiceProviderData;
use Modules\Billing\Models\ServiceProvider;
use Modules\Shared\Models\UtilityType;

it('updates a service provider', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->getKey(), ['is_primary' => true]);
    $utilityType = UtilityType::factory()->create();

    $provider = ServiceProvider::factory()->create([
        'address_id' => $address->getKey(),
        'utility_type_id' => $utilityType->getKey(),
        'name' => 'Стара назва',
    ]);

    $newUtilityType = UtilityType::factory()->create();

    $data = new UpdateServiceProviderData(
        name: 'Нова назва',
        description: 'Оновлений опис',
        phone: '+380501234567',
        email: 'new@email.ua',
        website: 'https://new.ua',
        isActive: false,
        utilityTypeId: $newUtilityType->getKey(),
    );

    $updated = app(UpdateServiceProvider::class)->handle($provider, $data);

    expect($updated->name)->toBe('Нова назва')
        ->and($updated->is_active)->toBeFalse()
        ->and($updated->utility_type_id)->toBe($newUtilityType->getKey());
});
