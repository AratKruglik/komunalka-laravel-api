<?php

declare(strict_types=1);

namespace Modules\Shared\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Shared\Database\Factories\ServiceCounterMeasurementFactory;

class ServiceCounterMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'measurement',
    ];

    /** @return HasMany<ServiceCounter, $this> */
    public function serviceCounters(): HasMany
    {
        return $this->hasMany(ServiceCounter::class);
    }

    protected static function newFactory(): ServiceCounterMeasurementFactory
    {
        return ServiceCounterMeasurementFactory::new();
    }
}
