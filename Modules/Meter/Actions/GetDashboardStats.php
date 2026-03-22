<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;

class GetDashboardStats
{
    use AsAction;

    /**
     * @return array{
     *     addressCount: int,
     *     meterCount: int,
     *     lastReadingDate: string|null,
     *     totalMonthlyConsumption: float,
     * }
     */
    public function handle(User $user): array
    {
        $addressIds = $user->addresses()->pluck('addresses.id');

        $meterCount = Meter::query()
            ->whereIn('address_id', $addressIds)
            ->active()
            ->count();

        $lastReading = MeterReading::query()
            ->whereHas('meter', fn ($q) => $q->whereIn('address_id', $addressIds))
            ->orderByDesc('reading_date')
            ->first(['reading_date']);

        $startOfMonth = CarbonImmutable::now()->startOfMonth();

        $totalMonthlyConsumption = MeterReading::query()
            ->whereHas('meter', fn ($q) => $q->whereIn('address_id', $addressIds))
            ->where('reading_date', '>=', $startOfMonth)
            ->sum('consumption');

        return [
            'addressCount' => $addressIds->count(),
            'meterCount' => $meterCount,
            'lastReadingDate' => $lastReading?->reading_date?->toDateString(),
            'totalMonthlyConsumption' => (float) $totalMonthlyConsumption,
        ];
    }
}
