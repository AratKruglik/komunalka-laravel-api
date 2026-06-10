<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

describe('Forgot Password Flow', function (): void {
    it('renders forgot password page', function (): void {
        $this->withoutVite()
            ->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
    });

    it('sends password reset link for valid email', function (): void {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('password.email'), ['email' => 'user@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');
    });

    it('fails to send reset link for invalid email', function (): void {
        $this->post(route('password.email'), ['email' => 'not-registered@example.com'])
            ->assertSessionHasErrors('email');
    });
});
