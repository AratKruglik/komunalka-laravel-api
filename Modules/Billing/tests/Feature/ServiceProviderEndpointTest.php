<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->baseUrl = '/api/v1/service-providers';
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('GET /service-providers (index)', function () {
    it('returns service providers for authenticated user addresses', function () {
        $providers = ServiceProvider::factory()->count(2)->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'is_active', 'address_id', 'utility_type']]]);
    });

    it('does not return providers of other users addresses', function () {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->id, ['is_primary' => false]);

        ServiceProvider::factory()->create([
            'address_id' => $otherAddress->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns 401 for unauthenticated request', function () {
        $this->getJson($this->baseUrl)->assertUnauthorized();
    });
});

describe('GET /service-providers/{id} (show)', function () {
    it('returns service provider with tariffs', function () {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $currency = Currency::factory()->create();
        Tariff::factory()->create([
            'service_provider_id' => $provider->id,
            'utility_type_id' => $this->utilityType->id,
            'currency_id' => $currency->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/{$provider->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $provider->id)
            ->assertJsonPath('data.utility_type.id', $this->utilityType->id)
            ->assertJsonCount(1, 'data.tariffs');
    });

    it('returns 404 for provider on another users address', function () {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->id, ['is_primary' => false]);

        $provider = ServiceProvider::factory()->create([
            'address_id' => $otherAddress->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/{$provider->id}")
            ->assertNotFound();
    });

    it('returns 404 for non-existent provider', function () {
        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/999")
            ->assertNotFound();
    });
});

describe('GET /service-providers/address/{addressId} (byAddress)', function () {
    it('returns providers for a specific address', function () {
        ServiceProvider::factory()->count(2)->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/address/{$this->address->id}")
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('returns 404 for address not owned by user', function () {
        $otherAddress = Address::factory()->create();

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/address/{$otherAddress->id}")
            ->assertNotFound();
    });
});

describe('POST /service-providers (store)', function () {
    it('creates a service provider', function () {
        $payload = [
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'name' => 'Київенерго',
            'description' => 'Постачальник електроенергії',
            'phone' => '+380441234567',
            'email' => 'info@kyivenergo.ua',
            'website' => 'https://kyivenergo.ua',
            'is_active' => true,
        ];

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Київенерго')
            ->assertJsonPath('data.address_id', $this->address->id)
            ->assertJsonPath('data.utility_type.id', $this->utilityType->id);

        $this->assertDatabaseHas('service_providers', ['name' => 'Київенерго']);
    });

    it('creates a service provider with nested tariffs', function () {
        $currency = Currency::factory()->create();

        $payload = [
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'name' => 'Газпостач',
            'tariffs' => [
                [
                    'utility_type_id' => $this->utilityType->id,
                    'currency_id' => $currency->id,
                    'name' => 'Базовий тариф',
                    'base_rate' => 7.99,
                    'service_fee' => 50.00,
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2026-12-31',
                ],
            ],
        ];

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Газпостач')
            ->assertJsonCount(1, 'data.tariffs');

        $this->assertDatabaseHas('tariffs', ['name' => 'Базовий тариф', 'base_rate' => '7.9900']);
    });

    it('validates required fields', function (string $field) {
        $payload = [
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'name' => 'Test Provider',
        ];
        unset($payload[$field]);

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['address_id', 'utility_type_id', 'name']);

    it('returns Ukrainian validation messages', function () {
        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('name');
    });

    it('validates address_id exists', function () {
        $payload = [
            'address_id' => 9999,
            'utility_type_id' => $this->utilityType->id,
            'name' => 'Test Provider',
        ];

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');
    });

    it('validates tariff date ranges', function () {
        $currency = Currency::factory()->create();

        $payload = [
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'name' => 'Test Provider',
            'tariffs' => [
                [
                    'utility_type_id' => $this->utilityType->id,
                    'currency_id' => $currency->id,
                    'name' => 'Invalid Tariff',
                    'base_rate' => 5.00,
                    'effective_from' => '2026-12-31',
                    'effective_to' => '2026-01-01',
                ],
            ],
        ];

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tariffs.0.effective_to');
    });

    it('returns 404 when user does not own address', function () {
        $otherAddress = Address::factory()->create();

        $payload = [
            'address_id' => $otherAddress->id,
            'utility_type_id' => $this->utilityType->id,
            'name' => 'Test Provider',
        ];

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertNotFound();
    });
});

describe('PUT /service-providers/{id} (update)', function () {
    it('updates a service provider', function () {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $newUtilityType = UtilityType::factory()->create();
        $payload = [
            'name' => 'Оновлена назва',
            'description' => 'Оновлений опис',
            'phone' => '+380501234567',
            'email' => 'new@provider.ua',
            'website' => 'https://new-provider.ua',
            'is_active' => false,
            'utility_type_id' => $newUtilityType->id,
        ];

        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$provider->id}", $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Оновлена назва')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.utility_type.id', $newUtilityType->id);

        $this->assertDatabaseHas('service_providers', ['id' => $provider->id, 'name' => 'Оновлена назва']);
    });

    it('returns 404 for provider on another users address', function () {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->id, ['is_primary' => false]);

        $provider = ServiceProvider::factory()->create([
            'address_id' => $otherAddress->id,
        ]);

        $payload = [
            'name' => 'Test',
            'utility_type_id' => $this->utilityType->id,
        ];

        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$provider->id}", $payload)
            ->assertNotFound();
    });
});

describe('DELETE /service-providers/{id} (destroy)', function () {
    it('deletes service provider and cascades tariffs', function () {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $tariff = Tariff::factory()->create([
            'service_provider_id' => $provider->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson("{$this->baseUrl}/{$provider->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('service_providers', ['id' => $provider->id]);
        $this->assertDatabaseMissing('tariffs', ['id' => $tariff->id]);
    });

    it('returns 404 for provider on another users address', function () {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->id, ['is_primary' => false]);

        $provider = ServiceProvider::factory()->create([
            'address_id' => $otherAddress->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson("{$this->baseUrl}/{$provider->id}")
            ->assertNotFound();
    });
});
