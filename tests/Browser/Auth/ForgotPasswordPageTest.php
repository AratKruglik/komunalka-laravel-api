<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Forgot Password Page Rendering', function (): void {
    it('renders the Auth/ForgotPassword Inertia component', function (): void {
        $this->get(route('password.request'))
            ->assertOk();
    });

    it('returns successful response with no server errors', function (): void {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertDontSee('Server Error');
    });

    it('passes status prop to the page', function (): void {
        $this->get(route('password.request'))
            ->assertOk();
    });
});

describe('Forgot Password Form Submission', function (): void {
    it('sends reset link and redirects back with status for valid email', function (): void {
        User::factory()->create(['email' => 'user@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'user@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');
    });

    it('returns validation error for unregistered email', function (): void {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    });

    it('returns validation error when email field is empty', function (): void {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => ''])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    });

    it('returns validation error for invalid email format', function (): void {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'not-an-email'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    });
});

describe('Forgot Password Guest Middleware', function (): void {
    it('redirects authenticated users away from forgot password page', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.request'))
            ->assertRedirect(route('dashboard'));
    });
});
