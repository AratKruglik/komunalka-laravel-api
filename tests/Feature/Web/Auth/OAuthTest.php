<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\Auth\Models\User;

function mockWebOAuthDriver(string $provider = 'google', array $overrides = []): void
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

    $redirect = new \Symfony\Component\HttpFoundation\RedirectResponse(
        "https://accounts.example.com/authorize?provider={$provider}",
    );
    $driver->shouldReceive('redirect')->andReturn($redirect);

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

describe('OAuth Flow', function (): void {
    it('redirects to Google OAuth', function (): void {
        mockWebOAuthDriver('google');

        $this->get(route('oauth.redirect', ['provider' => 'google']))
            ->assertRedirect();
    });

    it('redirects to GitHub OAuth', function (): void {
        mockWebOAuthDriver('github');

        $this->get(route('oauth.redirect', ['provider' => 'github']))
            ->assertRedirect();
    });

    it('handles Google callback and authenticates user', function (): void {
        mockWebOAuthDriver('google');

        $this->get(route('oauth.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'oauth@example.com',
            'auth_provider' => 'google',
            'external_id' => '12345',
        ]);
    });

    it('logs in existing user via OAuth callback', function (): void {
        $user = User::factory()->oauthGoogle()->create([
            'email' => 'existing@example.com',
            'external_id' => '12345',
        ]);

        mockWebOAuthDriver('google', [
            'id' => '12345',
            'email' => 'existing@example.com',
        ]);

        $this->get(route('oauth.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('links existing local user when OAuth email matches', function (): void {
        User::factory()->create([
            'email' => 'local@example.com',
            'auth_provider' => 'local',
            'external_id' => null,
        ]);

        mockWebOAuthDriver('google', [
            'id' => '99999',
            'email' => 'local@example.com',
        ]);

        $this->get(route('oauth.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'local@example.com',
            'auth_provider' => 'google',
            'external_id' => '99999',
        ]);
    });

    it('updates last_login_at on OAuth callback', function (): void {
        $user = User::factory()->oauthGoogle()->create([
            'external_id' => '12345',
            'last_login_at' => null,
        ]);

        mockWebOAuthDriver('google', [
            'id' => '12345',
            'email' => $user->email,
        ]);

        $this->get(route('oauth.callback', ['provider' => 'google']));

        $user->refresh();

        expect($user->last_login_at)->not->toBeNull();
    });

    it('fails with invalid provider on redirect', function (): void {
        $this->withoutExceptionHandling();

        $this->get(route('oauth.redirect', ['provider' => 'invalid']));
    })->throws(ValueError::class);

    it('fails with invalid provider on callback', function (): void {
        $this->withoutExceptionHandling();

        $this->get(route('oauth.callback', ['provider' => 'invalid']));
    })->throws(ValueError::class);
});
