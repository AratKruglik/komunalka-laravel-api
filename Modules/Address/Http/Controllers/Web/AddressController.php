<?php

declare(strict_types=1);

namespace Modules\Address\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Address\Actions\CreateAddress;
use Modules\Address\Actions\DeleteAddress;
use Modules\Address\Actions\GetAllAddressTypes;
use Modules\Address\Actions\GetAllRegions;
use Modules\Address\Actions\GetUserAddress;
use Modules\Address\Actions\GetUserAddresses;
use Modules\Address\Actions\UpdateAddress;
use Modules\Address\DTO\AddressPaginationData;
use Modules\Address\DTO\CreateAddressData;
use Modules\Address\DTO\UpdateAddressData;
use Modules\Address\Http\Requests\StoreAddressRequest;
use Modules\Address\Http\Requests\UpdateAddressRequest;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Address\Http\Resources\AddressTypeResource;
use Modules\Address\Http\Resources\RegionResource;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $pagination = AddressPaginationData::fromRequest($request);
        $addresses = GetUserAddresses::run((int) $request->user()->getKey(), $pagination);

        return Inertia::render('Addresses/Index', [
            'addresses' => AddressResource::collection($addresses),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Addresses/Create', [
            'regions' => RegionResource::collection(GetAllRegions::run()),
            'addressTypes' => AddressTypeResource::collection(GetAllAddressTypes::run()),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        CreateAddress::run((int) $request->user()->getKey(), CreateAddressData::fromRequest($request));

        return redirect()->route('addresses.index')->with('success', 'Адресу створено');
    }

    public function edit(string $id, Request $request): Response
    {
        $address = GetUserAddress::run((int) $request->user()->getKey(), (int) $id);

        return Inertia::render('Addresses/Edit', [
            'address' => new AddressResource($address),
            'regions' => RegionResource::collection(GetAllRegions::run()),
            'addressTypes' => AddressTypeResource::collection(GetAllAddressTypes::run()),
        ]);
    }

    public function update(UpdateAddressRequest $request, string $id): RedirectResponse
    {
        $address = GetUserAddress::run((int) $request->user()->getKey(), (int) $id);
        UpdateAddress::run((int) $request->user()->getKey(), $address, UpdateAddressData::fromRequest($request));

        return redirect()->route('addresses.index')->with('success', 'Адресу оновлено');
    }

    public function destroy(string $id, Request $request): RedirectResponse
    {
        $address = GetUserAddress::run((int) $request->user()->getKey(), (int) $id);
        DeleteAddress::run((int) $request->user()->getKey(), $address);

        return redirect()->route('addresses.index')->with('success', 'Адресу видалено');
    }
}
