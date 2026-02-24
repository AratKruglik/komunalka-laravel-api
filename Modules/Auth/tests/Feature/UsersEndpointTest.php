<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1/users';
    $this->user = User::factory()->create();
});

describe('GET /api/v1/users (index)', function () {
    it('returns all users with addresses', function () {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        User::factory()->count(2)->create();

        $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'username', 'first_name', 'last_name', 'email', 'role']]]);
    });

    it('includes addresses in response when user has them', function () {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful();

        $userData = collect($response->json('data'))->firstWhere('id', $this->user->getKey());

        expect($userData['addresses'])->toHaveCount(1);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson($this->baseUrl)->assertUnauthorized();
    });
});

describe('GET /api/v1/users/{id} (show)', function () {
    it('returns user with addresses and avatar urls', function () {
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/{$this->user->getKey()}")
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

    it('returns 404 for non-existent user', function () {
        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/99999")
            ->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson("{$this->baseUrl}/{$this->user->getKey()}")->assertUnauthorized();
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

    it('creates a new user', function () {
        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $this->validPayload)
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

    it('validates unique email', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $payload = array_merge($this->validPayload, ['email' => 'taken@example.com']);

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('validates unique username', function () {
        User::factory()->create(['username' => 'taken']);

        $payload = array_merge($this->validPayload, ['username' => 'taken']);

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    });

    it('validates required fields', function (string $field) {
        $payload = $this->validPayload;
        unset($payload[$field]);

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['username', 'first_name', 'last_name', 'email', 'password', 'role']);

    it('validates password confirmation', function () {
        $payload = array_merge($this->validPayload, [
            'password_confirmation' => 'different-password',
        ]);

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('validates role must be user or admin', function () {
        $payload = array_merge($this->validPayload, ['role' => 'superadmin']);

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    });

    it('returns 401 when unauthenticated', function () {
        $this->postJson($this->baseUrl, $this->validPayload)->assertUnauthorized();
    });
});

describe('PUT /api/v1/users/{id} (update)', function () {
    it('updates user profile fields', function () {
        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$this->user->getKey()}", [
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

    it('uploads avatar', function () {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('avatar.jpg', 800, 800);

        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$this->user->getKey()}", [
                'avatar' => $avatar,
            ])
            ->assertSuccessful();

        expect($this->user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
    });

    it('rejects non-image avatar', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$this->user->getKey()}", [
                'avatar' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    });

    it('rejects avatar exceeding max size', function () {
        $avatar = UploadedFile::fake()->image('large.jpg')->size(3000);

        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$this->user->getKey()}", [
                'avatar' => $avatar,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    });

    it('changes password with valid current password', function () {
        $user = User::factory()->create(['password' => 'oldpassword123']);

        $this->actingAs($user, 'api')
            ->putJson("{$this->baseUrl}/{$user->getKey()}", [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertSuccessful();
    });

    it('rejects password change with invalid current password', function () {
        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$this->user->getKey()}", [
                'current_password' => 'wrong-password',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    });

    it('requires current password when setting new password', function () {
        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$this->user->getKey()}", [
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    });

    it('returns 404 for non-existent user', function () {
        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/99999", ['first_name' => 'Test'])
            ->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        $this->putJson("{$this->baseUrl}/{$this->user->getKey()}", ['first_name' => 'Test'])
            ->assertUnauthorized();
    });
});

describe('DELETE /api/v1/users/{id} (destroy)', function () {
    it('deletes a user', function () {
        $userToDelete = User::factory()->create();

        $this->actingAs($this->user, 'api')
            ->deleteJson("{$this->baseUrl}/{$userToDelete->getKey()}")
            ->assertSuccessful()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->getKey()]);
    });

    it('returns 404 for non-existent user', function () {
        $this->actingAs($this->user, 'api')
            ->deleteJson("{$this->baseUrl}/99999")
            ->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        $this->deleteJson("{$this->baseUrl}/{$this->user->getKey()}")->assertUnauthorized();
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

        $this->getJson("{$this->baseUrl}/{$this->user->getKey()}/avatar")
            ->assertSuccessful();
    });

    it('returns 404 when user has no avatar', function () {
        $this->getJson("{$this->baseUrl}/{$this->user->getKey()}/avatar")
            ->assertNotFound();
    });

    it('returns 404 for non-existent user', function () {
        $this->getJson("{$this->baseUrl}/99999/avatar")
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $this->getJson("{$this->baseUrl}/{$this->user->getKey()}/avatar")
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

        $this->getJson("{$this->baseUrl}/{$this->user->getKey()}/avatar/thumbnail")
            ->assertSuccessful();
    });

    it('returns 404 when user has no avatar', function () {
        $this->getJson("{$this->baseUrl}/{$this->user->getKey()}/avatar/thumbnail")
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $this->getJson("{$this->baseUrl}/{$this->user->getKey()}/avatar/thumbnail")
            ->assertNotFound();
    });
});
