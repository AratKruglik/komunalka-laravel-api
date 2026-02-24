<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;
use Modules\Auth\Services\OAuthService;

class LinkOAuthProvider
{
    use AsAction;

    public function __construct(private OAuthService $oAuthService) {}

    public function handle(User $user, AuthProvider $provider, string $token): void
    {
        $socialiteUser = $this->oAuthService->authenticateWithToken($provider, $token);

        $user->update([
            'auth_provider' => $provider,
            'external_id' => $socialiteUser->getId(),
        ]);
    }
}
