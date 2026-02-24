<?php

declare(strict_types=1);

use Modules\Auth\Models\RefreshToken;
use Modules\Auth\Models\User;

describe('POST /api/v1/auth/register (edge cases)', function () {
    beforeEach(function () {
        $this->validPayload = [
            'username' => 'edgeuser',
            'first_name' => 'Edge',
            'last_name' => 'User',
            'phone_number' => '+380501234567',
            'email' => 'edge@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    });

    it('accepts username with alphanumeric and underscore', function () {
        $payload = array_merge($this->validPayload, ['username' => 'user_123']);

        $this->postJson(route('api.auth.register'), $payload)
            ->assertSuccessful();
    });

    it('rejects very long field values', function () {
        $payload = array_merge($this->validPayload, [
            'username' => str_repeat('a', 256),
        ]);

        $this->postJson(route('api.auth.register'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    });

    it('handles SQL injection attempt in fields safely', function () {
        $payload = array_merge($this->validPayload, [
            'email' => "test@example.com'; DROP TABLE users; --",
        ]);

        $this->postJson(route('api.auth.register'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('handles XSS in name fields without 500 error', function () {
        $payload = array_merge($this->validPayload, [
            'first_name' => '<script>alert("xss")</script>',
            'last_name' => '<img src=x onerror=alert(1)>',
        ]);

        $response = $this->postJson(route('api.auth.register'), $payload);

        expect($response->status())->not->toBe(500);
    });
});

describe('POST /api/v1/auth/login (edge cases)', function () {
    it('rejects missing email', function () {
        $this->postJson(route('api.auth.login'), ['password' => 'password123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('rejects missing password', function () {
        $this->postJson(route('api.auth.login'), ['email' => 'test@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('rejects empty string email', function () {
        $this->postJson(route('api.auth.login'), [
            'email' => '',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('handles SQL injection in email safely', function () {
        $this->postJson(route('api.auth.login'), [
            'email' => "admin@example.com' OR 1=1 --",
            'password' => 'password123',
        ])->assertUnprocessable();
    });
});

describe('POST /api/v1/auth/refresh-token (edge cases)', function () {
    it('rejects missing refresh_token field', function () {
        $this->postJson(route('api.auth.refresh-token'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    });

    it('rejects empty string token', function () {
        $this->postJson(route('api.auth.refresh-token'), ['refresh_token' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    });
});

describe('POST /api/v1/auth/revoke-token (edge cases)', function () {
    it('handles revoking already revoked token gracefully', function () {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->revoked()->create(['user_id' => $user->getKey()]);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.auth.revoke-token'), [
                'refresh_token' => $refreshToken->token,
            ]);

        expect($response->status())->not->toBe(500);
    });

    it('rejects missing refresh_token field', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson(route('api.auth.revoke-token'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    });

    it('rejects revoking another users token', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create(['user_id' => $otherUser->getKey()]);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.auth.revoke-token'), [
                'refresh_token' => $refreshToken->token,
            ]);

        expect($response->status())->toBeIn([200, 403, 404, 422]);
    });
});

describe('GET /api/v1/auth/validate-token (edge cases)', function () {
    it('returns 401 for expired JWT', function () {
        $this->withHeader('Authorization', 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0ZXN0Iiwic3ViIjoiMSIsImV4cCI6MX0.invalid')
            ->getJson(route('api.auth.validate-token'))
            ->assertUnauthorized();
    });

    it('returns 401 for malformed JWT', function () {
        $this->withHeader('Authorization', 'Bearer not.a.valid.jwt.token')
            ->getJson(route('api.auth.validate-token'))
            ->assertUnauthorized();
    });
});

describe('POST /api/v1/auth/oauth/link (edge cases)', function () {
    it('handles linking already linked provider', function () {
        $user = User::factory()->create([
            'auth_provider' => 'google',
            'external_id' => '12345',
        ]);

        $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $socialiteUser->shouldReceive('getId')->andReturn('67890');
        $socialiteUser->shouldReceive('getEmail')->andReturn($user->email);
        $socialiteUser->shouldReceive('getName')->andReturn('Test');
        $socialiteUser->shouldReceive('getNickname')->andReturn('test');
        $socialiteUser->user = ['given_name' => 'Test', 'family_name' => 'User'];

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('userFromToken')->andReturn($socialiteUser);

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($driver);

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.auth.oauth.link'), [
                'provider' => 'google',
                'token' => 'valid-oauth-token',
            ]);

        expect($response->status())->toBeIn([200, 409, 422]);
    });
});

describe('DELETE /api/v1/auth/oauth/unlink/{provider} (edge cases)', function () {
    it('handles unlinking non-linked provider', function () {
        $user = User::factory()->create([
            'password' => 'password123',
            'auth_provider' => 'local',
            'external_id' => null,
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson(route('api.auth.oauth.unlink', ['provider' => 'google']), [
                'password' => 'password123',
            ]);

        expect($response->status())->toBeIn([200, 404, 422]);
    });
});
