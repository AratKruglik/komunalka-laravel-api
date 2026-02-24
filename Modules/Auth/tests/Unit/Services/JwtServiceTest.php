<?php

declare(strict_types=1);

use Modules\Auth\Models\RefreshToken;
use Modules\Auth\Models\User;
use Modules\Auth\Services\JwtService;

beforeEach(function () {
    $this->jwtService = app(JwtService::class);
});

it('generates access token', function () {
    $user = User::factory()->create();

    $result = $this->jwtService->generateTokenPair($user);

    expect($result['access_token'])->toBeString()->not->toBeEmpty();
});

it('creates refresh token record in database', function () {
    $user = User::factory()->create();

    $result = $this->jwtService->generateTokenPair($user);

    $this->assertDatabaseHas('refresh_tokens', [
        'user_id' => $user->getKey(),
        'token' => $result['refresh_token'],
        'is_used' => false,
        'is_revoked' => false,
    ]);
});

it('returns correct token pair structure', function () {
    $user = User::factory()->create();

    $result = $this->jwtService->generateTokenPair($user);

    expect($result)
        ->toHaveKeys(['user', 'access_token', 'refresh_token', 'token_type', 'expires_in'])
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['token_type'])->toBe('bearer')
        ->and($result['expires_in'])->toBeInt()->toBeGreaterThan(0);
});

it('marks old token as used on refresh', function () {
    $user = User::factory()->create();
    $oldToken = RefreshToken::factory()->create(['user_id' => $user->getKey()]);

    $this->jwtService->refreshTokenPair($oldToken);

    $oldToken->refresh();

    expect($oldToken->is_used)->toBeTrue();
});

it('returns expiration in seconds', function () {
    $expiration = $this->jwtService->getTokenExpiration();

    expect($expiration)->toBeInt()->toBeGreaterThan(0);
});
