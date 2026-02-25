<?php

declare(strict_types=1);

namespace Modules\Meter\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Modules\Meter\Models\MeterReading;

/**
 * @extends RepositoryInterface<MeterReading>
 */
interface MeterReadingRepositoryInterface extends RepositoryInterface
{
    /** @return Collection<int, MeterReading> */
    public function getByMeterIds(array $meterIds, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection;

    public function getLatestForMeter(int $meterId): ?MeterReading;

    /** @param array<int> $meterIds
     *  @return \Illuminate\Database\Eloquent\Collection<int, MeterReading> */
    public function getLatestForMeters(array $meterIds): Collection;

    public function findWithRelations(int $id): ?MeterReading;
}
