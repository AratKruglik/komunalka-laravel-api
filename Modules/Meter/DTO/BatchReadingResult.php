<?php

declare(strict_types=1);

namespace Modules\Meter\DTO;

use Modules\Billing\DTO\TariffCalculationResult;
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
