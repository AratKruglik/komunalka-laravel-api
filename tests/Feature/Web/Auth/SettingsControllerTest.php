<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('SettingsController', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    it('renders settings page with profile tab', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('settings'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->has('tab')
                ->where('tab', 'profile')
                ->has('connectedProviders'),
            );
    });

    it('renders settings with specific tab param', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('settings', ['tab' => 'security']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->where('tab', 'security'),
            );
    });

    it('updates user profile', function (): void {
        $response = $this->actingAs($this->user)
            ->put(route('settings.profile'), [
                'first_name' => 'Тарас',
                'last_name' => 'Шевченко',
                'phone_number' => '+380501234567',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->user->refresh();

        expect($this->user->first_name)->toBe('Тарас')
            ->and($this->user->last_name)->toBe('Шевченко')
            ->and($this->user->phone_number)->toBe('+380501234567');
    });

    it('updates user password', function (): void {
        $response = $this->actingAs($this->user)
            ->put(route('settings.password'), [
                'current_password' => 'password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');
    });

    it('shows error for wrong current password', function (): void {
        $response = $this->actingAs($this->user)
            ->put(route('settings.password'), [
                'current_password' => 'wrong-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ]);

        $response->assertSessionHasErrors('current_password');
    });

    it('deletes user account', function (): void {
        $userId = $this->user->getKey();

        $response = $this->actingAs($this->user)
            ->delete(route('settings.account'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    });

    it('requires authentication', function (): void {
        $this->get(route('settings'))
            ->assertRedirect(route('login'));

        $this->put(route('settings.profile'))
            ->assertRedirect(route('login'));

        $this->put(route('settings.password'))
            ->assertRedirect(route('login'));

        $this->delete(route('settings.account'))
            ->assertRedirect(route('login'));
    });
});
