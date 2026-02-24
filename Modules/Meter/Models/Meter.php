<?php

declare(strict_types=1);

namespace Modules\Meter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Address\Models\Address;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Database\Factories\MeterFactory;
use Modules\Shared\Models\UtilityType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Meter extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'address_id',
        'utility_type_id',
        'service_provider_id',
        'serial_number',
        'name',
        'description',
        'model_name',
        'location',
        'installation_date',
        'initial_reading',
        'notes',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'initial_reading' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Address, $this> */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /** @return BelongsTo<UtilityType, $this> */
    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(UtilityType::class);
    }

    /** @return BelongsTo<ServiceProvider, $this> */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /** @return HasMany<MeterReading, $this> */
    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeForAddress(Builder $query, int $addressId): Builder
    {
        return $query->where('address_id', $addressId);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
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

    protected static function newFactory(): MeterFactory
    {
        return MeterFactory::new();
    }
}
