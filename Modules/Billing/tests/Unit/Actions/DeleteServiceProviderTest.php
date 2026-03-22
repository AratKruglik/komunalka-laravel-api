<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Actions\DeleteServiceProvider;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Models\UtilityType;

it('deletes a service provider and cascades tariffs', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->getKey(), ['is_primary' => true]);
    $utilityType = UtilityType::factory()->create();

    $provider = ServiceProvider::factory()->create([
        'address_id' => $address->getKey(),
        'utility_type_id' => $utilityType->getKey(),
    ]);

    $tariff = Tariff::factory()->create([
        'service_provider_id' => $provider->getKey(),
        'utility_type_id' => $utilityType->getKey(),
    ]);

    app(DeleteServiceProvider::class)->handle($provider);

    $this->assertDatabaseMissing('service_providers', ['id' => $provider->getKey()]);
    $this->assertDatabaseMissing('tariffs', ['id' => $tariff->getKey()]);
});
