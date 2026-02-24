<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Auth\Models\RefreshToken;
use Modules\Auth\Models\User;

/** @extends Factory<RefreshToken> */
class RefreshTokenFactory extends Factory
{
    protected $model = RefreshToken::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'token' => Str::random(64),
            'user_id' => User::factory(),
            'expiry_date' => now()->addDays(7),
            'is_used' => false,
            'is_revoked' => false,
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'expiry_date' => now()->subDay(),
        ]);
    }

    public function used(): static
    {
        return $this->state([
            'is_used' => true,
        ]);
    }

    public function revoked(): static
    {
        return $this->state([
            'is_revoked' => true,
        ]);
    }
}
