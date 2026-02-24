<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Rate limiting', function () {
    it('allows requests within API limit', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/utility-types')
            ->assertSuccessful();
    });

    it('returns 429 when API rate limit is exceeded', function () {
        $user = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user, 'api')
                ->getJson('/api/v1/utility-types');
        }

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/utility-types')
            ->assertStatus(429)
            ->assertJson([
                'statusCode' => 429,
                'message' => 'Too many requests',
            ]);
    });

    it('returns 429 when auth rate limit is exceeded', function () {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertStatus(429)
            ->assertJson([
                'statusCode' => 429,
                'message' => 'Too many requests',
            ]);
    });
});
