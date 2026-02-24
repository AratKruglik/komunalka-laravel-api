<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('POST /api/v1/auth/login', function () {
    it('logs in with valid credentials', function () {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => 'john@example.com',
            'password' => 'password123',
        ])->assertSuccessful();
    });

    it('returns authentication tokens on login', function () {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => 'john@example.com',
            'password' => 'password123',
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                ],
            ])
            ->assertJsonPath('data.token_type', 'bearer');
    });

    it('fails with invalid password', function () {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('fails with non-existent email', function () {
        $this->postJson(route('api.auth.login'), [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('blocks OAuth-only users from local login', function () {
        User::factory()->oauthGoogle()->create([
            'email' => 'oauth@example.com',
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => 'oauth@example.com',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('updates last_login_at on successful login', function () {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
            'last_login_at' => null,
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => 'john@example.com',
            'password' => 'password123',
        ])->assertSuccessful();

        $user->refresh();

        expect($user->last_login_at)->not->toBeNull();
    });
});
