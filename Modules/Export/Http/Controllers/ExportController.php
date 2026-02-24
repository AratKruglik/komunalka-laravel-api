<?php

declare(strict_types=1);

namespace Modules\Export\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Export\Actions\ExportMeterReadings;
use Modules\Export\DTOs\ExportRequestData;
use Modules\Export\Http\Requests\ExportMeterReadingsRequest;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function meterReadings(ExportMeterReadingsRequest $request, ExportMeterReadings $action): Response
    {
        return $action->handle(
            $request->user()->id,
            ExportRequestData::fromRequest($request),
        );
    }
}
