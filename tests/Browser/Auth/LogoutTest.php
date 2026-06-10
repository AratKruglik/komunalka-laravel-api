<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Logout', function (): void {
    it('logs out authenticated user and redirects to login', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('redirects unauthenticated user to login on logout attempt', function (): void {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    });
});
