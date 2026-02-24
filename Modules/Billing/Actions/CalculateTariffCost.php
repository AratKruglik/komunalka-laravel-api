<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\DTOs\TariffCalculationResult;
use Modules\Billing\Models\Tariff;

class CalculateTariffCost
{
    use AsAction;

    public function handle(Tariff $tariff, string $consumption, int $meterId, string $meterName, string $unit): TariffCalculationResult
    {
        $baseRate = $tariff->base_rate;
        $serviceFee = $tariff->service_fee;

        $usageCost = bcmul($consumption, $baseRate, 4);
        $totalCost = bcadd($usageCost, $serviceFee, 4);

        return new TariffCalculationResult(
            meterId: $meterId,
            meterName: $meterName,
            consumption: $consumption,
            unit: $unit,
            baseRate: $baseRate,
            serviceFee: $serviceFee,
            totalCost: $totalCost,
            currencyCode: $tariff->currency->code,
            currencySymbol: $tariff->currency->symbol,
        );
    }
}
