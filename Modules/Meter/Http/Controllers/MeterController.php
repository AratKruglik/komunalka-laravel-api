<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Meter\Actions\CreateMeter;
use Modules\Meter\Actions\DeleteMeter;
use Modules\Meter\Actions\GetActiveMeters;
use Modules\Meter\Actions\GetAllMeters;
use Modules\Meter\Actions\GetMeter;
use Modules\Meter\Actions\GetMetersByAddress;
use Modules\Meter\Actions\UpdateMeter;
use Modules\Meter\Actions\UploadMeterPhoto;
use Modules\Meter\DTOs\CreateMeterData;
use Modules\Meter\DTOs\UpdateMeterData;
use Modules\Meter\Http\Requests\StoreMeterRequest;
use Modules\Meter\Http\Requests\UpdateMeterRequest;
use Modules\Meter\Http\Requests\UploadMeterPhotoRequest;
use Modules\Meter\Http\Resources\MeterResource;
use Symfony\Component\HttpFoundation\Response;

class MeterController extends Controller
{
    public function index(Request $request, GetAllMeters $action): AnonymousResourceCollection
    {
        return MeterResource::collection($action->handle($request->user()->id));
    }

    public function show(Request $request, int $id, GetMeter $action): MeterResource
    {
        return new MeterResource($action->handle($request->user()->id, $id));
    }

    public function byAddress(Request $request, int $addressId, GetMetersByAddress $action): AnonymousResourceCollection
    {
        return MeterResource::collection($action->handle($request->user()->id, $addressId));
    }

    public function active(Request $request, GetActiveMeters $action): AnonymousResourceCollection
    {
        return MeterResource::collection($action->handle($request->user()->id));
    }

    public function store(StoreMeterRequest $request, CreateMeter $action): JsonResponse
    {
        $meter = $action->handle($request->user()->id, CreateMeterData::fromRequest($request));

        return (new MeterResource($meter))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function uploadPhoto(UploadMeterPhotoRequest $request, int $id): MeterResource
    {
        $meter = app(GetMeter::class)->handle($request->user()->id, $id);
        $meter = app(UploadMeterPhoto::class)->handle($meter, $request->file('photo'));

        return new MeterResource($meter);
    }

    public function update(UpdateMeterRequest $request, int $id, UpdateMeter $action): MeterResource
    {
        $updated = $action->handle($request->user()->id, $id, UpdateMeterData::fromRequest($request));

        return new MeterResource($updated);
    }

    public function destroy(Request $request, int $id, DeleteMeter $action): JsonResponse
    {
        $action->handle($request->user()->id, $id);

        return response()->json(['message' => 'Лічильник успішно видалено.']);
    }
}
