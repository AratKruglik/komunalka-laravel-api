<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Meter\DTOs\BatchReadingResult;

/** @mixin BatchReadingResult */
class BatchMeterReadingResponse extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'readings' => MeterReadingResource::collection($this->resource->readings),
            'tariff_calculations' => TariffCalculationResource::collection($this->resource->tariffCalculations),
        ];
    }
}
