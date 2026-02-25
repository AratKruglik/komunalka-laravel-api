<?php

declare(strict_types=1);

namespace Modules\Address\Repositories;

use App\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;

/** @extends EloquentRepository<Address> */
class AddressRepository extends EloquentRepository implements AddressRepositoryInterface
{
    public function __construct(Address $model)
    {
        parent::__construct($model);
    }

    public function getForUser(int $userId, int $perPage = 15, string $sortBy = 'created_at', bool $desc = true): LengthAwarePaginator
    {
        $direction = $desc ? 'desc' : 'asc';
        $sortColumn = $sortBy === 'is_primary' ? 'address_user.is_primary' : "addresses.{$sortBy}";

        return $this->newQuery()
            ->select('addresses.*')
            ->addSelect('address_user.is_primary')
            ->join('address_user', 'addresses.id', '=', 'address_user.address_id')
            ->where('address_user.user_id', $userId)
            ->with(['region', 'addressType'])
            ->orderBy($sortColumn, $direction)
            ->paginate($perPage);
    }

    public function findForUser(int $userId, int $addressId): ?Address
    {
        /** @var Address|null */
        return $this->newQuery()
            ->select('addresses.*', 'address_user.is_primary')
            ->join('address_user', 'addresses.id', '=', 'address_user.address_id')
            ->where('address_user.user_id', $userId)
            ->with(['region', 'addressType'])
            ->find($addressId);
    }
}
