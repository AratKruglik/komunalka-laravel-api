<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('OAuth Flow', function (): void {
    it('redirects to OAuth provider', function (string $provider): void {
        mockWebOAuthDriver($provider);

        $this->get(route('oauth.redirect', ['provider' => $provider]))
            ->assertRedirect();
    })->with(['google', 'github']);

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

    it('fails with invalid provider', function (string $routeName): void {
        $this->withoutExceptionHandling();

        $this->get(route($routeName, ['provider' => 'invalid']));
    })->throws(ValueError::class)->with([
        'redirect' => ['oauth.redirect'],
        'callback' => ['oauth.callback'],
    ]);

    it('redirects guest to login when accessing protected route', function (): void {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });
});
