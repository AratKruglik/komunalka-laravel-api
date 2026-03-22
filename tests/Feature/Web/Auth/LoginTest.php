<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

describe('Login Page', function (): void {
    it('renders login page', function (): void {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    });

    it('authenticates user with valid credentials', function (): void {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ]);

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('fails with invalid credentials', function (): void {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ]);

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('fails with validation errors for empty fields', function (string $field): void {
        $payload = [
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ];
        unset($payload[$field]);

        $this->post(route('login'), $payload)
            ->assertSessionHasErrors($field);

        $this->assertGuest();
    })->with(['email', 'password']);

    it('fails with invalid email format', function (): void {
        $this->post(route('login'), [
            'email' => 'not-an-email',
            'password' => 'secure-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('redirects authenticated users away from login', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    });

    it('redirects to intended URL after login', function (): void {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ]);

        $this->get(route('settings'));

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ])
            ->assertRedirect(route('settings'));

        $this->assertAuthenticatedAs($user);
    });

    it('blocks OAuth-only users from local login', function (): void {
        User::factory()->oauthGoogle()->create([
            'email' => 'oauth@example.com',
        ]);

        $this->post(route('login'), [
            'email' => 'oauth@example.com',
            'password' => 'some-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('updates last_login_at on successful login', function (): void {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secure-password',
            'last_login_at' => null,
        ]);

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ]);

        $user->refresh();

        expect($user->last_login_at)->not->toBeNull();
    });

    it('regenerates session on login', function (): void {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ]);

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ]);

        $this->assertAuthenticated();
    });
});
