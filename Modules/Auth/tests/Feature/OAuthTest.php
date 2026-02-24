<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\Auth\Models\User;

function mockSocialiteDriver(string $provider = 'google', array $overrides = []): void
{
    $defaults = [
        'id' => '12345',
        'email' => 'oauth@example.com',
        'name' => 'OAuth User',
        'nickname' => 'oauthuser',
        'given_name' => 'OAuth',
        'family_name' => 'User',
    ];

    $data = array_merge($defaults, $overrides);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn($data['id']);
    $socialiteUser->shouldReceive('getEmail')->andReturn($data['email']);
    $socialiteUser->shouldReceive('getName')->andReturn($data['name']);
    $socialiteUser->shouldReceive('getNickname')->andReturn($data['nickname']);
    $socialiteUser->user = [
        'given_name' => $data['given_name'],
        'family_name' => $data['family_name'],
    ];

    $driver = Mockery::mock();
    $driver->shouldReceive('stateless')->andReturnSelf();
    $driver->shouldReceive('userFromToken')->andReturn($socialiteUser);
    $driver->shouldReceive('user')->andReturn($socialiteUser);

    $redirect = Mockery::mock();
    $redirect->shouldReceive('getTargetUrl')->andReturn("https://accounts.example.com/authorize?provider={$provider}");
    $driver->shouldReceive('redirect')->andReturn($redirect);

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

describe('GET /api/v1/auth/oauth/{provider}/authorize', function () {
    it('returns authorization URL for google', function () {
        mockSocialiteDriver('google');

        $this->getJson(route('api.auth.oauth.authorize', ['provider' => 'google']))
            ->assertSuccessful()
            ->assertJsonStructure(['url', 'state']);
    });

    it('returns authorization URL for github', function () {
        mockSocialiteDriver('github');

        $this->getJson(route('api.auth.oauth.authorize', ['provider' => 'github']))
            ->assertSuccessful()
            ->assertJsonStructure(['url', 'state']);
    });

    it('fails with invalid provider', function () {
        $this->withoutExceptionHandling();

        $this->getJson(route('api.auth.oauth.authorize', ['provider' => 'invalid']));
    })->throws(ValueError::class);
});

describe('POST /api/v1/auth/oauth/login', function () {
    it('logs in existing user via OAuth by external id', function () {
        $user = User::factory()->oauthGoogle()->create([
            'email' => 'existing@example.com',
            'external_id' => '12345',
        ]);

        mockSocialiteDriver('google', [
            'id' => '12345',
            'email' => 'existing@example.com',
        ]);

        $this->postJson(route('api.auth.oauth.login'), [
            'provider' => 'google',
            'token' => 'valid-oauth-token',
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
            ->assertJsonPath('data.user.email', 'existing@example.com');
    });

    it('creates new user from OAuth if not exists', function () {
        mockSocialiteDriver('google', ['email' => 'new-oauth@example.com']);

        $this->postJson(route('api.auth.oauth.login'), [
            'provider' => 'google',
            'token' => 'valid-oauth-token',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'new-oauth@example.com',
            'auth_provider' => 'google',
            'external_id' => '12345',
        ]);
    });

    it('links existing local user when OAuth email matches', function () {
        User::factory()->create([
            'email' => 'local@example.com',
            'auth_provider' => 'local',
            'external_id' => null,
        ]);

        mockSocialiteDriver('google', [
            'id' => '99999',
            'email' => 'local@example.com',
        ]);

        $this->postJson(route('api.auth.oauth.login'), [
            'provider' => 'google',
            'token' => 'valid-oauth-token',
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.user.email', 'local@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'local@example.com',
            'auth_provider' => 'google',
            'external_id' => '99999',
        ]);
    });

    it('updates last_login_at on OAuth login', function () {
        $user = User::factory()->oauthGoogle()->create([
            'external_id' => '12345',
            'last_login_at' => null,
        ]);

        mockSocialiteDriver('google', [
            'id' => '12345',
            'email' => $user->email,
        ]);

        $this->postJson(route('api.auth.oauth.login'), [
            'provider' => 'google',
            'token' => 'valid-oauth-token',
        ])->assertSuccessful();

        $user->refresh();

        expect($user->last_login_at)->not->toBeNull();
    });

    it('fails with invalid provider', function () {
        $this->postJson(route('api.auth.oauth.login'), [
            'provider' => 'invalid',
            'token' => 'some-token',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('provider');
    });
});

describe('POST /api/v1/auth/oauth/callback', function () {
    it('handles successful callback', function () {
        mockSocialiteDriver('google');

        $this->postJson(route('api.auth.oauth.callback'), [
            'provider' => 'google',
            'code' => 'valid-auth-code',
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

    it('handles callback with error parameter', function () {
        $this->postJson(route('api.auth.oauth.callback'), [
            'provider' => 'google',
            'error' => 'access_denied',
            'error_description' => 'User denied access',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('error');
    });
});

describe('POST /api/v1/auth/oauth/link', function () {
    it('links OAuth provider to authenticated user', function () {
        $user = User::factory()->create();

        mockSocialiteDriver('google');

        $this->actingAs($user, 'api')
            ->postJson(route('api.auth.oauth.link'), [
                'provider' => 'google',
                'token' => 'valid-oauth-token',
            ])
            ->assertSuccessful()
            ->assertJsonPath('message', 'OAuth provider linked successfully.');

        $user->refresh();

        expect($user->auth_provider->value)->toBe('google')
            ->and($user->external_id)->toBe('12345');
    });

    it('requires authentication', function () {
        $this->postJson(route('api.auth.oauth.link'), [
            'provider' => 'google',
            'token' => 'some-token',
        ])->assertUnauthorized();
    });
});

describe('DELETE /api/v1/auth/oauth/unlink/{provider}', function () {
    it('unlinks OAuth provider with valid password', function () {
        $user = User::factory()->create([
            'password' => 'password123',
            'auth_provider' => 'google',
            'external_id' => '12345',
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson(route('api.auth.oauth.unlink', ['provider' => 'google']), [
                'password' => 'password123',
            ])
            ->assertSuccessful()
            ->assertJsonPath('message', 'OAuth provider unlinked successfully.');

        $user->refresh();

        expect($user->auth_provider->value)->toBe('local')
            ->and($user->external_id)->toBeNull();
    });

    it('fails without password', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->deleteJson(route('api.auth.oauth.unlink', ['provider' => 'google']), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('fails with wrong password', function () {
        $user = User::factory()->create(['password' => 'password123']);

        $this->actingAs($user, 'api')
            ->deleteJson(route('api.auth.oauth.unlink', ['provider' => 'google']), [
                'password' => 'wrong-password',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('requires authentication', function () {
        $this->deleteJson(route('api.auth.oauth.unlink', ['provider' => 'google']), [
            'password' => 'password123',
        ])->assertUnauthorized();
    });
});
