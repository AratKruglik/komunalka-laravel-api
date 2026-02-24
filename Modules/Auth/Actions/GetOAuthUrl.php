<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Services\OAuthService;

class GetOAuthUrl
{
    use AsAction;

    public function __construct(private OAuthService $oAuthService) {}

    /** @return array<string, string> */
    public function handle(AuthProvider $provider): array
    {
        return $this->oAuthService->getAuthorizationUrl($provider);
    }
}
