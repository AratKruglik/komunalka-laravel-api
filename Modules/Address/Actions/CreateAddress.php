<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\DTO\CreateAddressData;
use Modules\Address\Http\Requests\StoreAddressRequest;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;

class CreateAddress
{
    use AsAction;

    public function __construct(
        private AddressRepositoryInterface $addressRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, CreateAddressData $data): Address
    {
        return DB::transaction(function () use ($userId, $data) {
            /** @var Address $address */
            $address = $this->addressRepository->create([
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
                $this->userAddressRepository->clearPrimary($userId);
            }

            $this->userAddressRepository->attach($userId, $address->getKey(), $data->isPrimary);

            return $address->load(['region', 'addressType']);
        });
    }

    public function asController(StoreAddressRequest $request): RedirectResponse
    {
        $this->handle(
            (int) $request->user()->getKey(),
            CreateAddressData::fromRequest($request),
        );

        return redirect()->route('addresses.index')->with('success', 'Адресу створено');
    }
}
