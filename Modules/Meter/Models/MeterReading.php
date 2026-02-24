<?php

declare(strict_types=1);

namespace Modules\Meter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Database\Factories\MeterReadingFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MeterReading extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'meter_id',
        'reading_value',
        'reading_date',
        'previous_reading_value',
        'consumption',
        'notes',
        'is_estimated',
        'tariff_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reading_value' => 'float',
            'reading_date' => 'date',
            'previous_reading_value' => 'float',
            'consumption' => 'float',
            'is_estimated' => 'boolean',
        ];
    }

    /** @return BelongsTo<Meter, $this> */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    /** @return BelongsTo<Tariff, $this> */
    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->width(800)
            ->format('jpg')
            ->quality(85)
            ->queued();

        $this->addMediaConversion('thumbnail')
            ->width(200)
            ->format('jpg')
            ->quality(85)
            ->queued();
    }

    protected static function newFactory(): MeterReadingFactory
    {
        return MeterReadingFactory::new();
    }
}
