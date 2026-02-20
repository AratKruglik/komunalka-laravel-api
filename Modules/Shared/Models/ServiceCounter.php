<?php

declare(strict_types=1);

namespace Modules\Shared\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Shared\Database\Factories\ServiceCounterFactory;

class ServiceCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'address_id',
        'service_category_id',
        'serial_number',
        'service_counter_measurement_id',
    ];

    /** @return BelongsTo<ServiceCategory, $this> */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /** @return BelongsTo<ServiceCounterMeasurement, $this> */
    public function serviceCounterMeasurement(): BelongsTo
    {
        return $this->belongsTo(ServiceCounterMeasurement::class);
    }

    /** @return HasMany<ServiceCounterValue, $this> */
    public function serviceCounterValues(): HasMany
    {
        return $this->hasMany(ServiceCounterValue::class);
    }

    protected static function newFactory(): ServiceCounterFactory
    {
        return ServiceCounterFactory::new();
    }
}
