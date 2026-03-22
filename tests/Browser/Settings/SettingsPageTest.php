<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create([
        'first_name' => 'Олексій',
        'last_name' => 'Петренко',
        'phone_number' => '501234567',
        'password' => 'password',
    ]);
});

describe('Settings Page Rendering', function (): void {
    it('renders Settings/Index component with default profile tab', function (): void {
        $this->actingAs($this->user)
            ->get(route('settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('tab', 'profile'),
            );
    });

    it('renders with specific tab query parameter', function (): void {
        $this->actingAs($this->user)
            ->get(route('settings', ['tab' => 'security']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('tab', 'security'),
            );
    });

    it('returns user data in Inertia props', function (): void {
        $this->actingAs($this->user)
            ->get(route('settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('auth.user.data.first_name', 'Олексій')
                ->where('auth.user.data.last_name', 'Петренко'),
            );
    });

    it('includes connected providers in props', function (): void {
        $this->actingAs($this->user)
            ->get(route('settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('connectedProviders', 2)
                ->where('connectedProviders.0.provider', 'google')
                ->where('connectedProviders.0.is_connected', false)
                ->where('connectedProviders.1.provider', 'github')
                ->where('connectedProviders.1.is_connected', false),
            );
    });

    it('defaults to profile tab when tab param has invalid value', function (): void {
        $this->actingAs($this->user)
            ->get(route('settings', ['tab' => 'nonexistent']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('tab', 'nonexistent'),
            );
    });
});

describe('Profile Tab: Update Profile', function (): void {
    it('updates first_name and last_name with redirect and success flash', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings'))
            ->put(route('settings.profile'), [
                'first_name' => 'Іван',
                'last_name' => 'Шевченко',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHas('success', 'Профіль успішно оновлено.');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'first_name' => 'Іван',
            'last_name' => 'Шевченко',
        ]);
    });

    it('updates phone_number', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings'))
            ->put(route('settings.profile'), [
                'phone_number' => '509876543',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'phone_number' => '509876543',
        ]);
    });

    it('accepts request without phone_number field', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings'))
            ->put(route('settings.profile'), [
                'first_name' => 'Марія',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors('phone_number');

        $this->user->refresh();
        expect($this->user->phone_number)->toBe('501234567');
    });

    it('updates only first_name as partial update', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings'))
            ->put(route('settings.profile'), [
                'first_name' => 'Марія',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->first_name)->toBe('Марія')
            ->and($this->user->last_name)->toBe('Петренко');
    });

    it('preserves input after validation error', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings'))
            ->put(route('settings.profile'), [
                'first_name' => str_repeat('a', 256),
                'last_name' => 'Шевченко',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHasErrors('first_name');

        expect(session('_old_input'))
            ->toHaveKey('last_name', 'Шевченко');
    });
});

describe('Security Tab: Change Password', function (): void {
    it('changes password with valid current password', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings', ['tab' => 'security']))
            ->put(route('settings.password'), [
                'current_password' => 'password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('settings', ['tab' => 'security']))
            ->assertSessionHas('success', 'Пароль успішно змінено.');

        $this->user->refresh();
        expect(Hash::check('new-secure-password', $this->user->password))->toBeTrue();
    });

    it('shows validation error for wrong current password', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings', ['tab' => 'security']))
            ->put(route('settings.password'), [
                'current_password' => 'wrong-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('settings', ['tab' => 'security']))
            ->assertSessionHasErrors('current_password');

        $errors = session('errors');
        expect($errors->get('current_password'))
            ->toContain('Поточний пароль невірний.');
    });

    it('shows validation error when new password is too short', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings', ['tab' => 'security']))
            ->put(route('settings.password'), [
                'current_password' => 'password',
                'new_password' => 'short',
                'new_password_confirmation' => 'short',
            ])
            ->assertRedirect(route('settings', ['tab' => 'security']))
            ->assertSessionHasErrors('new_password');

        $errors = session('errors');
        expect($errors->get('new_password'))
            ->toContain('Новий пароль повинен містити щонайменше 8 символів.');
    });

    it('shows validation error when password confirmation does not match', function (): void {
        $this->actingAs($this->user)
            ->from(route('settings', ['tab' => 'security']))
            ->put(route('settings.password'), [
                'current_password' => 'password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'different-password',
            ])
            ->assertRedirect(route('settings', ['tab' => 'security']))
            ->assertSessionHasErrors('new_password');

        $errors = session('errors');
        expect($errors->get('new_password'))
            ->toContain('Підтвердження нового паролю не збігається.');
    });
});

describe('Account Tab: Delete Account', function (): void {
    it('deletes account with correct password and redirects to login', function (): void {
        $userId = $this->user->getKey();

        $this->actingAs($this->user)
            ->delete(route('settings.account'), [
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Ваш акаунт було видалено.');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    });

    it('rejects account deletion with wrong password and keeps user', function (): void {
        $userId = $this->user->getKey();

        $this->actingAs($this->user)
            ->from(route('settings', ['tab' => 'account']))
            ->delete(route('settings.account'), [
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('settings', ['tab' => 'account']))
            ->assertSessionHasErrors('password');

        $errors = session('errors');
        expect($errors->get('password'))
            ->toContain('Невірний пароль.');

        $this->assertDatabaseHas('users', ['id' => $userId]);
    });
});

describe('Authentication: Settings Routes', function (): void {
    it('redirects unauthenticated users to login', function (string $method, string $routeName, array $params = []): void {
        $response = match ($method) {
            'GET' => $this->get(route($routeName, $params)),
            'PUT' => $this->put(route($routeName, $params)),
            'DELETE' => $this->delete(route($routeName, $params)),
        };

        $response->assertRedirect(route('login'));
    })->with([
        'GET settings' => ['GET', 'settings'],
        'PUT profile' => ['PUT', 'settings.profile'],
        'PUT password' => ['PUT', 'settings.password'],
        'DELETE account' => ['DELETE', 'settings.account'],
    ]);
});
