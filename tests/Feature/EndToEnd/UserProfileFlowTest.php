<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;

describe('E2E: user profile management', function () {
    it('updates profile, uploads avatar, changes password', function () {
        $user = User::factory()->create([
            'password' => 'original-password',
        ]);

        $this->actingAs($user, 'api')
            ->putJson(route('api.users.update', $user->getKey()), [
                'first_name' => 'Updated',
                'last_name' => 'Name',
            ])->assertSuccessful()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.last_name', 'Name');

        Storage::fake('public');
        $avatar = UploadedFile::fake()->image('avatar.jpg', 800, 800);

        $this->actingAs($user, 'api')
            ->putJson(route('api.users.update', $user->getKey()), [
                'avatar' => $avatar,
            ])->assertSuccessful();

        $this->actingAs($user, 'api')
            ->getJson(route('api.users.show', $user->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.first_name', 'Updated');

        $this->actingAs($user, 'api')
            ->putJson(route('api.users.update', $user->getKey()), [
                'current_password' => 'original-password',
                'new_password' => 'new-password123',
                'new_password_confirmation' => 'new-password123',
            ])->assertSuccessful();

        $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'new-password123',
        ])->assertSuccessful();

        $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'original-password',
        ])->assertUnprocessable();
    });
});
