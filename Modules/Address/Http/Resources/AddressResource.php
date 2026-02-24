<?php

declare(strict_types=1);

namespace Modules\Address\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Address\Models\Address;

/** @mixin Address */
class AddressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'city' => $this->city,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'apartment_number' => $this->apartment_number,
            'zip_code' => $this->zip_code,
            'notes' => $this->notes,
            'is_primary' => (bool) ($this->pivot?->is_primary ?? $this->is_primary ?? false),
            'region' => new RegionResource($this->whenLoaded('region')),
            'address_type' => new AddressTypeResource($this->whenLoaded('addressType')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
