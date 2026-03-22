<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

beforeEach(function (): void {
    $this->validPayload = [
        'username' => 'newuser',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone_number' => '+380501234567',
        'email' => 'jane@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ];
});

describe('Register Page', function (): void {
    it('renders register page', function (): void {
        $this->withoutVite()
            ->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    });

    it('creates user and logs in', function (): void {
        $this->post(route('register'), $this->validPayload)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    });

    it('fails with validation errors', function (string $field): void {
        $payload = $this->validPayload;
        unset($payload[$field]);

        $this->post(route('register'), $payload)
            ->assertSessionHasErrors($field);

        $this->assertGuest();
    })->with(['username', 'first_name', 'last_name', 'email', 'password']);

    it('fails with duplicate email', function (): void {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->post(route('register'), $this->validPayload)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('fails with duplicate username', function (): void {
        User::factory()->create(['username' => 'newuser']);

        $this->post(route('register'), $this->validPayload)
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    });

    it('fails with invalid email format', function (): void {
        $payload = array_merge($this->validPayload, ['email' => 'not-an-email']);

        $this->post(route('register'), $payload)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('fails when password confirmation does not match', function (): void {
        $payload = array_merge($this->validPayload, [
            'password_confirmation' => 'different-password',
        ]);

        $this->post(route('register'), $payload)
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    });

    it('fails with password shorter than 8 characters', function (): void {
        $payload = array_merge($this->validPayload, [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $this->post(route('register'), $payload)
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    });

    it('redirects authenticated users away from register', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('dashboard'));
    });
});
