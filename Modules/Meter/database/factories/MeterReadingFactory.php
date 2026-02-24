<?php

declare(strict_types=1);

namespace Modules\Meter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;

/** @extends Factory<MeterReading> */
class MeterReadingFactory extends Factory
{
    protected $model = MeterReading::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'meter_id' => Meter::factory(),
            'reading_value' => fake()->randomFloat(2, 100, 9999),
            'reading_date' => fake()->date(),
            'previous_reading_value' => null,
            'consumption' => null,
            'notes' => null,
            'is_estimated' => false,
            'tariff_id' => null,
        ];
    }

    public function estimated(): static
    {
        return $this->state(['is_estimated' => true]);
    }

    public function withTariff(): static
    {
        return $this->state(['tariff_id' => Tariff::factory()]);
    }
}
