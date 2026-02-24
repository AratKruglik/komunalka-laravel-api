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

        $query = $this->newQuery()
            ->select('addresses.*')
            ->join('address_user', 'addresses.id', '=', 'address_user.address_id')
            ->where('address_user.user_id', $userId)
            ->with(['region', 'addressType']);

        if ($sortBy === 'is_primary') {
            $query->addSelect('address_user.is_primary')
                ->orderBy('address_user.is_primary', $direction);
        } else {
            $query->addSelect('address_user.is_primary')
                ->orderBy("addresses.{$sortBy}", $direction);
        }

        return $query->paginate($perPage);
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
