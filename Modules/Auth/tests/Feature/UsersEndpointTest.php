<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->admin()->create();
});

describe('GET /api/v1/users (index)', function () {
    it('returns all users for admin', function () {
        $address = Address::factory()->create();
        $this->admin->addresses()->attach($address->getKey(), ['is_primary' => true]);

        User::factory()->count(2)->create();

        $this->actingAs($this->admin, 'api')
            ->getJson(route('api.users.index'))
            ->assertSuccessful()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure(['data' => [['id', 'username', 'first_name', 'last_name', 'email', 'role']]]);
    });

    it('includes addresses in response when user has them', function () {
        $address = Address::factory()->create();
        $this->admin->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson(route('api.users.index'))
            ->assertSuccessful();

        $userData = collect($response->json('data'))->firstWhere('id', $this->admin->getKey());

        expect($userData['addresses'])->toHaveCount(1);
    });

    it('returns 403 for regular user', function () {
        $this->actingAs($this->user, 'api')
            ->getJson(route('api.users.index'))
            ->assertForbidden();
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(route('api.users.index'))->assertUnauthorized();
    });
});

describe('GET /api/v1/users/{id} (show)', function () {
    it('returns own profile for regular user', function () {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.users.show', $this->user->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $this->user->getKey())
            ->assertJsonPath('data.username', $this->user->username)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'username',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'role',
                    'auth_provider',
                    'email_verified',
                    'last_login_at',
                    'created_at',
                    'updated_at',
                    'addresses',
                    'avatar_optimized_url',
                    'avatar_thumbnail_url',
                ],
            ]);
    });

    it('returns 403 when viewing another user', function () {
        $otherUser = User::factory()->create();

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.users.show', $otherUser->getKey()))
            ->assertForbidden();
    });

    it('admin can view any user', function () {
        $this->actingAs($this->admin, 'api')
            ->getJson(route('api.users.show', $this->user->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $this->user->getKey());
    });

    it('returns 404 for non-existent user', function () {
        $this->actingAs($this->admin, 'api')
            ->getJson(route('api.users.show', 99999))
            ->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(route('api.users.show', $this->user->getKey()))->assertUnauthorized();
    });
});

describe('POST /api/v1/users (store)', function () {
    beforeEach(function () {
        $this->validPayload = [
            'username' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'User',
            'phone_number' => '+380501234567',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ];
    });

    it('admin creates a new user', function () {
        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.users.store'), $this->validPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.username', 'newuser')
            ->assertJsonPath('data.email', 'newuser@example.com')
            ->assertJsonPath('data.first_name', 'New')
            ->assertJsonPath('data.last_name', 'User');

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    });

    it('returns 403 for regular user', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.users.store'), $this->validPayload)
            ->assertForbidden();
    });

    it('validates unique email', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $payload = array_merge($this->validPayload, ['email' => 'taken@example.com']);

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.users.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('validates unique username', function () {
        User::factory()->create(['username' => 'taken']);

        $payload = array_merge($this->validPayload, ['username' => 'taken']);

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.users.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    });

    it('validates required fields', function (string $field) {
        $payload = $this->validPayload;
        unset($payload[$field]);

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.users.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['username', 'first_name', 'last_name', 'email', 'password', 'role']);

    it('validates password confirmation', function () {
        $payload = array_merge($this->validPayload, [
            'password_confirmation' => 'different-password',
        ]);

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.users.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('validates role must be user or admin', function () {
        $payload = array_merge($this->validPayload, ['role' => 'superadmin']);

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.users.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    });

    it('returns 401 when unauthenticated', function () {
        $this->postJson(route('api.users.store'), $this->validPayload)->assertUnauthorized();
    });
});

describe('PUT /api/v1/users/{id} (update)', function () {
    it('updates own profile fields', function () {
        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'phone_number' => '+380509999999',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.last_name', 'Name')
            ->assertJsonPath('data.phone_number', '+380509999999');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    });

    it('returns 403 when updating another user', function () {
        $otherUser = User::factory()->create();

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $otherUser->getKey()), [
                'first_name' => 'Hacked',
            ])
            ->assertForbidden();
    });

    it('admin can update any user', function () {
        $this->actingAs($this->admin, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), [
                'first_name' => 'AdminUpdated',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.first_name', 'AdminUpdated');
    });

    it('uploads avatar', function () {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('avatar.jpg', 800, 800);

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), [
                'avatar' => $avatar,
            ])
            ->assertSuccessful();

        expect($this->user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
    });

    it('rejects non-image avatar', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), [
                'avatar' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    });

    it('rejects avatar exceeding max size', function () {
        $avatar = UploadedFile::fake()->image('large.jpg')->size(3000);

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), [
                'avatar' => $avatar,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    });

    it('changes password with valid current password', function () {
        $user = User::factory()->create(['password' => 'oldpassword123']);

        $this->actingAs($user, 'api')
            ->putJson(route('api.users.update', $user->getKey()), [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertSuccessful();
    });

    it('rejects password change with invalid current password', function () {
        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), [
                'current_password' => 'wrong-password',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    });

    it('requires current password when setting new password', function () {
        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), [
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    });

    it('returns 404 for non-existent user', function () {
        $this->actingAs($this->admin, 'api')
            ->putJson(route('api.users.update', 99999), ['first_name' => 'Test'])
            ->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        $this->putJson(route('api.users.update', $this->user->getKey()), ['first_name' => 'Test'])
            ->assertUnauthorized();
    });
});

describe('DELETE /api/v1/users/{id} (destroy)', function () {
    it('admin deletes a user', function () {
        $userToDelete = User::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->deleteJson(route('api.users.destroy', $userToDelete->getKey()))
            ->assertSuccessful()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->getKey()]);
    });

    it('returns 403 for regular user', function () {
        $userToDelete = User::factory()->create();

        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.users.destroy', $userToDelete->getKey()))
            ->assertForbidden();
    });

    it('regular user cannot delete self', function () {
        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.users.destroy', $this->user->getKey()))
            ->assertForbidden();
    });

    it('returns 404 for non-existent user', function () {
        $this->actingAs($this->admin, 'api')
            ->deleteJson(route('api.users.destroy', 99999))
            ->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        $this->deleteJson(route('api.users.destroy', $this->user->getKey()))->assertUnauthorized();
    });
});

describe('GET /api/v1/users/{id}/avatar (public)', function () {
    it('returns avatar image when user has one', function () {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('avatar.jpg', 800, 800);
        $this->user->addMedia($avatar)->toMediaCollection('avatar');

        $media = $this->user->getFirstMedia('avatar');
        $conversionPath = $media->getPath('optimized');
        if (! file_exists(dirname($conversionPath))) {
            mkdir(dirname($conversionPath), 0755, true);
        }
        copy($media->getPath(), $conversionPath);

        $this->getJson(route('api.users.avatar', $this->user->getKey()))
            ->assertSuccessful();
    });

    it('returns 404 when user has no avatar', function () {
        $this->getJson(route('api.users.avatar', $this->user->getKey()))
            ->assertNotFound();
    });

    it('returns 404 for non-existent user', function () {
        $this->getJson(route('api.users.avatar', 99999))
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $this->getJson(route('api.users.avatar', $this->user->getKey()))
            ->assertNotFound();
    });
});

describe('GET /api/v1/users/{id}/avatar/thumbnail (public)', function () {
    it('returns thumbnail image when user has one', function () {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('avatar.jpg', 800, 800);
        $this->user->addMedia($avatar)->toMediaCollection('avatar');

        $media = $this->user->getFirstMedia('avatar');
        $conversionPath = $media->getPath('thumbnail');
        if (! file_exists(dirname($conversionPath))) {
            mkdir(dirname($conversionPath), 0755, true);
        }
        copy($media->getPath(), $conversionPath);

        $this->getJson(route('api.users.avatar.thumbnail', $this->user->getKey()))
            ->assertSuccessful();
    });

    it('returns 404 when user has no avatar', function () {
        $this->getJson(route('api.users.avatar.thumbnail', $this->user->getKey()))
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $this->getJson(route('api.users.avatar.thumbnail', $this->user->getKey()))
            ->assertNotFound();
    });
});
