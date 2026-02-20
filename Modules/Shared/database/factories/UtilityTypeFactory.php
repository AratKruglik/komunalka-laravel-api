<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Models\UtilityType;

/** @extends Factory<UtilityType> */
class UtilityTypeFactory extends Factory
{
    protected $model = UtilityType::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'display_name' => fake()->words(2, true),
            'unit' => fake()->randomElement(['kWh', 'm³', 'Gcal']),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
