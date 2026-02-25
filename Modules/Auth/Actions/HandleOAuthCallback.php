<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\OAuthCallbackData;
use Modules\Auth\Services\JwtService;
use Modules\Auth\Services\OAuthService;

class HandleOAuthCallback
{
    use AsAction;

    public function __construct(
        private OAuthService $oAuthService,
        private JwtService $jwtService,
    ) {}

    /** @return array<string, mixed> */
    public function handle(OAuthCallbackData $data): array
    {
        if ($data->error) {
            throw ValidationException::withMessages([
                'error' => [$data->errorDescription ?? $data->error],
            ]);
        }

        $socialiteUser = $this->oAuthService->handleCallback($data->provider, (string) $data->code, $data->state);
        $user = $this->oAuthService->findOrCreateUser($socialiteUser, $data->provider);

        $user->update(['last_login_at' => now()]);

        return $this->jwtService->generateTokenPair($user);
    }
}
