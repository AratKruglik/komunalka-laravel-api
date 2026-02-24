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
    $user->addresses()->attach($address->id, ['is_primary' => true]);
    $utilityType = UtilityType::factory()->create();

    $provider = ServiceProvider::factory()->create([
        'address_id' => $address->id,
        'utility_type_id' => $utilityType->id,
    ]);

    $tariff = Tariff::factory()->create([
        'service_provider_id' => $provider->id,
        'utility_type_id' => $utilityType->id,
    ]);

    app(DeleteServiceProvider::class)->handle($user->id, $provider);

    $this->assertDatabaseMissing('service_providers', ['id' => $provider->id]);
    $this->assertDatabaseMissing('tariffs', ['id' => $tariff->id]);
});
