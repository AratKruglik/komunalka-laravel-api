<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create();
});

describe('Reading create route', function (): void {
    it('returns 200 for GET /readings/create when authenticated', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Readings/Create'));
    });

    it('returns 405 for GET /readings/new (old URL is not registered)', function (): void {
        $this->actingAs($this->user)
            ->get('/readings/new')
            ->assertStatus(405);
    });
});

describe('Reading create page — serviceProviders JSON:API structure', function (): void {
    beforeEach(function (): void {
        $this->address = Address::factory()->create();
        $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);

        $this->providerUtilityType = UtilityType::factory()->create([
            'slug' => 'gas',
            'display_name' => 'Газ',
            'unit' => 'м³',
        ]);
        $this->tariffUtilityType = UtilityType::factory()->create([
            'slug' => 'electricity',
            'display_name' => 'Електроенергія',
            'unit' => 'кВт·год',
        ]);
        $this->currency = Currency::factory()->create();

        $this->provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->providerUtilityType->getKey(),
        ]);

        $this->tariff = Tariff::factory()->create([
            'service_provider_id' => $this->provider->getKey(),
            'utility_type_id' => $this->tariffUtilityType->getKey(),
            'currency_id' => $this->currency->getKey(),
        ]);
    });

    it('includes tariff utilityType relationship in serviceProviders JSON:API response', function (): void {
        $tariffId = (string) $this->tariff->getKey();
        $tariffUtilityTypeId = (string) $this->tariffUtilityType->getKey();

        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('serviceProviders.data', 1)
                ->has('serviceProviders.included')
                ->where(
                    'serviceProviders.included',
                    fn (mixed $included): bool => collect($included)
                        ->where('type', 'tariffs')
                        ->where('id', $tariffId)
                        ->filter(fn (mixed $item): bool => is_array($item)
                            && isset($item['relationships']['utilityType']['data']['id'])
                            && $item['relationships']['utilityType']['data']['id'] === $tariffUtilityTypeId)
                        ->isNotEmpty(),
                ),
            );
    });

    it('includes tariff utilityType resource in JSON:API included array', function (): void {
        $tariffUtilityTypeId = (string) $this->tariffUtilityType->getKey();

        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->where(
                    'serviceProviders.included',
                    fn (mixed $included): bool => collect($included)
                        ->where('type', 'utility_types')
                        ->where('id', $tariffUtilityTypeId)
                        ->isNotEmpty(),
                ),
            );
    });

    it('tariff utilityType differs from provider utilityType (discriminates the fix)', function (): void {
        $providerUtilityTypeId = (string) $this->providerUtilityType->getKey();
        $tariffUtilityTypeId = (string) $this->tariffUtilityType->getKey();

        expect($providerUtilityTypeId)->not->toBe($tariffUtilityTypeId);

        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->where(
                    'serviceProviders.included',
                    fn (mixed $included): bool => collect($included)
                        ->where('type', 'tariffs')
                        ->filter(fn (mixed $item): bool => is_array($item)
                            && isset($item['relationships']['utilityType']['data']['id'])
                            && $item['relationships']['utilityType']['data']['id'] === $tariffUtilityTypeId)
                        ->isNotEmpty(),
                ),
            );
    });
});

describe('Reading create page — addresses as AddressResource collection', function (): void {
    beforeEach(function (): void {
        $this->address = Address::factory()->create([
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '1',
        ]);
        $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    });

    it('returns addresses wrapped in JSON:API resource collection structure', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('addresses.data', 1)
            );
    });

    it('addresses.data items contain JSON:API attributes with city and street', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('addresses.data.0.attributes')
                ->where('addresses.data.0.attributes.city', 'Київ')
                ->where('addresses.data.0.attributes.street', 'Хрещатик')
                ->where('addresses.data.0.attributes.building_number', '1')
            );
    });

    it('returns empty addresses.data when user has no addresses', function (): void {
        $userWithoutAddresses = User::factory()->create();

        $this->actingAs($userWithoutAddresses)
            ->get(route('readings.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('addresses.data', 0)
            );
    });
});

describe('Reading index page 2 — addresses as AddressResource collection', function (): void {
    beforeEach(function (): void {
        $this->address = Address::factory()->create([
            'city' => 'Львів',
            'street' => 'Проспект Свободи',
            'building_number' => '5',
        ]);
        $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    });

    it('returns addresses wrapped in JSON:API resource collection structure', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('addresses.data', 1)
            );
    });

    it('addresses.data items contain JSON:API attributes with city and street', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('addresses.data.0.attributes')
                ->where('addresses.data.0.attributes.city', 'Львів')
                ->where('addresses.data.0.attributes.street', 'Проспект Свободи')
                ->where('addresses.data.0.attributes.building_number', '5')
            );
    });

    it('returns empty addresses.data when user has no addresses', function (): void {
        $userWithoutAddresses = User::factory()->create();

        $this->actingAs($userWithoutAddresses)
            ->get(route('readings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('addresses.data', 0)
            );
    });
});
