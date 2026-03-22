<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

beforeEach(function (): void {
    $this->validData = [
        'username' => 'testuser',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone_number' => '501234567',
        'email' => 'jane@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ];
});

describe('Register Page Rendering', function (): void {
    it('renders the Auth/Register Inertia component', function (): void {
        $this->withoutVite()
            ->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    });

    it('returns successful response with no server errors', function (): void {
        $this->withoutVite()
            ->get(route('register'))
            ->assertOk()
            ->assertDontSee('Server Error');
    });
});

describe('Successful Registration', function (): void {
    it('redirects to dashboard after valid registration', function (): void {
        $this->post(route('register'), $this->validData)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    });

    it('accepts Unicode characters in name fields', function (): void {
        $payload = array_merge($this->validData, [
            'first_name' => 'Олексій',
            'last_name' => 'Петренко',
        ]);

        $this->post(route('register'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'first_name' => 'Олексій',
            'last_name' => 'Петренко',
        ]);
    });

    it('accepts registration without phone number', function (): void {
        $payload = $this->validData;
        unset($payload['phone_number']);

        $this->post(route('register'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    });
});

describe('Validation: Duplicate Data', function (): void {
    it('shows error for duplicate email', function (): void {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->from(route('register'))
            ->post(route('register'), $this->validData)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('returns custom message for duplicate email', function (): void {
        User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->post(route('register'), $this->validData);

        $errors = session('errors');
        expect($errors->get('email'))->toContain('An account with this email already exists.');
    });

    it('shows error for duplicate username', function (): void {
        User::factory()->create(['username' => 'testuser']);

        $this->from(route('register'))
            ->post(route('register'), $this->validData)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    });

    it('returns custom message for duplicate username', function (): void {
        User::factory()->create(['username' => 'testuser']);

        $response = $this->post(route('register'), $this->validData);

        $errors = session('errors');
        expect($errors->get('username'))->toContain('This username is already taken.');
    });
});

describe('Validation: Password', function (): void {
    it('shows error when password confirmation does not match', function (): void {
        $payload = array_merge($this->validData, [
            'password_confirmation' => 'different-password',
        ]);

        $this->from(route('register'))
            ->post(route('register'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    });

    it('returns custom message for password mismatch', function (): void {
        $payload = array_merge($this->validData, [
            'password_confirmation' => 'different-password',
        ]);

        $this->post(route('register'), $payload);

        $errors = session('errors');
        expect($errors->get('password'))->toContain('Password confirmation does not match.');
    });

    it('shows error when password is shorter than 8 characters', function (): void {
        $payload = array_merge($this->validData, [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $this->from(route('register'))
            ->post(route('register'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    });

    it('returns custom message for short password', function (): void {
        $payload = array_merge($this->validData, [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $this->post(route('register'), $payload);

        $errors = session('errors');
        expect($errors->get('password'))->toContain('Password must be at least 8 characters.');
    });
});

describe('Validation: Required Fields', function (): void {
    it('shows errors when all required fields are empty', function (): void {
        $this->from(route('register'))
            ->post(route('register'), [])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'username',
                'first_name',
                'last_name',
                'email',
                'password',
            ]);

        $this->assertGuest();
    });

    it('shows error for each missing required field', function (string $field): void {
        $payload = $this->validData;
        unset($payload[$field]);

        $this->from(route('register'))
            ->post(route('register'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors($field);

        $this->assertGuest();
    })->with(['username', 'first_name', 'last_name', 'email', 'password']);
});

describe('Validation: Email Format', function (): void {
    it('shows error for invalid email format', function (string $invalidEmail): void {
        $payload = array_merge($this->validData, ['email' => $invalidEmail]);

        $this->from(route('register'))
            ->post(route('register'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    })->with([
        'plain text' => 'not-an-email',
        'missing domain' => 'user@',
        'missing at sign' => 'userexample.com',
    ]);
});

describe('Validation: Phone Number', function (): void {
    it('accepts valid phone number', function (): void {
        $payload = array_merge($this->validData, [
            'phone_number' => '501234567',
        ]);

        $this->post(route('register'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone_number' => '501234567',
        ]);
    });
});

describe('Validation: Input Preservation', function (): void {
    it('preserves input after validation error', function (): void {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->from(route('register'))
            ->post(route('register'), $this->validData)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        expect(session('_old_input'))
            ->toHaveKey('username', 'testuser')
            ->toHaveKey('first_name', 'Jane')
            ->toHaveKey('last_name', 'Doe')
            ->toHaveKey('email', 'jane@example.com');
    });
});

describe('Validation: Edge Cases', function (): void {
    it('shows error for very long username exceeding max length', function (): void {
        $payload = array_merge($this->validData, [
            'username' => str_repeat('a', 256),
        ]);

        $this->from(route('register'))
            ->post(route('register'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    });

    it('shows error for very long email exceeding max length', function (): void {
        $payload = array_merge($this->validData, [
            'email' => str_repeat('a', 250) . '@example.com',
        ]);

        $this->from(route('register'))
            ->post(route('register'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });
});

describe('Guest Middleware', function (): void {
    it('redirects authenticated users away from register page', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('dashboard'));
    });

    it('redirects authenticated users away from register POST', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('register'), $this->validData)
            ->assertRedirect(route('dashboard'));
    });
});
