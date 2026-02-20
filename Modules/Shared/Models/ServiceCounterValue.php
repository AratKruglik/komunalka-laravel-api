<?php

declare(strict_types=1);

namespace Modules\Shared\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Database\Factories\ServiceCounterValueFactory;

class ServiceCounterValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_counter_id',
        'value',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => 'float',
        ];
    }

    /** @return BelongsTo<ServiceCounter, $this> */
    public function serviceCounter(): BelongsTo
    {
        return $this->belongsTo(ServiceCounter::class);
    }

    protected static function newFactory(): ServiceCounterValueFactory
    {
        return ServiceCounterValueFactory::new();
    }
}
