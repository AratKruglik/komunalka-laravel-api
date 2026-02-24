<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Auth token response format compliance', function () {
    it('returns complete token structure on login', function () {
        User::factory()->create([
            'email' => 'token-test@example.com',
            'password' => 'password123',
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => 'token-test@example.com',
            'password' => 'password123',
        ])->assertSuccessful()
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
                        'last_login_at',
                        'created_at',
                        'updated_at',
                    ],
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    });

    it('returns JWT with three segments', function () {
        User::factory()->create([
            'email' => 'jwt@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'jwt@example.com',
            'password' => 'password123',
        ])->assertSuccessful();

        $accessToken = $response->json('data.access_token');
        $segments = explode('.', $accessToken);

        expect($segments)->toHaveCount(3);
    });

    it('returns positive integer expires_in', function () {
        User::factory()->create([
            'email' => 'expires@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'expires@example.com',
            'password' => 'password123',
        ])->assertSuccessful();

        $expiresIn = $response->json('data.expires_in');

        expect($expiresIn)->toBeInt()->toBeGreaterThan(0);
    });

    it('returns same token structure on register', function () {
        $this->postJson(route('api.auth.register'), [
            'username' => 'tokenformatuser',
            'first_name' => 'Token',
            'last_name' => 'Format',
            'phone_number' => '+380501234567',
            'email' => 'tokenformat@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSuccessful()
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
});
