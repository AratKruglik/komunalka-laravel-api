<?php

declare(strict_types=1);

use Modules\Auth\Models\RefreshToken;
use Modules\Auth\Models\User;

describe('POST /api/v1/auth/refresh-token', function () {
    it('refreshes token successfully', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create(['user_id' => $user->getKey()]);

        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $refreshToken->token,
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
            ]);
    });

    it('marks old refresh token as used', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create(['user_id' => $user->getKey()]);

        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $refreshToken->token,
        ])->assertSuccessful();

        $refreshToken->refresh();

        expect($refreshToken->is_used)->toBeTrue();
    });

    it('creates new refresh token record', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create(['user_id' => $user->getKey()]);

        $response = $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $refreshToken->token,
        ])->assertSuccessful();

        $newToken = $response->json('data.refresh_token');

        expect($newToken)->not->toBe($refreshToken->token);

        $this->assertDatabaseHas('refresh_tokens', ['token' => $newToken]);
    });

    it('rejects used refresh token', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->used()->create(['user_id' => $user->getKey()]);

        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $refreshToken->token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    });

    it('rejects revoked refresh token', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->revoked()->create(['user_id' => $user->getKey()]);

        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $refreshToken->token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    });

    it('rejects expired refresh token', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->expired()->create(['user_id' => $user->getKey()]);

        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $refreshToken->token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    });

    it('fails with invalid refresh token', function () {
        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => 'nonexistent-token',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    });
});

describe('POST /api/v1/auth/revoke-token', function () {
    it('revokes token successfully', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create(['user_id' => $user->getKey()]);

        $this->actingAs($user, 'api')
            ->postJson(route('api.auth.revoke-token'), [
                'refresh_token' => $refreshToken->token,
            ])->assertSuccessful()
            ->assertJsonPath('message', 'Token revoked successfully.');

        $refreshToken->refresh();

        expect($refreshToken->is_revoked)->toBeTrue();
    });

    it('requires authentication', function () {
        $this->postJson(route('api.auth.revoke-token'), [
            'refresh_token' => 'some-token',
        ])->assertUnauthorized();
    });
});

describe('GET /api/v1/auth/validate-token', function () {
    it('returns user data for valid token', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson(route('api.auth.validate-token'))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $user->getKey())
            ->assertJsonPath('data.email', $user->email);
    });

    it('returns 401 for missing token', function () {
        $this->getJson(route('api.auth.validate-token'))
            ->assertUnauthorized();
    });

    it('returns user resource structure', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson(route('api.auth.validate-token'))
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
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
            ]);
    });
});
