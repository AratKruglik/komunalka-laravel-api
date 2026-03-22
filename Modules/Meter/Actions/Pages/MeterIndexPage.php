<?php

declare(strict_types=1);

namespace Modules\Meter\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Actions\GetUserAddresses;
use Modules\Address\DTO\AddressPaginationData;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Meter\Actions\GetAllMeters;
use Modules\Meter\Actions\GetMetersByAddress;
use Modules\Meter\Http\Resources\MeterResource;

class MeterIndexPage
{
    use AsAction;

    public function handle(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $addressId = $request->query('address_id');

        $meters = $addressId
            ? GetMetersByAddress::run($userId, (int) $addressId)
            : GetAllMeters::run($userId);

        $pagination = AddressPaginationData::fromRequest($request);
        $addresses = GetUserAddresses::run($userId, $pagination);

        return Inertia::render('Meters/Index', [
            'meters' => MeterResource::collection($meters),
            'addresses' => AddressResource::collection($addresses),
            'selectedAddressId' => $addressId ? (int) $addressId : null,
        ]);
    }

    public function asController(Request $request): Response
    {
        return $this->handle($request);
    }
}
