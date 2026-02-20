<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Models\Currency;

/** @extends Factory<Currency> */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->currencyCode(),
            'name' => fake()->words(2, true),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', '₴']),
        ];
    }
}
