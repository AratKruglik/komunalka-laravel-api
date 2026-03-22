<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Illuminate\Http\Request;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Http\Resources\CurrencyResource;
use Modules\Shared\Http\Resources\UtilityTypeResource;

/** @mixin Tariff */
class TariffResource extends InertiaJsonApiResource
{
    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'base_rate' => $this->base_rate,
            'service_fee' => $this->service_fee,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /** @return array<string, string> */
    public function toRelationships(Request $request): array
    {
        return [
            'utilityType' => UtilityTypeResource::class,
            'currency' => CurrencyResource::class,
        ];
    }
}
