<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Billing\Actions\CreateServiceProvider;
use Modules\Billing\Actions\DeleteServiceProvider;
use Modules\Billing\Actions\GetServiceProvider;
use Modules\Billing\Actions\GetServiceProvidersByAddress;
use Modules\Billing\Actions\GetUserServiceProviders;
use Modules\Billing\Actions\UpdateServiceProvider;
use Modules\Billing\DTOs\CreateServiceProviderData;
use Modules\Billing\DTOs\UpdateServiceProviderData;
use Modules\Billing\Http\Requests\StoreServiceProviderRequest;
use Modules\Billing\Http\Requests\UpdateServiceProviderRequest;
use Modules\Billing\Http\Resources\ServiceProviderResource;

class ServiceProviderController extends Controller
{
    public function index(Request $request, GetUserServiceProviders $action): AnonymousResourceCollection
    {
        return ServiceProviderResource::collection($action->handle($request->user()->id));
    }

    public function show(int $id, Request $request, GetServiceProvider $action): ServiceProviderResource
    {
        return new ServiceProviderResource($action->handle($request->user()->id, $id));
    }

    public function byAddress(int $addressId, Request $request, GetServiceProvidersByAddress $action): AnonymousResourceCollection
    {
        return ServiceProviderResource::collection($action->handle($request->user()->id, $addressId));
    }

    public function store(StoreServiceProviderRequest $request, CreateServiceProvider $action): ServiceProviderResource
    {
        $provider = $action->handle($request->user()->id, CreateServiceProviderData::fromRequest($request));

        return new ServiceProviderResource($provider);
    }

    public function update(int $id, UpdateServiceProviderRequest $request, UpdateServiceProvider $action): ServiceProviderResource
    {
        $provider = app(GetServiceProvider::class)->handle($request->user()->id, $id);
        $updated = $action->handle($request->user()->id, $provider, UpdateServiceProviderData::fromRequest($request));

        return new ServiceProviderResource($updated);
    }

    public function destroy(int $id, Request $request, DeleteServiceProvider $action): JsonResponse
    {
        $provider = app(GetServiceProvider::class)->handle($request->user()->id, $id);
        $action->handle($request->user()->id, $provider);

        return response()->json(['message' => 'Service provider deleted successfully.']);
    }
}
