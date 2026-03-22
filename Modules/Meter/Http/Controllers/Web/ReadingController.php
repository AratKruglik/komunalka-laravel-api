<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;
use Modules\Meter\Actions\CreateBatchReadings;
use Modules\Meter\Actions\DeleteMeterReading;
use Modules\Meter\Actions\GetReadingsByAddress;
use Modules\Meter\DTO\BatchReadingData;
use Modules\Meter\Http\Requests\BatchMeterReadingRequest;
use Modules\Meter\Http\Resources\MeterReadingResource;
use Modules\Meter\Http\Resources\MeterResource;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;

class ReadingController
{
    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly UserAddressRepositoryInterface $userAddressRepository,
        private readonly MeterRepositoryInterface $meterRepository,
        private readonly MeterReadingRepositoryInterface $meterReadingRepository,
        private readonly ServiceProviderRepositoryInterface $serviceProviderRepository,
    ) {}

    public function index(Request $request): Response
    {
        $userId = $request->user()->getKey();
        $addressId = $request->integer('address_id');

        $addresses = $this->addressRepository->getForUser($userId, perPage: 100);

        $readings = [];
        if ($addressId > 0 && $this->userAddressRepository->userOwnsAddress($userId, $addressId)) {
            $readings = MeterReadingResource::collection(
                app(GetReadingsByAddress::class)->handle($userId, $addressId),
            );
        }

        return Inertia::render('Readings/Index', [
            'addresses' => $addresses,
            'readings' => $readings,
            'filters' => [
                'address_id' => $addressId ?: null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $userId = $request->user()->getKey();
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
            'addresses' => $addresses,
            'meters' => $meters,
            'readings' => $readings,
            'serviceProviders' => $serviceProviders,
            'selectedAddressId' => $addressId ?: null,
        ]);
    }

    public function store(BatchMeterReadingRequest $request): RedirectResponse
    {
        CreateBatchReadings::run(
            $request->user()->getKey(),
            BatchReadingData::fromRequest($request),
        );

        return redirect()
            ->route('readings.index')
            ->with('success', 'Показання успішно збережено!');
    }

    public function destroy(string $id, Request $request): RedirectResponse
    {
        DeleteMeterReading::run(
            $request->user()->getKey(),
            (int) $id,
        );

        return redirect()
            ->back()
            ->with('success', 'Показання успішно видалено.');
    }
}
