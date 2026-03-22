<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Illuminate\Http\Request;
use Modules\Billing\Http\Resources\TariffResource;
use Modules\Meter\Models\MeterReading;

/** @mixin MeterReading */
class MeterReadingResource extends InertiaJsonApiResource
{
    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
            'reading_value' => $this->reading_value,
            'reading_date' => $this->reading_date,
            'previous_reading_value' => $this->previous_reading_value,
            'consumption' => $this->consumption,
            'notes' => $this->notes,
            'is_estimated' => $this->is_estimated,
            'photos' => $this->getMedia('photos')->map(fn ($media) => [
                'id' => $media->id,
                'original_url' => $media->getUrl(),
                'optimized_url' => $media->getUrl('optimized'),
                'thumbnail_url' => $media->getUrl('thumbnail'),
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /** @return array<string, string> */
    public function toRelationships(Request $request): array
    {
        return [
            'meter' => MeterResource::class,
            'tariff' => TariffResource::class,
        ];
    }
}
