<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Meter\Actions\CreateServiceCounterValue;
use Modules\Meter\Actions\DeleteServiceCounterValue;
use Modules\Meter\Actions\GetServiceCounterValue;
use Modules\Meter\Http\Requests\StoreMeterReadingRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class LegacyMeterReadingController extends Controller
{
    public function store(StoreMeterReadingRequest $request, CreateServiceCounterValue $action): JsonResponse
    {
        $value = $action->handle(
            $request->user()->id,
            (int) $request->validated('service_counter_id'),
            (float) $request->validated('value'),
        );

        return response()->json($value, Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id, GetServiceCounterValue $action): JsonResponse
    {
        return response()->json($action->handle($request->user()->id, $id));
    }

    public function destroy(Request $request, int $id, DeleteServiceCounterValue $action): JsonResponse
    {
        $action->handle($request->user()->id, $id);

        return response()->json(['message' => 'Показання успішно видалено.']);
    }

    public function imageOptimized(int $id): BinaryFileResponse
    {
        $media = Media::query()->find($id);

        abort_if($media === null, Response::HTTP_NOT_FOUND);

        return response()->file($media->getPath('optimized'));
    }

    public function imageThumbnail(int $id): BinaryFileResponse
    {
        $media = Media::query()->find($id);

        abort_if($media === null, Response::HTTP_NOT_FOUND);

        return response()->file($media->getPath('thumbnail'));
    }
}
