<?php

declare(strict_types=1);

namespace Modules\Billing\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Actions\GetUserAddresses;
use Modules\Address\DTO\AddressPaginationData;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Actions\GetAllCurrencies;
use Modules\Shared\Http\Resources\CurrencyResource;
use Modules\Shared\Http\Resources\UtilityTypeResource;

class ProviderCreatePage
{
    use AsAction;

    public function handle(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Providers/Create', [
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'currencies' => CurrencyResource::collection(GetAllCurrencies::run()),
        ]);
    }

    public function asController(Request $request): Response
    {
        return $this->handle($request);
    }
}
