<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\OAuthLoginData;
use Modules\Auth\Models\User;
use Modules\Auth\Services\OAuthService;

class OAuthLogin
{
    use AsAction;

    public function __construct(
        private OAuthService $oAuthService,
    ) {}

    public function handle(OAuthLoginData $data): User
    {
        $socialiteUser = $this->oAuthService->authenticateWithToken($data->provider, $data->token);
        $user = $this->oAuthService->findOrCreateUser($socialiteUser, $data->provider);

        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
