<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\DTO\UpdateAddressData;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;

class UpdateAddress
{
    use AsAction;

    public function __construct(
        private AddressRepositoryInterface $addressRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, Address $address, UpdateAddressData $data): Address
    {
        /** @var Address */
        return DB::transaction(function () use ($userId, $address, $data) {
            /** @var Address $updated */
            $updated = $this->addressRepository->update($address, [
                'region_id' => $data->regionId,
                'address_type_id' => $data->addressTypeId,
                'city' => $data->city,
                'street' => $data->street,
                'building_number' => $data->buildingNumber,
                'apartment_number' => $data->apartmentNumber,
                'zip_code' => $data->zipCode,
                'notes' => $data->notes,
            ]);

            if ($data->isPrimary) {
                $this->userAddressRepository->setPrimary($userId, $address->id);
            }

            return $updated->load(['region', 'addressType']);
        });
    }
}
