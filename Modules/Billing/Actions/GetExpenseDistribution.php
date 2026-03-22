<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;

class GetExpenseDistribution
{
    use AsAction;

    /**
     * @return Collection<int, array{type: string, displayName: string, amount: float, percentage: float}>
     */
    public function handle(User $user): Collection
    {
        $addressIds = $user->addresses()->pluck('addresses.id');

        $startDate = CarbonImmutable::now()->subMonths(12)->startOfMonth();

        $expenses = DB::table('meter_readings')
            ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
            ->join('utility_types', 'meters.utility_type_id', '=', 'utility_types.id')
            ->leftJoin('tariffs', 'meter_readings.tariff_id', '=', 'tariffs.id')
            ->whereIn('meters.address_id', $addressIds)
            ->where('meter_readings.reading_date', '>=', $startDate)
            ->select([
                'utility_types.slug as type',
                'utility_types.display_name',
                DB::raw('SUM(meter_readings.consumption * COALESCE(tariffs.base_rate, 0) + COALESCE(tariffs.service_fee, 0)) as amount'),
            ])
            ->groupBy('utility_types.slug', 'utility_types.display_name')
            ->get();

        $totalAmount = $expenses->sum('amount');

        return $expenses->map(fn (object $row): array => [
            'type' => (string) $row->type,
            'displayName' => (string) $row->display_name,
            'amount' => round((float) $row->amount, 2),
            'percentage' => $totalAmount > 0
                ? round((float) $row->amount / $totalAmount * 100, 1)
                : 0.0,
        ]);
    }
}
