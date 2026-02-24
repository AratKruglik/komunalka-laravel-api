<?php

declare(strict_types=1);

namespace Modules\Billing\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Billing\Models\ServiceProvider;

/**
 * @extends RepositoryInterface<ServiceProvider>
 */
interface ServiceProviderRepositoryInterface extends RepositoryInterface
{
    /** @return Collection<int, ServiceProvider> */
    public function getByAddressIds(array $addressIds): Collection;

    /** @return Collection<int, ServiceProvider> */
    public function getByAddressId(int $addressId): Collection;

    public function findWithTariffs(int $id): ?ServiceProvider;
}
