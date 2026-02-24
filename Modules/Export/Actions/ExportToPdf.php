<?php

declare(strict_types=1);

namespace Modules\Export\Actions;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Meter\Models\MeterReading;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class ExportToPdf
{
    /** @param Collection<int, MeterReading> $readings */
    public function handle(Collection $readings, Carbon $fromDate, Carbon $toDate): string
    {
        return base64_decode(
            Pdf::view('export::meter-readings', [
                'readings' => $readings,
                'fromDate' => $fromDate->format('d.m.Y'),
                'toDate' => $toDate->format('d.m.Y'),
            ])
                ->landscape()
                ->format(Format::A4)
                ->base64()
        );
    }
}
