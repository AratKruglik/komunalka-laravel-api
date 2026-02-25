<?php

declare(strict_types=1);

namespace Modules\Address\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Address\Actions\CreateAddress;
use Modules\Address\Actions\DeleteAddress;
use Modules\Address\Actions\GetUserAddress;
use Modules\Address\Actions\GetUserAddresses;
use Modules\Address\Actions\PatchAddress;
use Modules\Address\Actions\UpdateAddress;
use Modules\Address\DTO\AddressPaginationData;
use Modules\Address\DTO\CreateAddressData;
use Modules\Address\DTO\PatchAddressData;
use Modules\Address\DTO\UpdateAddressData;
use Modules\Address\Http\Requests\PatchAddressRequest;
use Modules\Address\Http\Requests\StoreAddressRequest;
use Modules\Address\Http\Requests\UpdateAddressRequest;
use Modules\Address\Http\Resources\AddressResource;

class AddressController extends Controller
{
    public function index(Request $request, GetUserAddresses $action): AnonymousResourceCollection
    {
        $pagination = AddressPaginationData::fromRequest($request);

        return AddressResource::collection($action->handle($request->user()->id, $pagination));
    }

    public function show(int $id, Request $request, GetUserAddress $action): AddressResource
    {
        return new AddressResource($action->handle($request->user()->id, $id));
    }

    public function store(StoreAddressRequest $request, CreateAddress $action): AddressResource
    {
        $address = $action->handle($request->user()->id, CreateAddressData::fromRequest($request));

        return new AddressResource($address);
    }

    public function update(int $id, UpdateAddressRequest $request, UpdateAddress $action): AddressResource
    {
        $address = app(GetUserAddress::class)->handle($request->user()->id, $id);
        $updated = $action->handle($request->user()->id, $address, UpdateAddressData::fromRequest($request));

        return new AddressResource($updated);
    }

    public function patch(int $id, PatchAddressRequest $request, PatchAddress $action): AddressResource
    {
        $address = app(GetUserAddress::class)->handle($request->user()->id, $id);
        $patched = $action->handle($request->user()->id, $address, PatchAddressData::fromRequest($request));

        return new AddressResource($patched);
    }

    public function destroy(int $id, Request $request, DeleteAddress $action): JsonResponse
    {
        $address = app(GetUserAddress::class)->handle($request->user()->id, $id);
        $action->handle($request->user()->id, $address);

        return response()->json(['message' => 'Address deleted successfully.']);
    }
}
