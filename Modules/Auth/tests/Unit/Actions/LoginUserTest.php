<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\LoginUser;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Models\User;

it('returns token pair for valid credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $data = new LoginData(email: 'john@example.com', password: 'password123');

    $result = app(LoginUser::class)->handle($data);

    expect($result)
        ->toHaveKeys(['user', 'access_token', 'refresh_token', 'token_type', 'expires_in'])
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['token_type'])->toBe('bearer');
});

it('throws validation exception for invalid password', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $data = new LoginData(email: 'john@example.com', password: 'wrong-password');

    app(LoginUser::class)->handle($data);
})->throws(ValidationException::class);

it('throws validation exception for non-existent email', function () {
    $data = new LoginData(email: 'nobody@example.com', password: 'password123');

    app(LoginUser::class)->handle($data);
})->throws(ValidationException::class);

it('rejects OAuth-only users', function () {
    User::factory()->oauthGoogle()->create([
        'email' => 'oauth@example.com',
    ]);

    $data = new LoginData(email: 'oauth@example.com', password: 'password123');

    app(LoginUser::class)->handle($data);
})->throws(ValidationException::class);
