<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;

class GetConsumptionHistory
{
    use AsAction;

    /**
     * @return Collection<int, array{month: string, utilityType: string, value: float}>
     */
    public function handle(User $user, int $months = 6): Collection
    {
        $addressIds = $user->addresses()->pluck('addresses.id');

        $startDate = CarbonImmutable::now()->subMonths($months)->startOfMonth();

        return DB::table('meter_readings')
            ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
            ->join('utility_types', 'meters.utility_type_id', '=', 'utility_types.id')
            ->whereIn('meters.address_id', $addressIds)
            ->where('meter_readings.reading_date', '>=', $startDate)
            ->select([
                DB::raw("to_char(meter_readings.reading_date, 'YYYY-MM') as month"),
                'utility_types.slug as utility_type',
                DB::raw('SUM(meter_readings.consumption) as value'),
            ])
            ->groupBy('month', 'utility_types.slug')
            ->orderBy('month')
            ->get()
            ->map(fn (object $row): array => [
                'month' => (string) $row->month,
                'utilityType' => (string) $row->utility_type,
                'value' => (float) $row->value,
            ]);
    }
}
