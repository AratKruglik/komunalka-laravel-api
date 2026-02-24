<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\DTOs\PatchAddressData;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;

class PatchAddress
{
    use AsAction;

    public function __construct(
        private AddressRepositoryInterface $addressRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, Address $address, PatchAddressData $data): Address
    {
        /** @var Address */
        return DB::transaction(function () use ($userId, $address, $data) {
            $attributes = $data->toAddressAttributes();

            if ($attributes !== []) {
                /** @var Address $address */
                $address = $this->addressRepository->update($address, $attributes);
            }

            if ($data->isPrimary === true) {
                $this->userAddressRepository->setPrimary($userId, $address->id);
            } elseif ($data->isPrimary === false) {
                $this->userAddressRepository->clearPrimary($userId);
            }

            return $address->load(['region', 'addressType']);
        });
    }
}
