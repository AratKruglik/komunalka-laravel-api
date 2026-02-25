<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Http\Requests\OAuthLoginRequest;

final readonly class OAuthLoginData
{
    public function __construct(
        public AuthProvider $provider,
        public string $token,
    ) {}

    public static function fromRequest(OAuthLoginRequest $request): self
    {
        return new self(
            provider: AuthProvider::from($request->validated('provider')),
            token: $request->validated('token'),
        );
    }
}
