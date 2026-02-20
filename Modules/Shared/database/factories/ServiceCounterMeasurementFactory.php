<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Models\ServiceCounterMeasurement;

/** @extends Factory<ServiceCounterMeasurement> */
class ServiceCounterMeasurementFactory extends Factory
{
    protected $model = ServiceCounterMeasurement::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'measurement' => fake()->randomElement(['kWh', 'm³', 'Gcal', 'L']),
        ];
    }
}
