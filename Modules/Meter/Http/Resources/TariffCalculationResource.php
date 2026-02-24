<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Billing\DTOs\TariffCalculationResult;

/** @mixin TariffCalculationResult */
class TariffCalculationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'meter_id' => $this->resource->meterId,
            'meter_name' => $this->resource->meterName,
            'consumption' => $this->resource->consumption,
            'unit' => $this->resource->unit,
            'base_rate' => $this->resource->baseRate,
            'service_fee' => $this->resource->serviceFee,
            'total_cost' => $this->resource->totalCost,
            'currency_code' => $this->resource->currencyCode,
            'currency_symbol' => $this->resource->currencySymbol,
        ];
    }
}
