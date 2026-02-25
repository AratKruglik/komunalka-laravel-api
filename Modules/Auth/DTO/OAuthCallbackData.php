<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Http\Requests\OAuthCallbackRequest;

final readonly class OAuthCallbackData
{
    public function __construct(
        public AuthProvider $provider,
        public ?string $code,
        public ?string $state,
        public ?string $error,
        public ?string $errorDescription,
    ) {}

    public static function fromRequest(OAuthCallbackRequest $request): self
    {
        return new self(
            provider: AuthProvider::from($request->validated('provider')),
            code: $request->validated('code'),
            state: $request->validated('state'),
            error: $request->validated('error'),
            errorDescription: $request->validated('error_description'),
        );
    }
}
