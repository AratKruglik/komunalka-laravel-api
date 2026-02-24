<?php

declare(strict_types=1);

namespace Modules\Meter\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Meter\Models\Meter;

/**
 * @extends RepositoryInterface<Meter>
 */
interface MeterRepositoryInterface extends RepositoryInterface
{
    /** @return Collection<int, Meter> */
    public function getByAddressIds(array $addressIds): Collection;

    /** @return Collection<int, Meter> */
    public function getByAddressId(int $addressId): Collection;

    /** @return Collection<int, Meter> */
    public function getActiveByAddressIds(array $addressIds): Collection;

    public function findWithRelations(int $id): ?Meter;
}
