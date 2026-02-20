<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Models\ServiceCounter;
use Modules\Shared\Models\ServiceCounterValue;

/** @extends Factory<ServiceCounterValue> */
class ServiceCounterValueFactory extends Factory
{
    protected $model = ServiceCounterValue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'service_counter_id' => ServiceCounter::factory(),
            'value' => fake()->randomFloat(2, 0, 10000),
        ];
    }
}
