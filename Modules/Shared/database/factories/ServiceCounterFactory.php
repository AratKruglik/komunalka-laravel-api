<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Models\ServiceCategory;
use Modules\Shared\Models\ServiceCounter;
use Modules\Shared\Models\ServiceCounterMeasurement;

/** @extends Factory<ServiceCounter> */
class ServiceCounterFactory extends Factory
{
    protected $model = ServiceCounter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'address_id' => fake()->randomNumber(5),
            'service_category_id' => ServiceCategory::factory(),
            'serial_number' => fake()->unique()->numerify('SC-######'),
            'service_counter_measurement_id' => ServiceCounterMeasurement::factory(),
        ];
    }
}
