<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Illuminate\Http\Request;
use Modules\Billing\Models\ServiceProvider;
use Modules\Shared\Http\Resources\UtilityTypeResource;

/** @mixin ServiceProvider */
class ServiceProviderResource extends InertiaJsonApiResource
{
    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'is_active' => $this->is_active,
            'address_id' => $this->address_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /** @return array<string, string> */
    public function toRelationships(Request $request): array
    {
        return [
            'utilityType' => UtilityTypeResource::class,
            'tariffs' => TariffResource::class,
        ];
    }
}
