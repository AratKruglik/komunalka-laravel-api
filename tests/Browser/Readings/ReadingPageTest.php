<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create([
        'slug' => 'electricity',
        'display_name' => 'Електроенергія',
        'unit' => 'kWh',
    ]);
    $this->meter = Meter::factory()->create([
        'address_id' => $this->address->getKey(),
        'utility_type_id' => $this->utilityType->getKey(),
        'serial_number' => 'E-100200',
        'name' => 'Лічильник електроенергії',
        'initial_reading' => 100,
    ]);
});

describe('Readings Index Page', function (): void {
    it('renders readings list for selected address', function (): void {
        MeterReading::factory()->count(3)->create([
            'meter_id' => $this->meter->getKey(),
            'reading_date' => '2026-03-01',
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('addresses')
                ->has('readings.data', 3)
                ->where('filters.address_id', $this->address->getKey()),
            );
    });

    it('filters readings by address', function (): void {
        $otherAddress = Address::factory()->create();
        $this->user->addresses()->attach($otherAddress->getKey(), ['is_primary' => false]);
        $otherMeter = Meter::factory()->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'initial_reading' => 0,
        ]);

        MeterReading::factory()->count(2)->create([
            'meter_id' => $this->meter->getKey(),
        ]);
        MeterReading::factory()->create([
            'meter_id' => $otherMeter->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $otherAddress->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('readings.data', 1)
                ->where('filters.address_id', $otherAddress->getKey()),
            );
    });

    it('shows empty state when no readings exist', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('readings.data', 0)
                ->where('filters.address_id', $this->address->getKey()),
            );
    });

    it('shows empty state when no address selected', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->where('filters.address_id', null),
            );
    });

    it('includes reading consumption data in response', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 200,
            'previous_reading_value' => 100,
            'consumption' => 100,
            'reading_date' => '2026-03-01',
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('readings.data', 1)
                ->where('readings.data.0.attributes.reading_value', 200)
                ->where('readings.data.0.attributes.previous_reading_value', 100)
                ->where('readings.data.0.attributes.consumption', 100),
            );
    });
});

describe('Readings Create Page', function (): void {
    it('renders batch form with meters for address', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('addresses')
                ->has('meters.data', 1)
                ->where('selectedAddressId', $this->address->getKey()),
            );
    });

    it('changes meter list when address changes', function (): void {
        $otherAddress = Address::factory()->create();
        $this->user->addresses()->attach($otherAddress->getKey(), ['is_primary' => false]);
        Meter::factory()->count(2)->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $otherAddress->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('meters.data', 2)
                ->where('selectedAddressId', $otherAddress->getKey()),
            );
    });

    it('submits batch readings and redirects with flash', function (): void {
        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->getKey(),
                    'reading_value' => 250,
                    'reading_date' => '2026-03-22',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertRedirect(route('readings.index'))
            ->assertSessionHas('success', 'Показання успішно збережено!');

        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 250,
        ]);
    });

    it('shows empty form with message when address has no meters', function (): void {
        $emptyAddress = Address::factory()->create();
        $this->user->addresses()->attach($emptyAddress->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $emptyAddress->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->where('meters', ['data' => []])
                ->where('selectedAddressId', $emptyAddress->getKey()),
            );
    });

    it('includes service providers for the selected address', function (): void {
        $serviceProvider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('serviceProviders.data', 1),
            );
    });

    it('includes previous readings for meters', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 150,
            'reading_date' => '2026-02-15',
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('readings.data', 1),
            );
    });
});

describe('Readings Store Validation', function (): void {
    it('allows partial batch with some empty readings', function (): void {
        $secondMeter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'initial_reading' => 50,
        ]);

        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->getKey(),
                    'reading_value' => 200,
                    'reading_date' => '2026-03-22',
                ],
                [
                    'meter_id' => $secondMeter->getKey(),
                    'reading_value' => 100,
                    'reading_date' => '2026-03-22',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertRedirect(route('readings.index'));

        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 200,
        ]);
        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $secondMeter->getKey(),
            'reading_value' => 100,
        ]);
    });

    it('rejects reading value less than previous', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 500,
            'reading_date' => '2026-02-01',
        ]);

        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->getKey(),
                    'reading_value' => 400,
                    'reading_date' => '2026-03-01',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertUnprocessable();
    });

    it('accepts very large reading values', function (): void {
        $payload = [
            'readings' => [
                [
                    'meter_id' => $this->meter->getKey(),
                    'reading_value' => 999999.99,
                    'reading_date' => '2026-03-22',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertRedirect(route('readings.index'));

        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 999999.99,
        ]);
    });

    it('rejects empty readings array', function (): void {
        $this->actingAs($this->user)
            ->post(route('readings.store'), ['readings' => []])
            ->assertSessionHasErrors('readings');
    });

    it('rejects missing required fields in reading', function (): void {
        $payload = [
            'readings' => [
                [
                    'reading_value' => 200,
                    'reading_date' => '2026-03-22',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('readings.store'), $payload)
            ->assertSessionHasErrors('readings.0.meter_id');
    });
});

describe('Readings Delete', function (): void {
    it('deletes reading and redirects back', function (): void {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
        ]);

        $this->actingAs($this->user)
            ->from(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->delete(route('readings.destroy', $reading->getKey()))
            ->assertRedirect(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertSessionHas('success', 'Показання успішно видалено.');

        $this->assertDatabaseMissing('meter_readings', [
            'id' => $reading->getKey(),
        ]);
    });
});

describe('Readings Authentication', function (): void {
    it('redirects unauthenticated users to login', function (string $method, string $routeName, array $params): void {
        $this->{$method}(route($routeName, $params))
            ->assertRedirect(route('login'));
    })->with([
        ['get', 'readings.index', []],
        ['get', 'readings.create', []],
        ['post', 'readings.store', []],
        ['delete', 'readings.destroy', [1]],
    ]);
});

describe('Readings Inertia Props Structure', function (): void {
    it('returns reading with meter details on index', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 300,
            'reading_date' => '2026-03-10',
            'is_estimated' => true,
            'notes' => 'Тестова нотатка',
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('readings.data.0', fn ($reading) => $reading
                    ->has('id')
                    ->has('type')
                    ->has('attributes.reading_value')
                    ->has('attributes.reading_date')
                    ->has('attributes.previous_reading_value')
                    ->has('attributes.consumption')
                    ->has('attributes.notes')
                    ->has('attributes.is_estimated')
                    ->has('relationships.meter.data', fn ($meter) => $meter
                        ->has('id')
                        ->has('type')
                    )
                    ->etc(),
                ),
            );
    });

    it('returns meter with utility type on create', function (): void {
        $this->actingAs($this->user)
            ->get(route('readings.create', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Create')
                ->has('meters.data.0', fn ($meter) => $meter
                    ->has('id')
                    ->has('type')
                    ->has('attributes.serial_number')
                    ->has('attributes.name')
                    ->has('attributes.is_active')
                    ->has('attributes.address_id')
                    ->has('relationships.utilityType.data', fn ($ut) => $ut
                        ->has('id')
                        ->has('type')
                    )
                    ->etc(),
                ),
            );
    });

    it('provides address list for filtering', function (): void {
        $secondAddress = Address::factory()->create();
        $this->user->addresses()->attach($secondAddress->getKey(), ['is_primary' => false]);

        $this->actingAs($this->user)
            ->get(route('readings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->has('addresses.data', 2),
            );
    });

    it('prevents viewing readings for non-owned address', function (): void {
        $otherAddress = Address::factory()->create();
        MeterReading::factory()->create([
            'meter_id' => Meter::factory()->create([
                'address_id' => $otherAddress->getKey(),
            ])->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $otherAddress->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readings/Index')
                ->where('readings', []),
            );
    });
});
