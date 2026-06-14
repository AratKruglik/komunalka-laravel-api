<?php

declare(strict_types=1);

namespace Modules\Billing\Repositories;

use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;

/** @extends EloquentRepository<ServiceProvider> */
class ServiceProviderRepository extends EloquentRepository implements ServiceProviderRepositoryInterface
{
    public function __construct(ServiceProvider $model)
    {
        parent::__construct($model);
    }

    public function getByAddressIds(array $addressIds): Collection
    {
        return $this->newQuery()
            ->whereIn('address_id', $addressIds)
            ->with(['utilityType', 'tariffs.currency', 'tariffs.utilityType'])
            ->get();
    }

    public function getByAddressId(int $addressId): Collection
    {
        return $this->newQuery()
            ->where('address_id', $addressId)
            ->with(['utilityType', 'tariffs.currency', 'tariffs.utilityType'])
            ->get();
    }

    public function findWithTariffs(int $id): ?ServiceProvider
    {
        /** @var ServiceProvider|null */
        return $this->newQuery()
            ->with(['utilityType', 'tariffs.currency', 'tariffs.utilityType'])
            ->find($id);
    }
}
