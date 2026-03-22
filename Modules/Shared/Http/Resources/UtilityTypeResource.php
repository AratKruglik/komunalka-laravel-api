<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Illuminate\Http\Request;
use Modules\Shared\Models\UtilityType;

/** @mixin UtilityType */
class UtilityTypeResource extends InertiaJsonApiResource
{
    /** @var list<string> */
    public $attributes = [
        'slug',
        'display_name',
        'unit',
        'description',
        'is_active',
    ];

    /** @return array<string, mixed> */
    public function toMeta(Request $request): array
    {
        return [
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
