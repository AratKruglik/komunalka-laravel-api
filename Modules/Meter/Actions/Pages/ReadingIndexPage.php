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
use Modules\Meter\Actions\GetReadingsByAddress;
use Modules\Meter\Http\Resources\MeterReadingResource;

class ReadingIndexPage
{
    use AsAction;

    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $addressId = $request->integer('address_id');

        $addresses = $this->addressRepository->getForUser($userId, perPage: 100);

        $readings = [];
        if ($addressId > 0 && $this->userAddressRepository->userOwnsAddress($userId, $addressId)) {
            $readings = MeterReadingResource::collection(
                GetReadingsByAddress::run($userId, $addressId),
            );
        }

        return Inertia::render('Readings/Index', [
            'addresses' => AddressResource::collection($addresses),
            'readings' => $readings,
            'filters' => [
                'address_id' => $addressId ?: null,
            ],
        ]);
    }

    public function asController(Request $request): Response
    {
        return $this->handle($request);
    }
}
