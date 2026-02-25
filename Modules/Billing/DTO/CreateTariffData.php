<?php

declare(strict_types=1);

namespace Modules\Billing\DTO;

final readonly class CreateTariffData
{
    public function __construct(
        public int $utilityTypeId,
        public int $currencyId,
        public string $name,
        public string $baseRate,
        public string $serviceFee,
        public string $effectiveFrom,
        public ?string $effectiveTo,
        public ?string $notes,
    ) {}
}
