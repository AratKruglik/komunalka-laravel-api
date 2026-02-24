<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;

class DeleteAddress
{
    use AsAction;

    public function __construct(
        private AddressRepositoryInterface $addressRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, Address $address): void
    {
        DB::transaction(function () use ($userId, $address) {
            $this->userAddressRepository->detach($userId, $address->id);
            $this->addressRepository->delete($address);
        });
    }
}
