<?php

declare(strict_types=1);

namespace Modules\Shared\DTO;

use Modules\Shared\Http\Requests\UpdateCurrencyRequest;

final readonly class UpdateCurrencyData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
    ) {}

    public static function fromRequest(UpdateCurrencyRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            symbol: $request->validated('symbol'),
        );
    }
}
