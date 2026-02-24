<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\DTOs\AddressPaginationData;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;

class GetUserAddresses
{
    use AsAction;

    public function __construct(private AddressRepositoryInterface $repository) {}

    /** @return LengthAwarePaginator<int, Address> */
    public function handle(int $userId, AddressPaginationData $pagination): LengthAwarePaginator
    {
        return $this->repository->getForUser(
            $userId,
            $pagination->perPage,
            $pagination->sortBy,
            $pagination->desc,
        );
    }
}
