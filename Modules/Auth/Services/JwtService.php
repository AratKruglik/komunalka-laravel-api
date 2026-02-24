<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use Modules\Auth\Models\RefreshToken;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\RefreshTokenRepositoryInterface;

class JwtService
{
    public function __construct(private RefreshTokenRepositoryInterface $refreshTokenRepository) {}

    /** @return array<string, mixed> */
    public function generateTokenPair(User $user): array
    {
        $jwt = auth('api')->login($user);

        /** @var RefreshToken $refreshTokenRecord */
        $refreshTokenRecord = $this->refreshTokenRepository->create([
            'user_id' => $user->getKey(),
            'token' => Str::random(64),
            'expiry_date' => now()->addDays(config('auth.refresh_token_ttl_days', 7)),
            'is_used' => false,
            'is_revoked' => false,
        ]);

        return [
            'user' => $user,
            'access_token' => $jwt,
            'refresh_token' => $refreshTokenRecord->token,
            'token_type' => 'bearer',
            'expires_in' => $this->getTokenExpiration(),
        ];
    }

    /** @return array<string, mixed> */
    public function refreshTokenPair(RefreshToken $refreshToken): array
    {
        $refreshToken->update(['is_used' => true]);

        return $this->generateTokenPair($refreshToken->user);
    }

    public function getTokenExpiration(): int
    {
        return auth('api')->factory()->getTTL() * 60;
    }
}
