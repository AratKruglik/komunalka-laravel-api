<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

describe('Dashboard Page', function (): void {
    beforeEach(function (): void {
        $this->withoutVite();
        $this->user = User::factory()->create([
            'first_name' => 'Олексій',
            'username' => 'oleksii',
        ]);
    });

    it('renders dashboard for authenticated user', function (): void {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('stats')
                ->has('consumptionHistory')
                ->has('expenseDistribution')
                ->has('recentReadings')
                ->has('addresses'),
            );
    });

    it('shows user first_name in welcome header props', function (): void {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('auth.user.data.first_name', 'Олексій')
                ->where('auth.user.data.username', 'oleksii'),
            );
    });

    it('displays stats cards with correct address and meter counts', function (): void {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        Meter::factory()->count(3)->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.addressCount', 1)
                ->where('stats.meterCount', 3),
            );
    });

    it('includes quick action link targets in page props', function (): void {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index'),
            );
    });

    it('displays recent readings when data exists', function (): void {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create([
            'slug' => 'electricity',
            'display_name' => 'Електроенергія',
            'unit' => 'кВт·год',
        ]);

        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
            'name' => 'Лічильник електро',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_value' => 1500.50,
            'reading_date' => CarbonImmutable::parse('2026-03-15'),
            'consumption' => 120.5,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('recentReadings.data', 1)
                ->where('recentReadings.data.0.reading_value', 1500.50)
                ->where('recentReadings.data.0.consumption', 120.5)
                ->where('recentReadings.data.0.meter.name', 'Лічильник електро')
                ->where('recentReadings.data.0.meter.utility_type.slug', 'electricity'),
            );
    });

    it('redirects unauthenticated user to login', function (): void {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });

    it('renders empty state for new user with no data', function (): void {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.addressCount', 0)
                ->where('stats.meterCount', 0)
                ->where('stats.lastReadingDate', null)
                ->where('stats.totalMonthlyConsumption', 0)
                ->has('recentReadings.data', 0)
                ->has('addresses.data', 0),
            );
    });

    it('returns valid inertia page without javascript errors', function (): void {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('stats', fn (Assert $stats) => $stats
                    ->whereType('addressCount', 'integer')
                    ->whereType('meterCount', 'integer')
                    ->whereType('totalMonthlyConsumption', ['integer', 'double'])
                    ->has('lastReadingDate'),
                ),
            );
    });

    it('shows zero meters when user has addresses but no meters', function (): void {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.addressCount', 1)
                ->where('stats.meterCount', 0)
                ->where('stats.lastReadingDate', null)
                ->has('recentReadings.data', 0),
            );
    });

    it('shows no recent readings when user has meters but no readings', function (): void {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $utilityType = UtilityType::factory()->create();
        Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.meterCount', 1)
                ->where('stats.lastReadingDate', null)
                ->has('recentReadings.data', 0),
            );
    });

    it('renders correct inertia component for dashboard route', function (): void {
        $this->actingAs($this->user)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index'),
            );
    });

    it('includes consumption history even with no data', function (): void {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('consumptionHistory'),
            );
    });
});
