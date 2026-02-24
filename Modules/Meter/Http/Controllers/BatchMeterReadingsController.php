<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Meter\Actions\CreateBatchReadings;
use Modules\Meter\Actions\DeleteMeterReading;
use Modules\Meter\Actions\GetMeterReading;
use Modules\Meter\Actions\GetReadingsByAddress;
use Modules\Meter\DTOs\BatchReadingData;
use Modules\Meter\Http\Requests\BatchMeterReadingRequest;
use Modules\Meter\Http\Resources\BatchMeterReadingResponse;
use Modules\Meter\Http\Resources\MeterReadingResource;
use Symfony\Component\HttpFoundation\Response;

class BatchMeterReadingsController extends Controller
{
    public function store(BatchMeterReadingRequest $request, CreateBatchReadings $action): JsonResponse
    {
        $result = $action->handle($request->user()->id, BatchReadingData::fromRequest($request));

        return (new BatchMeterReadingResponse($result))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function byAddress(Request $request, int $addressId, GetReadingsByAddress $action): AnonymousResourceCollection
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        return MeterReadingResource::collection($action->handle($request->user()->id, $addressId, $from, $to));
    }

    public function show(Request $request, int $id, GetMeterReading $action): MeterReadingResource
    {
        return new MeterReadingResource($action->handle($request->user()->id, $id));
    }

    public function destroy(Request $request, int $id, DeleteMeterReading $action): JsonResponse
    {
        $action->handle($request->user()->id, $id);

        return response()->json(['message' => 'Показання лічильника успішно видалено.']);
    }
}
