<?php

declare(strict_types=1);

namespace Modules\Shared\DTOs;

use Modules\Shared\Http\Requests\StoreCurrencyRequest;

final readonly class CreateCurrencyData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
    ) {}

    public static function fromRequest(StoreCurrencyRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            symbol: $request->validated('symbol'),
        );
    }
}
