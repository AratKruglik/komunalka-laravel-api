<?php

declare(strict_types=1);

namespace Modules\Meter\Repositories;

use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Meter\Models\Meter;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;

/** @extends EloquentRepository<Meter> */
class MeterRepository extends EloquentRepository implements MeterRepositoryInterface
{
    public function __construct(Meter $model)
    {
        parent::__construct($model);
    }

    public function getByAddressIds(array $addressIds): Collection
    {
        return $this->newQuery()
            ->whereIn('address_id', $addressIds)
            ->with(['utilityType', 'serviceProvider'])
            ->get();
    }

    public function getByAddressId(int $addressId): Collection
    {
        return $this->newQuery()
            ->where('address_id', $addressId)
            ->with(['utilityType', 'serviceProvider'])
            ->get();
    }

    public function getActiveByAddressIds(array $addressIds): Collection
    {
        return $this->newQuery()
            ->whereIn('address_id', $addressIds)
            ->active()
            ->with(['utilityType', 'serviceProvider'])
            ->get();
    }

    public function findWithRelations(int $id): ?Meter
    {
        /** @var Meter|null */
        return $this->newQuery()
            ->with(['utilityType', 'serviceProvider', 'media'])
            ->find($id);
    }

    public function findManyWithRelations(array $ids): Collection
    {
        return $this->newQuery()
            ->whereIn('id', $ids)
            ->with(['utilityType', 'serviceProvider', 'media'])
            ->get();
    }
}
