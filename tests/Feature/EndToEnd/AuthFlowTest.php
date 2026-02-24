<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

describe('E2E: register -> login -> refresh -> validate -> revoke', function () {
    it('completes full auth lifecycle', function () {
        $payload = [
            'username' => 'e2euser',
            'first_name' => 'E2E',
            'last_name' => 'User',
            'phone_number' => '+380501234567',
            'email' => 'e2e@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $registerResponse = $this->postJson(route('api.auth.register'), $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.user.email', 'e2e@example.com');

        $this->assertDatabaseHas('users', ['email' => 'e2e@example.com']);

        $registerAccessToken = $registerResponse->json('data.access_token');
        $registerRefreshToken = $registerResponse->json('data.refresh_token');

        $loginResponse = $this->postJson(route('api.auth.login'), [
            'email' => 'e2e@example.com',
            'password' => 'password123',
        ])->assertSuccessful();

        $loginAccessToken = $loginResponse->json('data.access_token');
        $loginRefreshToken = $loginResponse->json('data.refresh_token');

        expect($loginAccessToken)->not->toBe($registerAccessToken);

        $this->withHeader('Authorization', "Bearer {$loginAccessToken}")
            ->getJson(route('api.auth.validate-token'))
            ->assertSuccessful()
            ->assertJsonPath('data.email', 'e2e@example.com');

        $refreshResponse = $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $loginRefreshToken,
        ])->assertSuccessful();

        $newRefreshToken = $refreshResponse->json('data.refresh_token');

        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $loginRefreshToken,
        ])->assertUnprocessable();

        $this->withHeader('Authorization', "Bearer {$loginAccessToken}")
            ->postJson(route('api.auth.revoke-token'), [
                'refresh_token' => $newRefreshToken,
            ])->assertSuccessful();

        $this->postJson(route('api.auth.refresh-token'), [
            'refresh_token' => $newRefreshToken,
        ])->assertUnprocessable();
    });
});

describe('E2E: OAuth authorize -> callback -> validate', function () {
    it('completes OAuth flow', function () {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('oauth-e2e-id');
        $socialiteUser->shouldReceive('getEmail')->andReturn('oauth-e2e@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('OAuth E2E');
        $socialiteUser->shouldReceive('getNickname')->andReturn('oauthe2e');
        $socialiteUser->user = ['given_name' => 'OAuth', 'family_name' => 'E2E'];

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('userFromToken')->andReturn($socialiteUser);
        $driver->shouldReceive('user')->andReturn($socialiteUser);

        $redirect = Mockery::mock();
        $redirect->shouldReceive('getTargetUrl')->andReturn('https://accounts.example.com/authorize?provider=google');
        $driver->shouldReceive('redirect')->andReturn($redirect);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->getJson(route('api.auth.oauth.authorize', ['provider' => 'google']))
            ->assertSuccessful()
            ->assertJsonStructure(['url', 'state']);

        $callbackResponse = $this->postJson(route('api.auth.oauth.callback'), [
            'provider' => 'google',
            'code' => 'valid-auth-code',
        ])->assertSuccessful()
            ->assertJsonStructure(['data' => ['user', 'access_token', 'refresh_token']]);

        $this->assertDatabaseHas('users', ['email' => 'oauth-e2e@example.com']);

        $accessToken = $callbackResponse->json('data.access_token');

        $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->getJson(route('api.auth.validate-token'))
            ->assertSuccessful()
            ->assertJsonPath('data.email', 'oauth-e2e@example.com');
    });
});
