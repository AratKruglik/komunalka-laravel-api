<?php

declare(strict_types=1);

namespace Modules\Billing\Repositories;

use App\Repositories\EloquentRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Modules\Billing\Models\Tariff;
use Modules\Billing\Repositories\Contracts\TariffRepositoryInterface;

/** @extends EloquentRepository<Tariff> */
class TariffRepository extends EloquentRepository implements TariffRepositoryInterface
{
    /** @var array<string, Tariff|null> */
    private array $cache = [];

    public function __construct(Tariff $model)
    {
        parent::__construct($model);
    }

    public function getEffective(int $serviceProviderId, CarbonImmutable $date): ?Tariff
    {
        /** @var Tariff|null */
        return $this->newQuery()
            ->where('service_provider_id', $serviceProviderId)
            ->effectiveAt($date)
            ->with(['currency', 'utilityType'])
            ->first();
    }

    public function getEffectiveForUtilityType(int $serviceProviderId, int $utilityTypeId, CarbonImmutable $date): ?Tariff
    {
        $key = "{$serviceProviderId}:{$utilityTypeId}:{$date->toDateString()}";

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        /** @var Tariff|null */
        return $this->cache[$key] = $this->newQuery()
            ->where('service_provider_id', $serviceProviderId)
            ->where('utility_type_id', $utilityTypeId)
            ->effectiveAt($date)
            ->with(['currency', 'utilityType'])
            ->first();
    }

    public function getByServiceProviderId(int $serviceProviderId): Collection
    {
        return $this->newQuery()
            ->where('service_provider_id', $serviceProviderId)
            ->with(['currency', 'utilityType'])
            ->get();
    }
}
