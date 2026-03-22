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
use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Http\Resources\UtilityTypeResource;

class MeterCreatePage
{
    use AsAction;

    public function handle(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Meters/Create', [
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'serviceProviders' => ServiceProviderResource::collection(GetUserServiceProviders::run($userId)),
        ]);
    }

    public function asController(Request $request): Response
    {
        return $this->handle($request);
    }
}
