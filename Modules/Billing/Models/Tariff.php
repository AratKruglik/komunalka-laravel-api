<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Database\Factories\TariffFactory;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

class Tariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_id',
        'utility_type_id',
        'currency_id',
        'name',
        'base_rate',
        'service_fee',
        'effective_from',
        'effective_to',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'base_rate' => 'decimal:4',
            'service_fee' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /** @return BelongsTo<ServiceProvider, $this> */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /** @return BelongsTo<UtilityType, $this> */
    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(UtilityType::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @param Builder<self> $query */
    public function scopeEffectiveAt(Builder $query, CarbonImmutable $date): void
    {
        $query->where('effective_from', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    protected static function newFactory(): TariffFactory
    {
        return TariffFactory::new();
    }
}
