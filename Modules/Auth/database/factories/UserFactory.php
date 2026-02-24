<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Models\User;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone_number' => fake()->phoneNumber(),
            'role' => UserRole::User,
            'auth_provider' => AuthProvider::Local,
            'email_verified' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state([
            'role' => UserRole::Admin,
        ]);
    }

    public function oauthGoogle(): static
    {
        return $this->state([
            'auth_provider' => AuthProvider::Google,
            'external_id' => fake()->uuid(),
            'password' => null,
        ]);
    }

    public function oauthGithub(): static
    {
        return $this->state([
            'auth_provider' => AuthProvider::GitHub,
            'external_id' => fake()->uuid(),
            'password' => null,
        ]);
    }

    public function unverified(): static
    {
        return $this->state([
            'email_verified' => false,
            'email_verified_at' => null,
        ]);
    }
}
