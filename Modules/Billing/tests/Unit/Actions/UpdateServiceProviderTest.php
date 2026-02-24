<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Actions\UpdateServiceProvider;
use Modules\Billing\DTOs\UpdateServiceProviderData;
use Modules\Billing\Models\ServiceProvider;
use Modules\Shared\Models\UtilityType;

it('updates a service provider', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->id, ['is_primary' => true]);
    $utilityType = UtilityType::factory()->create();

    $provider = ServiceProvider::factory()->create([
        'address_id' => $address->id,
        'utility_type_id' => $utilityType->id,
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
        utilityTypeId: $newUtilityType->id,
    );

    $updated = app(UpdateServiceProvider::class)->handle($user->id, $provider, $data);

    expect($updated->name)->toBe('Нова назва')
        ->and($updated->is_active)->toBeFalse()
        ->and($updated->utility_type_id)->toBe($newUtilityType->id);
});
