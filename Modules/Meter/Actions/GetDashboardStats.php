<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;

class GetDashboardStats
{
    use AsAction;

    /**
     * @param  Collection<int, int>  $addressIds
     * @return array{
     *     addressCount: int,
     *     meterCount: int,
     *     lastReadingDate: string|null,
     *     totalMonthlyConsumption: float,
     * }
     */
    public function handle(Collection $addressIds): array
    {
        $meterIds = Meter::query()
            ->whereIn('address_id', $addressIds)
            ->active()
            ->pluck('id');

        $lastReading = MeterReading::query()
            ->whereIn('meter_id', $meterIds)
            ->orderByDesc('reading_date')
            ->first(['reading_date']);

        $startOfMonth = CarbonImmutable::now()->startOfMonth();

        $totalMonthlyConsumption = MeterReading::query()
            ->whereIn('meter_id', $meterIds)
            ->where('reading_date', '>=', $startOfMonth)
            ->sum('consumption');

        return [
            'addressCount' => $addressIds->count(),
            'meterCount' => $meterIds->count(),
            'lastReadingDate' => $lastReading?->reading_date?->toDateString(),
            'totalMonthlyConsumption' => (float) $totalMonthlyConsumption,
        ];
    }
}
