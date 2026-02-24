<?php

declare(strict_types=1);

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

/** @extends Factory<Tariff> */
class TariffFactory extends Factory
{
    protected $model = Tariff::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $effectiveFrom = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'service_provider_id' => ServiceProvider::factory(),
            'utility_type_id' => UtilityType::factory(),
            'currency_id' => Currency::factory(),
            'name' => fake()->words(3, true),
            'base_rate' => fake()->randomFloat(4, 1, 50),
            'service_fee' => fake()->randomFloat(4, 0, 100),
            'effective_from' => $effectiveFrom,
            'effective_to' => fake()->optional()->dateTimeBetween($effectiveFrom, '+1 year'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
