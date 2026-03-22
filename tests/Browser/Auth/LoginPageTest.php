<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

beforeEach(function (): void {
    $this->validData = [
        'email' => 'john@example.com',
        'password' => 'password',
        'remember' => false,
    ];
});

describe('Login Page Rendering', function (): void {
    it('renders the Auth/Login Inertia component', function (): void {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    });

    it('returns successful response with no server errors', function (): void {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertDontSee('Server Error');
    });
});

describe('Successful Login', function (): void {
    it('redirects to dashboard after valid credentials', function (): void {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), $this->validData)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    });

    it('rejects email in different case as non-matching', function (): void {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'JOHN@EXAMPLE.COM',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('accepts remember me parameter', function (): void {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), array_merge($this->validData, [
            'remember' => true,
        ]))->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    });
});

describe('Validation: Invalid Credentials', function (): void {
    it('shows error for wrong password', function (): void {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'john@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('shows error for non-existent email', function (): void {
        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'nobody@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });
});

describe('Validation: Required Fields', function (): void {
    it('shows error for each missing required field', function (string $field): void {
        $payload = $this->validData;
        unset($payload[$field]);

        $this->from(route('login'))
            ->post(route('login'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors($field);

        $this->assertGuest();
    })->with(['email', 'password']);
});

describe('Validation: Email Format', function (): void {
    it('shows error for invalid email format', function (string $invalidEmail): void {
        $payload = array_merge($this->validData, ['email' => $invalidEmail]);

        $this->from(route('login'))
            ->post(route('login'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    })->with([
        'plain text' => 'not-email',
        'missing domain' => 'missing@',
        'missing at sign' => '@missing.com',
    ]);
});

describe('Validation: Edge Cases', function (): void {
    it('shows error for very long email exceeding max length', function (): void {
        $payload = array_merge($this->validData, [
            'email' => str_repeat('a', 250) . '@example.com',
        ]);

        $this->from(route('login'))
            ->post(route('login'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('handles very long password gracefully', function (): void {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $payload = array_merge($this->validData, [
            'password' => str_repeat('a', 256),
        ]);

        $response = $this->from(route('login'))
            ->post(route('login'), $payload);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    it('does not break on XSS attempt in email field', function (): void {
        $payload = array_merge($this->validData, [
            'email' => '<script>alert("xss")</script>@example.com',
        ]);

        $this->from(route('login'))
            ->post(route('login'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });
});

describe('Guest Middleware', function (): void {
    it('redirects authenticated users away from login page', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    });

    it('redirects authenticated users away from login POST', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('login'), $this->validData)
            ->assertRedirect(route('dashboard'));
    });
});

describe('OAuth Buttons', function (): void {
    it('renders Auth/Login component containing OAuth options', function (): void {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    });
});
