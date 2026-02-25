<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\RefreshTokenData;
use Modules\Auth\Models\RefreshToken;

class RevokeToken
{
    use AsAction;

    public function handle(RefreshTokenData $data): void
    {
        $refreshToken = RefreshToken::query()->where('token', $data->refreshToken)->first();
        $refreshToken?->update(['is_revoked' => true]);
    }
}
