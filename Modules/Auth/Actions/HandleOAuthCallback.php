<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\OAuthCallbackData;
use Modules\Auth\Models\User;
use Modules\Auth\Services\OAuthService;

class HandleOAuthCallback
{
    use AsAction;

    public function __construct(
        private OAuthService $oAuthService,
    ) {}

    public function handle(OAuthCallbackData $data): User
    {
        if ($data->error) {
            throw ValidationException::withMessages([
                'error' => [$data->errorDescription ?? $data->error],
            ]);
        }

        $socialiteUser = $this->oAuthService->handleCallback($data->provider, (string) $data->code, $data->state);
        $user = $this->oAuthService->findOrCreateUser($socialiteUser, $data->provider);

        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
