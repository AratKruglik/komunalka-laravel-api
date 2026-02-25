<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\DTO\TariffCalculationResult;
use Modules\Billing\Models\Tariff;

class CalculateTariffCost
{
    use AsAction;

    public function handle(Tariff $tariff, string $consumption, int $meterId, string $meterName, string $unit): TariffCalculationResult
    {
        $usageCost = bcmul($consumption, $tariff->base_rate, 4);

        return new TariffCalculationResult(
            meterId: $meterId,
            meterName: $meterName,
            consumption: $consumption,
            unit: $unit,
            baseRate: $tariff->base_rate,
            serviceFee: $tariff->service_fee,
            totalCost: bcadd($usageCost, $tariff->service_fee, 4),
            currencyCode: $tariff->currency->code,
            currencySymbol: $tariff->currency->symbol,
        );
    }
}
