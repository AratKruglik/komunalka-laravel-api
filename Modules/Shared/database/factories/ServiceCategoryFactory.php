<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Models\ServiceCategory;

/** @extends Factory<ServiceCategory> */
class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
