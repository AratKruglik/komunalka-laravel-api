<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('Meters Index Page', function (): void {
    it('renders meter list', function (): void {
        $meters = Meter::factory()->count(3)->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 3)
                ->has('addresses.data')
                ->where('selectedAddressId', null),
            );
    });

    it('shows empty state when no meters exist', function (): void {
        $this->actingAs($this->user)
            ->get(route('meters.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 0)
                ->has('addresses.data', 1),
            );
    });

    it('renders address filter dropdown with user addresses', function (): void {
        $secondAddress = Address::factory()->create();
        $this->user->addresses()->attach($secondAddress->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user)
            ->get(route('meters.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Index')
                ->has('addresses.data', 2),
            );
    });

    it('filters meters by selected address', function (): void {
        Meter::factory()->count(2)->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $otherAddress = Address::factory()->create();
        $this->user->addresses()->attach($otherAddress->getKey(), ['is_primary' => false]);
        Meter::factory()->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 2)
                ->where('selectedAddressId', $this->address->getKey()),
            );
    });

    it('shows empty state when filtering by address with no meters', function (): void {
        $emptyAddress = Address::factory()->create();
        $this->user->addresses()->attach($emptyAddress->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user)
            ->get(route('meters.index', ['address_id' => $emptyAddress->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 0)
                ->where('selectedAddressId', $emptyAddress->getKey()),
            );
    });

    it('displays active and inactive meter status', function (): void {
        Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'is_active' => true,
            'name' => 'Active Meter',
        ]);

        Meter::factory()->inactive()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Inactive Meter',
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 2)
                ->where('meters.data', fn ($meters) => collect($meters)->contains('attributes.is_active', true)
                    && collect($meters)->contains('attributes.is_active', false)),
            );
    });

    it('displays meter with long serial number', function (): void {
        $longSerial = str_repeat('A', 255);
        Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'serial_number' => $longSerial,
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Index')
                ->has('meters.data', 1)
                ->where('meters.data.0.attributes.serial_number', $longSerial),
            );
    });
});

describe('Meters Create Page', function (): void {
    it('renders form with address, utility type, and provider dropdowns', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Create')
                ->has('addresses.data', 1)
                ->has('utilityTypes.data')
                ->has('serviceProviders.data', 1)
                ->where('addresses.data.0.id', (string) $this->address->getKey())
                ->where('serviceProviders.data.0.id', (string) $provider->getKey()),
            );
    });

    it('successfully creates meter and redirects to index', function (): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'serial_number' => 'M-123456',
            'name' => 'Лічильник газу',
            'initial_reading' => 0,
        ];

        $this->actingAs($this->user)
            ->post(route('meters.store'), $payload)
            ->assertRedirect(route('meters.index'))
            ->assertSessionHas('success', 'Лічильник створено');

        $this->assertDatabaseHas('meters', [
            'name' => 'Лічильник газу',
            'serial_number' => 'M-123456',
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);
    });

    it('creates meter without optional fields', function (): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'serial_number' => 'M-MINIMAL',
            'name' => 'Мінімальний лічильник',
            'initial_reading' => 0,
        ];

        $this->actingAs($this->user)
            ->post(route('meters.store'), $payload)
            ->assertRedirect(route('meters.index'));

        $this->assertDatabaseHas('meters', [
            'serial_number' => 'M-MINIMAL',
            'description' => null,
            'model_name' => null,
            'location' => null,
            'installation_date' => null,
            'notes' => null,
            'service_provider_id' => null,
        ]);
    });

    it('shows validation errors for missing required fields', function (string $field): void {
        $payload = [
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'serial_number' => 'M-123456',
            'name' => 'Test Meter',
            'initial_reading' => 0,
        ];
        unset($payload[$field]);

        $this->actingAs($this->user)
            ->post(route('meters.store'), $payload)
            ->assertSessionHasErrors($field);

        $this->assertDatabaseMissing('meters', ['serial_number' => 'M-123456']);
    })->with(['address_id', 'utility_type_id', 'serial_number', 'name', 'initial_reading']);
});

describe('Meters Edit Page', function (): void {
    it('renders pre-filled form with meter data', function (): void {
        $provider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'service_provider_id' => $provider->getKey(),
            'name' => 'Лічильник води',
            'serial_number' => 'W-555',
            'location' => 'Ванна кімната',
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.edit', $meter->getKey()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meters/Edit')
                ->where('meter.data.id', (string) $meter->getKey())
                ->where('meter.data.attributes.name', 'Лічильник води')
                ->where('meter.data.attributes.serial_number', 'W-555')
                ->where('meter.data.attributes.location', 'Ванна кімната')
                ->where('meter.data.attributes.address_id', $this->address->getKey())
                ->has('addresses.data')
                ->has('utilityTypes.data')
                ->has('serviceProviders.data'),
            );
    });

    it('updates meter and redirects with flash message', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Старий лічильник',
        ]);

        $this->actingAs($this->user)
            ->put(route('meters.update', $meter->getKey()), [
                'name' => 'Оновлений лічильник',
                'serial_number' => 'U-999',
            ])
            ->assertRedirect(route('meters.index'))
            ->assertSessionHas('success', 'Лічильник оновлено');

        $this->assertDatabaseHas('meters', [
            'id' => $meter->getKey(),
            'name' => 'Оновлений лічильник',
            'serial_number' => 'U-999',
        ]);
    });
});

describe('Meters Delete', function (): void {
    it('deletes meter and redirects with flash message', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('meters.destroy', $meter->getKey()))
            ->assertRedirect(route('meters.index'))
            ->assertSessionHas('success', 'Лічильник видалено');

        $this->assertDatabaseMissing('meters', ['id' => $meter->getKey()]);
    });
});

describe('Meters Authentication', function (): void {
    it('redirects unauthenticated users to login', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))
            ->assertRedirect(route('login'));
    })->with([
        ['get', 'meters.index', []],
        ['get', 'meters.create', []],
        ['post', 'meters.store', []],
        ['get', 'meters.edit', [1]],
        ['put', 'meters.update', [1]],
        ['delete', 'meters.destroy', [1]],
    ]);
});
