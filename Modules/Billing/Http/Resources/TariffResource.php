<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Http\Resources\CurrencyResource;
use Modules\Shared\Http\Resources\UtilityTypeResource;

/** @mixin Tariff */
class TariffResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_rate' => $this->base_rate,
            'service_fee' => $this->service_fee,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'notes' => $this->notes,
            'utility_type' => new UtilityTypeResource($this->whenLoaded('utilityType')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
