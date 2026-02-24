<?php

declare(strict_types=1);

namespace Modules\Address\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Address\Models\Address;

/** @extends RepositoryInterface<Address> */
interface AddressRepositoryInterface extends RepositoryInterface
{
    /** @return LengthAwarePaginator<int, Address> */
    public function getForUser(int $userId, int $perPage = 15, string $sortBy = 'created_at', bool $desc = true): LengthAwarePaginator;

    public function findForUser(int $userId, int $addressId): ?Address;
}
