<?php

declare(strict_types=1);

namespace Modules\Address\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Address\Models\Address;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;

/** @extends Factory<Address> */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'address_type_id' => AddressType::factory(),
            'city' => fake()->city(),
            'street' => fake()->streetName(),
            'building_number' => fake()->buildingNumber(),
            'apartment_number' => fake()->optional()->numberBetween(1, 200),
            'zip_code' => fake()->optional()->postcode(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
