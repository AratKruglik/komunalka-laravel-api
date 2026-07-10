<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Illuminate\Http\Request;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Meter\Models\Meter;
use Modules\Shared\Concerns\ResolvesMediaConversionUrls;
use Modules\Shared\Http\Resources\UtilityTypeResource;

/** @mixin Meter */
class MeterResource extends InertiaJsonApiResource
{
    use ResolvesMediaConversionUrls;

    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
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
            'photo' => $this->resolveMediaConversionUrls($this->getFirstMedia('photo')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /** @return array<string, string> */
    public function toRelationships(Request $request): array
    {
        return [
            'utilityType' => UtilityTypeResource::class,
            'serviceProvider' => ServiceProviderResource::class,
        ];
    }
}
