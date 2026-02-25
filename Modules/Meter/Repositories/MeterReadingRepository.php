<?php

declare(strict_types=1);

namespace Modules\Meter\Repositories;

use App\Repositories\EloquentRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Modules\Meter\Models\MeterReading;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;

/** @extends EloquentRepository<MeterReading> */
class MeterReadingRepository extends EloquentRepository implements MeterReadingRepositoryInterface
{
    public function __construct(MeterReading $model)
    {
        parent::__construct($model);
    }

    public function getByMeterIds(array $meterIds, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        return $this->newQuery()
            ->whereIn('meter_id', $meterIds)
            ->with(['meter.utilityType', 'meter.address', 'tariff', 'media'])
            ->when($from, fn ($query) => $query->where('reading_date', '>=', $from))
            ->when($to, fn ($query) => $query->where('reading_date', '<=', $to))
            ->orderBy('reading_date', 'desc')
            ->get();
    }

    public function getLatestForMeter(int $meterId): ?MeterReading
    {
        /** @var MeterReading|null */
        return $this->newQuery()
            ->where('meter_id', $meterId)
            ->orderBy('reading_date', 'desc')
            ->first();
    }

    public function getLatestForMeters(array $meterIds): Collection
    {
        if ($meterIds === []) {
            return new Collection;
        }

        $latestIds = $this->newQuery()
            ->selectRaw('MAX(id) as id')
            ->whereIn('meter_id', $meterIds)
            ->groupBy('meter_id')
            ->pluck('id');

        return $this->newQuery()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('meter_id');
    }

    public function findWithRelations(int $id): ?MeterReading
    {
        /** @var MeterReading|null */
        return $this->newQuery()
            ->with(['meter.utilityType', 'tariff', 'media'])
            ->find($id);
    }
}
