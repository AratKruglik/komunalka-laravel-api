<?php

declare(strict_types=1);

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Address\Models\Address;
use Modules\Billing\Models\ServiceProvider;
use Modules\Shared\Models\UtilityType;

/** @extends Factory<ServiceProvider> */
class ServiceProviderFactory extends Factory
{
    protected $model = ServiceProvider::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'address_id' => Address::factory(),
            'utility_type_id' => UtilityType::factory(),
            'name' => fake()->company(),
            'description' => fake()->optional()->sentence(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'website' => fake()->optional()->url(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
