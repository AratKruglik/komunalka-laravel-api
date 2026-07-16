<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;

describe('SettingsController', function (): void {
    beforeEach(function (): void {
        $this->withoutVite();
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

    it('uploads avatar via spoofed POST and stores it in the avatar collection', function (): void {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->post(route('settings.profile'), [
                '_method' => 'put',
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->user->refresh();

        expect($this->user->getMedia('avatar'))->toHaveCount(1)
            ->and($this->user->getFirstMedia('avatar')->collection_name)->toBe('avatar');

        $this->assertDatabaseHas('media', [
            'model_type' => User::class,
            'model_id' => $this->user->getKey(),
            'collection_name' => 'avatar',
        ]);
    });

    it('rejects avatar with disallowed mime type', function (): void {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->post(route('settings.profile'), [
                '_method' => 'put',
                'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('avatar');

        $this->assertDatabaseMissing('media', [
            'model_type' => User::class,
            'model_id' => $this->user->getKey(),
            'collection_name' => 'avatar',
        ]);
    });

    it('rejects oversized avatar', function (): void {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->post(route('settings.profile'), [
                '_method' => 'put',
                'avatar' => UploadedFile::fake()->image('big-avatar.jpg')->size(2049),
            ]);

        $response->assertSessionHasErrors('avatar');

        $this->assertDatabaseMissing('media', [
            'model_type' => User::class,
            'model_id' => $this->user->getKey(),
            'collection_name' => 'avatar',
        ]);
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

    it('deletes user account with an active remember token and does not resurrect the user', function (): void {
        $user = User::factory()->create([
            'remember_token' => Str::random(60),
        ]);
        $userId = $user->getKey();

        $response = $this->actingAs($user)
            ->delete(route('settings.account'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    });

    it('prevents login after account deletion with the same credentials', function (): void {
        $user = User::factory()->create([
            'email' => 'deleted-user@example.com',
            'remember_token' => Str::random(60),
        ]);

        $this->actingAs($user)
            ->delete(route('settings.account'), [
                'password' => 'password',
            ]);

        $this->assertGuest();

        $loginResponse = $this->post(route('login'), [
            'email' => 'deleted-user@example.com',
            'password' => 'password',
        ]);

        $loginResponse->assertSessionHasErrors();
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'deleted-user@example.com']);
    });

    it('rejects account deletion with a wrong password and keeps the user authenticated', function (): void {
        $user = User::factory()->create([
            'remember_token' => Str::random(60),
        ]);
        $userId = $user->getKey();

        $response = $this->actingAs($user)
            ->delete(route('settings.account'), [
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors('password');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['id' => $userId]);
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
