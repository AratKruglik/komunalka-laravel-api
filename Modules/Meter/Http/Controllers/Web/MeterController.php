<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Address\Actions\GetUserAddresses;
use Modules\Address\DTO\AddressPaginationData;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Billing\Actions\GetUserServiceProviders;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Meter\Actions\CreateMeter;
use Modules\Meter\Actions\DeleteMeter;
use Modules\Meter\Actions\GetAllMeters;
use Modules\Meter\Actions\GetMeter;
use Modules\Meter\Actions\GetMetersByAddress;
use Modules\Meter\Actions\UpdateMeter;
use Modules\Meter\Actions\UploadMeterPhoto;
use Modules\Meter\DTO\CreateMeterData;
use Modules\Meter\DTO\UpdateMeterData;
use Modules\Meter\Http\Requests\StoreMeterRequest;
use Modules\Meter\Http\Requests\UpdateMeterRequest;
use Modules\Meter\Http\Requests\UploadMeterPhotoRequest;
use Modules\Meter\Http\Resources\MeterResource;
use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Http\Resources\UtilityTypeResource;

class MeterController extends Controller
{
    public function index(Request $request): Response
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

    public function create(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Meters/Create', [
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'serviceProviders' => ServiceProviderResource::collection(GetUserServiceProviders::run($userId)),
        ]);
    }

    public function store(StoreMeterRequest $request): RedirectResponse
    {
        CreateMeter::run((int) $request->user()->getKey(), CreateMeterData::fromRequest($request));

        return redirect()->route('meters.index')->with('success', 'Лічильник створено');
    }

    public function edit(string $id, Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $meter = GetMeter::run($userId, (int) $id);
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Meters/Edit', [
            'meter' => new MeterResource($meter),
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'serviceProviders' => ServiceProviderResource::collection(GetUserServiceProviders::run($userId)),
        ]);
    }

    public function update(UpdateMeterRequest $request, string $id): RedirectResponse
    {
        UpdateMeter::run((int) $request->user()->getKey(), (int) $id, UpdateMeterData::fromRequest($request));

        return redirect()->route('meters.index')->with('success', 'Лічильник оновлено');
    }

    public function destroy(string $id, Request $request): RedirectResponse
    {
        DeleteMeter::run((int) $request->user()->getKey(), (int) $id);

        return redirect()->route('meters.index')->with('success', 'Лічильник видалено');
    }

    public function uploadPhoto(UploadMeterPhotoRequest $request, string $meterId): RedirectResponse
    {
        $meter = GetMeter::run((int) $request->user()->getKey(), (int) $meterId);
        UploadMeterPhoto::run($meter, $request->file('photo'));

        return redirect()->back()->with('success', 'Фото завантажено');
    }
}
