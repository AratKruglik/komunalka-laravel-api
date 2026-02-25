<?php

declare(strict_types=1);

namespace Modules\Billing\DTO;

final readonly class TariffCalculationResult
{
    public function __construct(
        public int $meterId,
        public string $meterName,
        public string $consumption,
        public string $unit,
        public string $baseRate,
        public string $serviceFee,
        public string $totalCost,
        public string $currencyCode,
        public string $currencySymbol,
    ) {}
}
