<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Shared\Actions\CreateCurrency;
use Modules\Shared\Actions\DeleteCurrency;
use Modules\Shared\Actions\GetAllCurrencies;
use Modules\Shared\Actions\GetCurrency;
use Modules\Shared\Actions\UpdateCurrency;
use Modules\Shared\DTO\CreateCurrencyData;
use Modules\Shared\DTO\UpdateCurrencyData;
use Modules\Shared\Http\Requests\StoreCurrencyRequest;
use Modules\Shared\Http\Requests\UpdateCurrencyRequest;
use Modules\Shared\Http\Resources\CurrencyResource;

class CurrencyController extends Controller
{
    public function index(GetAllCurrencies $action): AnonymousResourceCollection
    {
        return CurrencyResource::collection($action->handle());
    }

    public function show(int $currency, GetCurrency $action): CurrencyResource
    {
        return new CurrencyResource($action->handle($currency));
    }

    public function store(StoreCurrencyRequest $request, CreateCurrency $action): CurrencyResource
    {
        $currency = $action->handle(CreateCurrencyData::fromRequest($request));

        return (new CurrencyResource($currency))
            ->additional(['message' => 'Currency created successfully.']);
    }

    public function update(int $currency, UpdateCurrencyRequest $request, UpdateCurrency $action): CurrencyResource
    {
        $model = app(GetCurrency::class)->handle($currency);
        $updated = $action->handle($model, UpdateCurrencyData::fromRequest($request));

        return new CurrencyResource($updated);
    }

    public function destroy(int $currency, DeleteCurrency $action): JsonResponse
    {
        $model = app(GetCurrency::class)->handle($currency);
        $action->handle($model);

        return response()->json(['message' => 'Currency deleted successfully.']);
    }
}
