<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;
use Modules\Meter\Models\MeterReading;

class GetRecentReadings
{
    use AsAction;

    /** @return Collection<int, MeterReading> */
    public function handle(User $user, int $limit = 10): Collection
    {
        $addressIds = $user->addresses()->pluck('addresses.id');

        return MeterReading::query()
            ->whereHas('meter', fn ($q) => $q->whereIn('address_id', $addressIds))
            ->with(['meter.utilityType', 'meter.address', 'tariff'])
            ->orderByDesc('reading_date')
            ->limit($limit)
            ->get();
    }
}
