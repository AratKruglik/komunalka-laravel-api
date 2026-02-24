<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Modules\Auth\Http\Requests\RefreshTokenRequest;

final readonly class RefreshTokenData
{
    public function __construct(
        public string $refreshToken,
    ) {}

    public static function fromRequest(RefreshTokenRequest $request): self
    {
        return new self(
            refreshToken: $request->validated('refresh_token'),
        );
    }
}
