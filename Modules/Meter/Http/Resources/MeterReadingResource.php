<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Billing\Http\Resources\TariffResource;
use Modules\Meter\Models\MeterReading;

/** @mixin MeterReading */
class MeterReadingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reading_value' => $this->reading_value,
            'reading_date' => $this->reading_date,
            'previous_reading_value' => $this->previous_reading_value,
            'consumption' => $this->consumption,
            'notes' => $this->notes,
            'is_estimated' => $this->is_estimated,
            'meter' => new MeterResource($this->whenLoaded('meter')),
            'tariff' => new TariffResource($this->whenLoaded('tariff')),
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
}
