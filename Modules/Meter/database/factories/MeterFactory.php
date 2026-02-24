<?php

declare(strict_types=1);

namespace Modules\Meter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Address\Models\Address;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

/** @extends Factory<Meter> */
class MeterFactory extends Factory
{
    protected $model = Meter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'address_id' => Address::factory(),
            'utility_type_id' => UtilityType::factory(),
            'service_provider_id' => null,
            'serial_number' => fake()->unique()->numerify('M-######'),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'model_name' => fake()->optional()->word(),
            'location' => fake()->optional()->word(),
            'installation_date' => fake()->optional()->date(),
            'initial_reading' => 0,
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withServiceProvider(): static
    {
        return $this->state(['service_provider_id' => ServiceProvider::factory()]);
    }
}
