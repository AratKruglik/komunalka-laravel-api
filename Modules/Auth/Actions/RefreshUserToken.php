<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTOs\RefreshTokenData;
use Modules\Auth\Repositories\Contracts\RefreshTokenRepositoryInterface;
use Modules\Auth\Services\JwtService;

class RefreshUserToken
{
    use AsAction;

    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private JwtService $jwtService,
    ) {}

    /** @return array<string, mixed> */
    public function handle(RefreshTokenData $data): array
    {
        $refreshToken = $this->refreshTokenRepository->findValidByToken($data->refreshToken);

        if (! $refreshToken) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Invalid or expired refresh token.'],
            ]);
        }

        return $this->jwtService->refreshTokenPair($refreshToken);
    }
}
