<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Settings OAuth Link', function (): void {
    it('redirects authenticated user to OAuth provider', function (): void {
        $user = User::factory()->create();

        mockWebOAuthDriver('google');

        $this->actingAs($user)
            ->get(route('settings.oauth.link', ['provider' => 'google']))
            ->assertRedirect();
    });

    it('redirects guest to login', function (): void {
        $this->get(route('settings.oauth.link', ['provider' => 'google']))
            ->assertRedirect(route('login'));
    });
});

describe('Settings OAuth Unlink', function (): void {
    beforeEach(function (): void {
        $this->oauthUser = User::factory()->oauthGoogle()->create([
            'password' => 'password',
        ]);
    });

    it('unlinks provider with valid password', function (): void {
        $this->actingAs($this->oauthUser)
            ->delete(route('settings.oauth.unlink', ['provider' => 'google']), [
                'password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', "Провайдер успішно від'єднано.");

        $this->oauthUser->refresh();
        expect($this->oauthUser->auth_provider->value)->toBe('local')
            ->and($this->oauthUser->external_id)->toBeNull();
    });

    it('returns validation error for wrong password', function (): void {
        $this->actingAs($this->oauthUser)
            ->delete(route('settings.oauth.unlink', ['provider' => 'google']), [
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('password');
    });

    it('returns validation error when user has no password set', function (): void {
        $userWithoutPassword = User::factory()->oauthGoogle()->create(['password' => null]);

        $this->actingAs($userWithoutPassword)
            ->delete(route('settings.oauth.unlink', ['provider' => 'google']), [
                'password' => 'any-password',
            ])
            ->assertSessionHasErrors('password');
    });

    it('returns validation error when password field is missing', function (): void {
        $this->actingAs($this->oauthUser)
            ->delete(route('settings.oauth.unlink', ['provider' => 'google']), [])
            ->assertSessionHasErrors('password');
    });

    it('redirects guest to login', function (): void {
        $this->delete(route('settings.oauth.unlink', ['provider' => 'google']), [
            'password' => 'password',
        ])
            ->assertRedirect(route('login'));
    });
});
