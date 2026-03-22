<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create([
        'display_name' => 'Електроенергія',
        'unit' => 'кВт·год',
    ]);
    $this->currency = Currency::factory()->create([
        'code' => 'UAH',
        'name' => 'Гривня',
        'symbol' => '₴',
    ]);
});

describe('Providers Index Page', function (): void {
    it('renders provider list with data', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Київенерго',
        ]);

        Tariff::factory()->create([
            'service_provider_id' => $provider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $this->currency->getKey(),
            'name' => 'Денний тариф',
            'base_rate' => 2.64,
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Providers/Index')
                ->has('providers.data', 1)
                ->where('providers.data.0.name', 'Київенерго')
                ->has('providers.data.0.tariffs', 1)
                ->where('providers.data.0.tariffs.0.name', 'Денний тариф'),
            );
    });

    it('renders empty state when no providers exist', function (): void {
        $this->actingAs($this->user)
            ->get(route('providers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Providers/Index')
                ->has('providers.data', 0),
            );
    });

    it('does not show providers belonging to other users', function (): void {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->getKey(), ['is_primary' => true]);

        ServiceProvider::factory()->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Чужий провайдер',
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Providers/Index')
                ->has('providers.data', 0),
            );
    });

    it('redirects unauthenticated user to login', function (): void {
        $this->get(route('providers.index'))
            ->assertRedirect(route('login'));
    });
});

describe('Providers Create Page', function (): void {
    it('renders form with addresses, utility types, and currencies', function (): void {
        $this->actingAs($this->user)
            ->get(route('providers.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Providers/Create')
                ->has('addresses.data', 1)
                ->has('utilityTypes.data', fn (Assert $items) => $items
                    ->each(fn (Assert $item) => $item
                        ->has('id')
                        ->has('display_name')
                        ->has('unit')
                        ->etc(),
                    ),
                )
                ->has('currencies.data', fn (Assert $items) => $items
                    ->each(fn (Assert $item) => $item
                        ->has('id')
                        ->has('code')
                        ->has('symbol')
                        ->etc(),
                    ),
                ),
            );
    });

    it('submits successfully and redirects to index', function (): void {
        $this->actingAs($this->user)
            ->post(route('providers.store'), [
                'address_id' => $this->address->getKey(),
                'utility_type_id' => $this->utilityType->getKey(),
                'name' => 'Київводоканал',
                'description' => 'Водопостачання Києва',
                'phone' => '+380441234567',
                'email' => 'info@vodokanal.kyiv.ua',
                'website' => 'https://vodokanal.kyiv.ua',
                'tariffs' => [
                    [
                        'utility_type_id' => $this->utilityType->getKey(),
                        'currency_id' => $this->currency->getKey(),
                        'name' => 'Базовий тариф',
                        'base_rate' => 12.45,
                        'service_fee' => 50.00,
                        'effective_from' => '2026-01-01',
                    ],
                ],
            ])
            ->assertRedirect(route('providers.index'))
            ->assertSessionHas('success', 'Провайдера створено');

        $this->assertDatabaseHas('service_providers', [
            'name' => 'Київводоканал',
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->assertDatabaseHas('tariffs', [
            'name' => 'Базовий тариф',
            'base_rate' => 12.45,
        ]);
    });

    it('submits with multiple tariff rows', function (): void {
        $this->actingAs($this->user)
            ->post(route('providers.store'), [
                'address_id' => $this->address->getKey(),
                'utility_type_id' => $this->utilityType->getKey(),
                'name' => 'ДТЕК',
                'tariffs' => [
                    [
                        'utility_type_id' => $this->utilityType->getKey(),
                        'currency_id' => $this->currency->getKey(),
                        'name' => 'Денний',
                        'base_rate' => 2.64,
                        'service_fee' => 0,
                        'effective_from' => '2026-01-01',
                    ],
                    [
                        'utility_type_id' => $this->utilityType->getKey(),
                        'currency_id' => $this->currency->getKey(),
                        'name' => 'Нічний',
                        'base_rate' => 1.32,
                        'service_fee' => 0,
                        'effective_from' => '2026-01-01',
                    ],
                ],
            ])
            ->assertRedirect(route('providers.index'));

        $provider = ServiceProvider::query()
            ->where('name', 'ДТЕК')
            ->first();

        expect($provider)->not->toBeNull();
        expect($provider->tariffs()->count())->toBe(2);
    });

    it('shows validation errors for missing required fields', function (): void {
        $this->actingAs($this->user)
            ->post(route('providers.store'), [])
            ->assertSessionHasErrors(['address_id', 'utility_type_id', 'name']);
    });

    it('shows validation errors for invalid email and website', function (): void {
        $this->actingAs($this->user)
            ->post(route('providers.store'), [
                'address_id' => $this->address->getKey(),
                'utility_type_id' => $this->utilityType->getKey(),
                'name' => 'Test',
                'email' => 'not-an-email',
                'website' => 'not-a-url',
            ])
            ->assertSessionHasErrors(['email', 'website']);
    });

    it('creates provider with zero tariffs', function (): void {
        $this->actingAs($this->user)
            ->post(route('providers.store'), [
                'address_id' => $this->address->getKey(),
                'utility_type_id' => $this->utilityType->getKey(),
                'name' => 'Провайдер без тарифів',
                'tariffs' => [],
            ])
            ->assertRedirect(route('providers.index'));

        $this->assertDatabaseHas('service_providers', [
            'name' => 'Провайдер без тарифів',
        ]);
    });

    it('creates provider with very long name at max length', function (): void {
        $longName = str_repeat('А', 255);

        $this->actingAs($this->user)
            ->post(route('providers.store'), [
                'address_id' => $this->address->getKey(),
                'utility_type_id' => $this->utilityType->getKey(),
                'name' => $longName,
            ])
            ->assertRedirect(route('providers.index'));

        $this->assertDatabaseHas('service_providers', ['name' => $longName]);
    });

    it('rejects name exceeding max length', function (): void {
        $tooLongName = str_repeat('А', 256);

        $this->actingAs($this->user)
            ->post(route('providers.store'), [
                'address_id' => $this->address->getKey(),
                'utility_type_id' => $this->utilityType->getKey(),
                'name' => $tooLongName,
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('creates provider with tariff having zero base rate', function (): void {
        $this->actingAs($this->user)
            ->post(route('providers.store'), [
                'address_id' => $this->address->getKey(),
                'utility_type_id' => $this->utilityType->getKey(),
                'name' => 'Безкоштовний провайдер',
                'tariffs' => [
                    [
                        'utility_type_id' => $this->utilityType->getKey(),
                        'currency_id' => $this->currency->getKey(),
                        'name' => 'Нульовий тариф',
                        'base_rate' => 0,
                        'service_fee' => 0,
                        'effective_from' => '2026-01-01',
                    ],
                ],
            ])
            ->assertRedirect(route('providers.index'));

        $this->assertDatabaseHas('tariffs', [
            'name' => 'Нульовий тариф',
            'base_rate' => 0,
        ]);
    });

    it('redirects unauthenticated user to login', function (): void {
        $this->get(route('providers.create'))
            ->assertRedirect(route('login'));
    });
});

describe('Providers Edit Page', function (): void {
    it('renders pre-filled form with provider data', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Київенерго',
            'description' => 'Опис провайдера',
            'phone' => '+380441111111',
            'email' => 'test@provider.ua',
            'website' => 'https://provider.ua',
        ]);

        Tariff::factory()->create([
            'service_provider_id' => $provider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $this->currency->getKey(),
            'name' => 'Базовий',
            'base_rate' => 7.99,
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.edit', $provider->getKey()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Providers/Edit')
                ->where('provider.data.name', 'Київенерго')
                ->where('provider.data.description', 'Опис провайдера')
                ->where('provider.data.phone', '+380441111111')
                ->where('provider.data.email', 'test@provider.ua')
                ->where('provider.data.website', 'https://provider.ua')
                ->has('provider.data.tariffs', 1)
                ->where('provider.data.tariffs.0.name', 'Базовий')
                ->has('addresses.data', 1)
                ->has('utilityTypes.data')
                ->has('currencies.data'),
            );
    });

    it('updates provider and redirects with flash message', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Стара назва',
        ]);

        $newUtilityType = UtilityType::factory()->create();

        $this->actingAs($this->user)
            ->put(route('providers.update', $provider->getKey()), [
                'utility_type_id' => $newUtilityType->getKey(),
                'name' => 'Нова назва',
                'description' => 'Оновлений опис',
                'phone' => '+380509876543',
                'email' => 'updated@provider.ua',
                'website' => 'https://updated-provider.ua',
            ])
            ->assertRedirect(route('providers.index'))
            ->assertSessionHas('success', 'Провайдера оновлено');

        $this->assertDatabaseHas('service_providers', [
            'id' => $provider->getKey(),
            'name' => 'Нова назва',
            'utility_type_id' => $newUtilityType->getKey(),
        ]);
    });

    it('shows validation errors on update with invalid data', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->put(route('providers.update', $provider->getKey()), [
                'name' => '',
                'utility_type_id' => '',
            ])
            ->assertSessionHasErrors(['name', 'utility_type_id']);
    });

    it('deletes provider and redirects with flash message', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Провайдер для видалення',
        ]);

        $this->actingAs($this->user)
            ->delete(route('providers.destroy', $provider->getKey()))
            ->assertRedirect(route('providers.index'))
            ->assertSessionHas('success', 'Провайдера видалено');

        $this->assertDatabaseMissing('service_providers', [
            'id' => $provider->getKey(),
        ]);
    });

    it('deletes provider that has associated meters', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'service_provider_id' => $provider->getKey(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('providers.destroy', $provider->getKey()))
            ->assertRedirect(route('providers.index'));

        $this->assertDatabaseMissing('service_providers', [
            'id' => $provider->getKey(),
        ]);

        $meter->refresh();
        expect($meter->service_provider_id)->toBeNull();
    });

    it('returns 404 for provider belonging to another user', function (): void {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $otherUser->addresses()->attach($otherAddress->getKey(), ['is_primary' => true]);

        $provider = ServiceProvider::factory()->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.edit', $provider->getKey()))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->put(route('providers.update', $provider->getKey()), [
                'name' => 'Hacked',
                'utility_type_id' => $this->utilityType->getKey(),
            ])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('providers.destroy', $provider->getKey()))
            ->assertNotFound();
    });

    it('returns valid inertia page with correct prop types', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        Tariff::factory()->create([
            'service_provider_id' => $provider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $this->currency->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('providers.edit', $provider->getKey()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Providers/Edit')
                ->has('provider.data', fn (Assert $data) => $data
                    ->whereType('id', 'integer')
                    ->whereType('name', 'string')
                    ->whereType('is_active', 'boolean')
                    ->whereType('address_id', 'integer')
                    ->has('utility_type')
                    ->has('tariffs')
                    ->etc(),
                ),
            );
    });

    it('redirects unauthenticated user to login', function (): void {
        $provider = ServiceProvider::factory()->create();

        $this->get(route('providers.edit', $provider->getKey()))
            ->assertRedirect(route('login'));
    });
});
