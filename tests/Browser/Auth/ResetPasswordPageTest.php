<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;

describe('Reset Password Page Rendering', function (): void {
    it('renders the Auth/ResetPassword Inertia component with token and email props', function (): void {
        $token = 'test-token-abc';
        $email = 'user@example.com';

        $this->get(route('password.reset', ['token' => $token, 'email' => $email]))
            ->assertOk();
    });

    it('returns successful response with no server errors', function (): void {
        $this->get(route('password.reset', ['token' => 'some-token', 'email' => 'user@example.com']))
            ->assertOk()
            ->assertDontSee('Server Error');
    });
});

describe('Reset Password Form Submission', function (): void {
    it('resets password and redirects to login for valid token', function (): void {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('password.email'), ['email' => 'user@example.com']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification): bool {
            $response = $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => 'user@example.com',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

            $response->assertRedirect(route('login'));

            return true;
        });
    });

    it('returns validation error for mismatched password confirmation', function (): void {
        $this->post(route('password.update'), [
            'token' => 'any-token',
            'email' => 'user@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');
    });

    it('returns validation error for password shorter than minimum length', function (): void {
        $this->post(route('password.update'), [
            'token' => 'any-token',
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    });

    it('returns validation error when token is invalid', function (): void {
        User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors();
    });
});

describe('Reset Password Guest Middleware', function (): void {
    it('redirects authenticated users away from reset password page', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.reset', ['token' => 'any-token', 'email' => 'user@example.com']))
            ->assertRedirect(route('dashboard'));
    });
});
