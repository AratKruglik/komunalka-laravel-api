<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

describe('DashboardController', function (): void {
    beforeEach(function (): void {
        $this->withoutVite();
        $this->user = User::factory()->create();
    });

    it('renders dashboard page', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index'),
            );
    });

    it('includes stats data', function (): void {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => $utilityType->getKey(),
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => CarbonImmutable::now(),
            'consumption' => 42.5,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->has('stats')
                ->where('stats.addressCount', 1)
                ->where('stats.meterCount', 1),
            );
    });

    it('includes consumption history', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->has('consumptionHistory'),
            );
    });

    it('includes expense distribution', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->has('expenseDistribution'),
            );
    });

    it('includes recent readings', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->has('recentReadings'),
            );
    });

    it('requires authentication', function (): void {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });

    it('works for user with no data', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->where('stats.addressCount', 0)
                ->where('stats.meterCount', 0)
                ->where('stats.lastReadingDate', null)
                ->where('stats.totalMonthlyConsumption', 0),
            );
    });
});
