<?php

declare(strict_types=1);

namespace Modules\Address\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Illuminate\Http\Request;
use Modules\Address\Models\Address;

/** @mixin Address */
class AddressResource extends InertiaJsonApiResource
{
    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
            'city' => $this->city,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'apartment_number' => $this->apartment_number,
            'zip_code' => $this->zip_code,
            'notes' => $this->notes,
            'is_primary' => (bool) ($this->pivot?->is_primary ?? $this->is_primary ?? false),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /** @return array<string, string> */
    public function toRelationships(Request $request): array
    {
        return [
            'region' => RegionResource::class,
            'addressType' => AddressTypeResource::class,
        ];
    }
}
