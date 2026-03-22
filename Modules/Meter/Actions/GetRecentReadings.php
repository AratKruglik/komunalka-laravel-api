<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;

class GetRecentReadings
{
    use AsAction;

    /**
     * @param  BaseCollection<int, int>  $addressIds
     * @return Collection<int, MeterReading>
     */
    public function handle(BaseCollection $addressIds, int $limit = 10): Collection
    {
        $meterIds = Meter::query()
            ->whereIn('address_id', $addressIds)
            ->pluck('id');

        return MeterReading::query()
            ->whereIn('meter_id', $meterIds)
            ->with(['meter.utilityType', 'meter.address', 'tariff'])
            ->orderByDesc('reading_date')
            ->limit($limit)
            ->get();
    }
}
