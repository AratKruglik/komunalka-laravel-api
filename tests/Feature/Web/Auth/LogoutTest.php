<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Logout', function (): void {
    it('logs out authenticated user', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('invalidates session', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['test_key' => 'test_value'])
            ->post(route('logout'));

        $this->assertGuest();
    });

    it('redirects to login', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));
    });

    it('requires authentication', function (): void {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    });
});
