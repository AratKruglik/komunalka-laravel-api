<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Meter\Models\Meter;
use Modules\Shared\Http\Resources\UtilityTypeResource;

/** @mixin Meter */
class MeterResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'name' => $this->name,
            'description' => $this->description,
            'model_name' => $this->model_name,
            'location' => $this->location,
            'installation_date' => $this->installation_date,
            'initial_reading' => $this->initial_reading,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'address_id' => $this->address_id,
            'utility_type' => new UtilityTypeResource($this->whenLoaded('utilityType')),
            'service_provider' => new ServiceProviderResource($this->whenLoaded('serviceProvider')),
            'photo_url' => $this->getFirstMediaUrl('photo', 'optimized') ?: null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
