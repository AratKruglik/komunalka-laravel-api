<?php

declare(strict_types=1);

namespace Modules\Address\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Address\Models\Region;

/** @extends Factory<Region> */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->state(),
        ];
    }
}
