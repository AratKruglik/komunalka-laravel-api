<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

describe('Reset Password Flow', function (): void {
    it('renders reset password page with valid token', function (): void {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $this->withoutVite()
            ->get(route('password.reset', ['token' => $token, 'email' => 'user@example.com']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ResetPassword')
                ->where('token', $token)
                ->where('email', 'user@example.com')
            );
    });

    it('resets password with valid token and data', function (): void {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();
        expect(Hash::check('new-secure-password', $user->password))->toBeTrue();
    });

    it('fails to reset password with invalid token', function (): void {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertSessionHasErrors('email');
    });
});
