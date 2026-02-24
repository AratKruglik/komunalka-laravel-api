<?php

declare(strict_types=1);

namespace Modules\Address\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Address\Models\AddressType;

/** @extends Factory<AddressType> */
class AddressTypeFactory extends Factory
{
    protected $model = AddressType::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['home', 'building', 'office', 'apartment']),
        ];
    }
}
