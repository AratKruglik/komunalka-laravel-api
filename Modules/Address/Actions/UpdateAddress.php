<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\DTO\UpdateAddressData;
use Modules\Address\Http\Requests\UpdateAddressRequest;
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
                $this->userAddressRepository->setPrimary($userId, $address->getKey());
            }

            return $updated->load(['region', 'addressType']);
        });
    }

    public function asController(UpdateAddressRequest $request, string $address): RedirectResponse
    {
        $userId = (int) $request->user()->getKey();
        $addressModel = GetUserAddress::run($userId, (int) $address);
        $this->handle($userId, $addressModel, UpdateAddressData::fromRequest($request));

        return redirect()->route('addresses.index')->with('success', 'Адресу оновлено');
    }
}
