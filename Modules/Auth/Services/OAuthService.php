<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class OAuthService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /** @return array<string, string> */
    public function getAuthorizationUrl(AuthProvider $provider): array
    {
        $state = Str::random(40);
        Cache::put("oauth_state_{$state}", true, 300);

        $driver = Socialite::driver($provider->value)->stateless();
        $url = $driver->redirect()->getTargetUrl();

        return ['url' => $url, 'state' => $state];
    }

    public function handleCallback(AuthProvider $provider, string $code, ?string $state): SocialiteUser
    {
        if ($state !== null) {
            $cacheKey = "oauth_state_{$state}";

            if (! Cache::has($cacheKey)) {
                throw ValidationException::withMessages(['state' => ['Invalid OAuth state.']]);
            }

            Cache::forget($cacheKey);
        }

        return Socialite::driver($provider->value)->stateless()->user();
    }

    public function authenticateWithToken(AuthProvider $provider, string $token): SocialiteUser
    {
        return Socialite::driver($provider->value)->stateless()->userFromToken($token);
    }

    public function findOrCreateUser(SocialiteUser $socialiteUser, AuthProvider $provider): User
    {
        $existingByExternalId = $this->userRepository->findByExternalId($provider, (string) $socialiteUser->getId());

        if ($existingByExternalId) {
            return $existingByExternalId;
        }

        $existingByEmail = $this->userRepository->findByEmail((string) $socialiteUser->getEmail());

        if ($existingByEmail) {
            $existingByEmail->update([
                'external_id' => $socialiteUser->getId(),
                'auth_provider' => $provider,
            ]);

            return $existingByEmail;
        }

        $username = $this->generateUniqueUsername((string) $socialiteUser->getName());

        /** @var User $user */
        $user = $this->userRepository->create([
            'username' => $username,
            'first_name' => $socialiteUser->user['given_name'] ?? explode(' ', (string) $socialiteUser->getName())[0] ?? $username,
            'last_name' => $socialiteUser->user['family_name'] ?? explode(' ', (string) $socialiteUser->getName())[1] ?? '',
            'email' => $socialiteUser->getEmail(),
            'auth_provider' => $provider,
            'external_id' => $socialiteUser->getId(),
            'email_verified' => true,
        ]);

        return $user;
    }

    public function generateUniqueUsername(string $baseName): string
    {
        $slug = Str::slug($baseName, '_');
        $slug = $slug ?: 'user';

        if (! User::query()->where('username', $slug)->exists()) {
            return $slug;
        }

        $counter = 1;

        do {
            $candidate = "{$slug}_{$counter}";
            $counter++;
        } while (User::query()->where('username', $candidate)->exists());

        return $candidate;
    }
}
