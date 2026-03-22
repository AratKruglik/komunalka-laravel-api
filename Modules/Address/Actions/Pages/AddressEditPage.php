<?php

declare(strict_types=1);

namespace Modules\Address\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Actions\GetAllAddressTypes;
use Modules\Address\Actions\GetAllRegions;
use Modules\Address\Actions\GetUserAddress;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Address\Http\Resources\AddressTypeResource;
use Modules\Address\Http\Resources\RegionResource;

class AddressEditPage
{
    use AsAction;

    public function handle(int $userId, int $addressId): Response
    {
        $address = GetUserAddress::run($userId, $addressId);

        return Inertia::render('Addresses/Edit', [
            'address' => new AddressResource($address),
            'regions' => RegionResource::collection(GetAllRegions::run()),
            'addressTypes' => AddressTypeResource::collection(GetAllAddressTypes::run()),
        ]);
    }

    public function asController(Request $request, string $address): Response
    {
        return $this->handle((int) $request->user()->getKey(), (int) $address);
    }
}
