<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    Model::preventLazyLoading();
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

afterEach(function () {
    Model::preventLazyLoading(false);
});

describe('N+1 query prevention', function () {
    it('loads GET /api/v1/meter without lazy loading', function () {
        Meter::factory()->count(5)->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.index'))
            ->assertSuccessful();
    });

    it('loads GET /api/v1/meter/{id} without lazy loading', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.show', $meter->getKey()))
            ->assertSuccessful();
    });

    it('loads GET /api/v1/address without lazy loading', function () {
        $addresses = Address::factory()->count(5)->create();
        foreach ($addresses as $address) {
            $this->user->addresses()->attach($address->id, ['is_primary' => false]);
        }

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index'))
            ->assertSuccessful();
    });

    it('loads GET /api/v1/service-providers without lazy loading', function () {
        ServiceProvider::factory()->count(3)->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.service-providers.index'))
            ->assertSuccessful();
    });

    it('loads GET /api/v1/service-providers/{id} without lazy loading', function () {
        $currency = Currency::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        Tariff::factory()->count(2)->create([
            'service_provider_id' => $serviceProvider->id,
            'utility_type_id' => $this->utilityType->id,
            'currency_id' => $currency->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.service-providers.show', $serviceProvider->getKey()))
            ->assertSuccessful();
    });

    it('loads GET /api/v1/meter-readings/address/{id} without lazy loading', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        MeterReading::factory()->count(5)->create([
            'meter_id' => $meter->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter-readings.by-address', ['addressId' => $this->address->id]))
            ->assertSuccessful();
    });

    it('loads GET /api/v1/users without lazy loading', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $this->actingAs($admin, 'api')
            ->getJson(route('api.users.index'))
            ->assertSuccessful();
    });
});
