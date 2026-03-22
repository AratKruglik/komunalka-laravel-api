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
use Modules\Billing\Actions\GetServiceProvider;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Actions\GetAllCurrencies;
use Modules\Shared\Http\Resources\CurrencyResource;
use Modules\Shared\Http\Resources\UtilityTypeResource;

class ProviderEditPage
{
    use AsAction;

    public function handle(Request $request, int $providerId): Response
    {
        $userId = (int) $request->user()->getKey();
        $provider = GetServiceProvider::run($userId, $providerId);
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Providers/Edit', [
            'provider' => new ServiceProviderResource($provider),
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'currencies' => CurrencyResource::collection(GetAllCurrencies::run()),
        ]);
    }

    public function asController(Request $request, string $provider): Response
    {
        return $this->handle($request, (int) $provider);
    }
}
