<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\DTO\RegisterUserData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Models\User;

it('creates a user in the database', function () {
    $data = new RegisterUserData(
        username: 'johndoe',
        firstName: 'John',
        lastName: 'Doe',
        phoneNumber: '+380501234567',
        email: 'john@example.com',
        password: 'password123',
    );

    app(RegisterUser::class)->handle($data);

    $this->assertDatabaseHas('users', [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});

it('hashes the password', function () {
    $data = new RegisterUserData(
        username: 'johndoe',
        firstName: 'John',
        lastName: 'Doe',
        phoneNumber: '+380501234567',
        email: 'john@example.com',
        password: 'password123',
    );

    app(RegisterUser::class)->handle($data);

    $user = User::query()->where('email', 'john@example.com')->first();

    expect(Hash::check('password123', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('password123');
});

it('assigns default user role', function () {
    $data = new RegisterUserData(
        username: 'johndoe',
        firstName: 'John',
        lastName: 'Doe',
        phoneNumber: '+380501234567',
        email: 'john@example.com',
        password: 'password123',
    );

    app(RegisterUser::class)->handle($data);

    $user = User::query()->where('email', 'john@example.com')->first();

    expect($user->role)->toBe(UserRole::User)
        ->and($user->auth_provider)->toBe(AuthProvider::Local);
});

it('returns token pair with user', function () {
    $data = new RegisterUserData(
        username: 'johndoe',
        firstName: 'John',
        lastName: 'Doe',
        phoneNumber: '+380501234567',
        email: 'john@example.com',
        password: 'password123',
    );

    $result = app(RegisterUser::class)->handle($data);

    expect($result)
        ->toHaveKeys(['user', 'access_token', 'refresh_token', 'token_type', 'expires_in'])
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['token_type'])->toBe('bearer')
        ->and($result['access_token'])->toBeString()->not->toBeEmpty()
        ->and($result['refresh_token'])->toBeString()->not->toBeEmpty()
        ->and($result['expires_in'])->toBeInt()->toBeGreaterThan(0);
});
