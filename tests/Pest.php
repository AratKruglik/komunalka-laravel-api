<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser', '../Modules/*/tests/Feature', '../Modules/*/tests/Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function mockWebOAuthDriver(string $provider = 'google', array $overrides = []): void
{
    $defaults = [
        'id' => '12345',
        'email' => 'oauth@example.com',
        'name' => 'OAuth User',
        'nickname' => 'oauthuser',
        'given_name' => 'OAuth',
        'family_name' => 'User',
    ];

    $data = array_merge($defaults, $overrides);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn($data['id']);
    $socialiteUser->shouldReceive('getEmail')->andReturn($data['email']);
    $socialiteUser->shouldReceive('getName')->andReturn($data['name']);
    $socialiteUser->shouldReceive('getNickname')->andReturn($data['nickname']);
    $socialiteUser->user = [
        'given_name' => $data['given_name'],
        'family_name' => $data['family_name'],
    ];

    $driver = Mockery::mock();
    $driver->shouldReceive('stateless')->andReturnSelf();
    $driver->shouldReceive('userFromToken')->andReturn($socialiteUser);
    $driver->shouldReceive('user')->andReturn($socialiteUser);

    $redirect = new RedirectResponse(
        "https://accounts.example.com/authorize?provider={$provider}",
    );
    $driver->shouldReceive('redirect')->andReturn($redirect);

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}
