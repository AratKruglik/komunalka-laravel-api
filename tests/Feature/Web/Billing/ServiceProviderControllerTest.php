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
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('ServiceProviderController', function (): void {
    it('lists providers', function (): void {
        ServiceProvider::factory()->count(2)->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Providers/Index')
                ->has('providers.data', 2),
            );
    });

    it('renders edit page with provider and tariffs', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $currency = Currency::factory()->create();
        Tariff::factory()->create([
            'service_provider_id' => $provider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.edit', $provider->getKey()))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Providers/Edit')
                ->has('provider'),
            );
    });

    it('creates provider', function (): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Київенерго',
            'description' => 'Постачальник електроенергії',
            'phone' => '+380441234567',
            'email' => 'info@kyivenergo.ua',
            'website' => 'https://kyivenergo.ua',
            'is_active' => true,
        ];

        $this->actingAs($this->user)
            ->post(route('providers.store'), $payload)
            ->assertRedirect(route('providers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('service_providers', ['name' => 'Київенерго']);
    });

    it('creates provider with nested tariffs', function (): void {
        $currency = Currency::factory()->create();

        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Газпостач',
            'tariffs' => [
                [
                    'utility_type_id' => $this->utilityType->getKey(),
                    'currency_id' => $currency->getKey(),
                    'name' => 'Базовий тариф',
                    'base_rate' => 7.99,
                    'service_fee' => 50.00,
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2026-12-31',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('providers.store'), $payload)
            ->assertRedirect(route('providers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('service_providers', ['name' => 'Газпостач']);
        $this->assertDatabaseHas('tariffs', ['name' => 'Базовий тариф']);
    });

    it('validates required fields on create', function (string $field): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Test Provider',
        ];
        unset($payload[$field]);

        $this->actingAs($this->user)
            ->post(route('providers.store'), $payload)
            ->assertSessionHasErrors($field);
    })->with(['address_id', 'utility_type_id', 'name']);

    it('validates email and website formats', function (): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Test Provider',
            'email' => 'not-an-email',
            'website' => 'not-a-url',
        ];

        $this->actingAs($this->user)
            ->post(route('providers.store'), $payload)
            ->assertSessionHasErrors(['email', 'website']);
    });

    it('updates provider', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $newUtilityType = UtilityType::factory()->create();
        $payload = [
            'name' => 'Оновлена назва',
            'description' => 'Оновлений опис',
            'phone' => '+380501234567',
            'email' => 'new@provider.ua',
            'website' => 'https://new-provider.ua',
            'is_active' => false,
            'utility_type_id' => $newUtilityType->getKey(),
        ];

        $this->actingAs($this->user)
            ->put(route('providers.update', $provider->getKey()), $payload)
            ->assertRedirect(route('providers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('service_providers', [
            'id' => $provider->getKey(),
            'name' => 'Оновлена назва',
        ]);
    });

    it('deletes provider and cascades tariffs', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $tariff = Tariff::factory()->create([
            'service_provider_id' => $provider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('providers.destroy', $provider->getKey()))
            ->assertRedirect(route('providers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('service_providers', ['id' => $provider->getKey()]);
        $this->assertDatabaseMissing('tariffs', ['id' => $tariff->getKey()]);
    });

    it('requires authentication', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))->assertRedirect(route('login'));
    })->with([
        ['get', 'providers.index', []],
        ['get', 'providers.edit', [1]],
        ['post', 'providers.store', []],
        ['put', 'providers.update', [1]],
        ['delete', 'providers.destroy', [1]],
    ]);

    it('prevents access to other users providers', function (): void {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->getKey(), ['is_primary' => false]);

        $provider = ServiceProvider::factory()->create([
            'address_id' => $otherAddress->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.edit', $provider->getKey()))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->put(route('providers.update', $provider->getKey()), [
                'name' => 'Test',
                'utility_type_id' => $this->utilityType->getKey(),
            ])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('providers.destroy', $provider->getKey()))
            ->assertNotFound();
    });

    it('prevents creating provider for non-owned address', function (): void {
        $otherAddress = Address::factory()->create();

        $payload = [
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Test Provider',
        ];

        $this->actingAs($this->user)
            ->post(route('providers.store'), $payload)
            ->assertNotFound();
    });
});
