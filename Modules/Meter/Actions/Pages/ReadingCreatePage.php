<?php

declare(strict_types=1);

namespace Modules\Meter\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;
use Modules\Meter\Http\Resources\MeterReadingResource;
use Modules\Meter\Http\Resources\MeterResource;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;

class ReadingCreatePage
{
    use AsAction;

    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly UserAddressRepositoryInterface $userAddressRepository,
        private readonly MeterRepositoryInterface $meterRepository,
        private readonly MeterReadingRepositoryInterface $meterReadingRepository,
        private readonly ServiceProviderRepositoryInterface $serviceProviderRepository,
    ) {}

    public function handle(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $addressId = $request->integer('address_id');

        $addresses = $this->addressRepository->getForUser($userId, perPage: 100);

        $meters = [];
        $readings = [];
        $serviceProviders = [];

        if ($addressId > 0 && $this->userAddressRepository->userOwnsAddress($userId, $addressId)) {
            $addressMeters = $this->meterRepository->getByAddressId($addressId);
            $meters = MeterResource::collection($addressMeters);

            $meterIds = $addressMeters->pluck('id')->toArray();
            $readings = MeterReadingResource::collection(
                $this->meterReadingRepository->getByMeterIds($meterIds),
            );
            $serviceProviders = ServiceProviderResource::collection(
                $this->serviceProviderRepository->getByAddressId($addressId),
            );
        }

        return Inertia::render('Readings/Create', [
            'addresses' => AddressResource::collection($addresses),
            'meters' => $meters,
            'readings' => $readings,
            'serviceProviders' => $serviceProviders,
            'selectedAddressId' => $addressId ?: null,
        ]);
    }

    public function asController(Request $request): Response
    {
        return $this->handle($request);
    }
}
