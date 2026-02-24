<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

beforeEach(function () {
    $this->validPayload = [
        'username' => 'johndoe',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '+380501234567',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];
});

describe('POST /api/v1/auth/register', function () {
    it('registers a new user successfully', function () {
        $this->postJson(route('api.auth.register'), $this->validPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.user.username', 'johndoe')
            ->assertJsonPath('data.user.email', 'john@example.com');

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    });

    it('returns authentication tokens on registration', function () {
        $this->postJson(route('api.auth.register'), $this->validPayload)
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

    it('returns user data in response', function () {
        $this->postJson(route('api.auth.register'), $this->validPayload)
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'username',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'role',
                        'auth_provider',
                        'email_verified',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    });

    it('fails with missing required fields', function (string $field) {
        $payload = $this->validPayload;
        unset($payload[$field]);

        $this->postJson(route('api.auth.register'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['username', 'first_name', 'last_name', 'email', 'password']);

    it('fails with invalid email format', function () {
        $payload = array_merge($this->validPayload, ['email' => 'not-an-email']);

        $this->postJson(route('api.auth.register'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('fails with duplicate email', function () {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson(route('api.auth.register'), $this->validPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('fails with duplicate username', function () {
        User::factory()->create(['username' => 'johndoe']);

        $this->postJson(route('api.auth.register'), $this->validPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    });

    it('fails when password confirmation does not match', function () {
        $payload = array_merge($this->validPayload, [
            'password_confirmation' => 'different-password',
        ]);

        $this->postJson(route('api.auth.register'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('fails with password shorter than 8 characters', function () {
        $payload = array_merge($this->validPayload, [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $this->postJson(route('api.auth.register'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('sets default role to user', function () {
        $this->postJson(route('api.auth.register'), $this->validPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.user.role', 'user');
    });

    it('sets default auth provider to local', function () {
        $this->postJson(route('api.auth.register'), $this->validPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.user.auth_provider', 'local');
    });
});
