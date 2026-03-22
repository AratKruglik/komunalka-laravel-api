<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Address\Actions\GetUserAddresses;
use Modules\Address\DTO\AddressPaginationData;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Billing\Actions\CreateServiceProvider;
use Modules\Billing\Actions\DeleteServiceProvider;
use Modules\Billing\Actions\GetServiceProvider;
use Modules\Billing\Actions\GetUserServiceProviders;
use Modules\Billing\Actions\UpdateServiceProvider;
use Modules\Billing\DTO\CreateServiceProviderData;
use Modules\Billing\DTO\UpdateServiceProviderData;
use Modules\Billing\Http\Requests\StoreServiceProviderRequest;
use Modules\Billing\Http\Requests\UpdateServiceProviderRequest;
use Modules\Billing\Http\Resources\ServiceProviderResource;
use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Actions\GetAllCurrencies;
use Modules\Shared\Http\Resources\CurrencyResource;
use Modules\Shared\Http\Resources\UtilityTypeResource;

class ServiceProviderController extends Controller
{
    public function index(Request $request): Response
    {
        $providers = GetUserServiceProviders::run((int) $request->user()->getKey());

        return Inertia::render('Providers/Index', [
            'providers' => ServiceProviderResource::collection($providers),
        ]);
    }

    public function create(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Providers/Create', [
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'currencies' => CurrencyResource::collection(GetAllCurrencies::run()),
        ]);
    }

    public function store(StoreServiceProviderRequest $request): RedirectResponse
    {
        CreateServiceProvider::run(
            (int) $request->user()->getKey(),
            CreateServiceProviderData::fromRequest($request),
        );

        return redirect()->route('providers.index')->with('success', 'Провайдера створено');
    }

    public function edit(string $id, Request $request): Response
    {
        $userId = (int) $request->user()->getKey();
        $provider = GetServiceProvider::run($userId, (int) $id);
        $pagination = AddressPaginationData::fromRequest($request);

        return Inertia::render('Providers/Edit', [
            'provider' => new ServiceProviderResource($provider),
            'addresses' => AddressResource::collection(GetUserAddresses::run($userId, $pagination)),
            'utilityTypes' => UtilityTypeResource::collection(GetActiveUtilityTypes::run()),
            'currencies' => CurrencyResource::collection(GetAllCurrencies::run()),
        ]);
    }

    public function update(UpdateServiceProviderRequest $request, string $id): RedirectResponse
    {
        $userId = (int) $request->user()->getKey();
        $provider = GetServiceProvider::run($userId, (int) $id);
        UpdateServiceProvider::run($userId, $provider, UpdateServiceProviderData::fromRequest($request));

        return redirect()->route('providers.index')->with('success', 'Провайдера оновлено');
    }

    public function destroy(string $id, Request $request): RedirectResponse
    {
        $userId = (int) $request->user()->getKey();
        $provider = GetServiceProvider::run($userId, (int) $id);
        DeleteServiceProvider::run($userId, $provider);

        return redirect()->route('providers.index')->with('success', 'Провайдера видалено');
    }
}
