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
use Modules\Billing\Actions\GetUserServiceProviders;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Meter\Actions\GetMeter;
use Modules\Meter\Http\Resources\MeterResource;
use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Http\Resources\UtilityTypeResource;

class MeterEditPage
{
    use AsAction;

    public function handle(Request $request, int $meterId): Response
    {
        $userId = (int) $request->user()->getKey();
        $meter = GetMeter::run($userId, $meterId);
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Meters/Edit', [
            'meter' => new MeterResource($meter),
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'serviceProviders' => ServiceProviderResource::collection(GetUserServiceProviders::run($userId)),
        ]);
    }

    public function asController(Request $request, string $meter): Response
    {
        return $this->handle($request, (int) $meter);
    }
}
