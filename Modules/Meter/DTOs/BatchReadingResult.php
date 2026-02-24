<?php

declare(strict_types=1);

namespace Modules\Meter\DTOs;

use Modules\Billing\DTOs\TariffCalculationResult;
use Modules\Meter\Models\MeterReading;

final readonly class BatchReadingResult
{
    /**
     * @param  array<int, MeterReading>  $readings
     * @param  array<int, TariffCalculationResult>  $tariffCalculations
     */
    public function __construct(
        public array $readings,
        public array $tariffCalculations,
    ) {}
}
