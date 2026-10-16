<?php

declare(strict_types=1);

namespace Modules\Ai\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;

/** @extends Factory<AssistantMeterPhoto> */
class AssistantMeterPhotoFactory extends Factory
{
    protected $model = AssistantMeterPhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => null,
            'disk' => AssistantMeterPhoto::DISK,
            'path' => 'assistant-meter-photos/'.fake()->uuid().'.jpg',
            'taken_at' => null,
            'consumed_at' => null,
            'expires_at' => now()->addHours(AssistantMeterPhoto::TTL_HOURS),
        ];
    }

    public function withExif(): static
    {
        return $this->state(['taken_at' => now()->subHour()]);
    }

    public function consumed(): static
    {
        return $this->state(['consumed_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }
}
