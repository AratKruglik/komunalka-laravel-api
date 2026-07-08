<?php

declare(strict_types=1);

namespace Modules\Meter\DTO;

use Illuminate\Http\UploadedFile;
use Modules\Meter\Http\Requests\BatchMeterReadingRequest;

final readonly class BatchReadingData
{
    /**
     * @param  array<int, CreateMeterReadingData>  $readings
     * @param  array<int, array<int, UploadedFile>>  $photos  Keyed by meter_id
     */
    public function __construct(
        public array $readings,
        public array $photos,
    ) {}

    public static function fromRequest(BatchMeterReadingRequest $request): self
    {
        $readings = [];
        foreach ($request->validated('readings', []) as $reading) {
            $readings[] = new CreateMeterReadingData(
                meterId: (int) $reading['meter_id'],
                readingValue: (float) $reading['reading_value'],
                readingDate: $reading['reading_date'],
                notes: $reading['notes'] ?? null,
                isEstimated: (bool) ($reading['is_estimated'] ?? false),
                tariffId: isset($reading['tariff_id']) ? (int) $reading['tariff_id'] : null,
            );
        }

        $photos = [];
        foreach ($request->file('photos', []) as $meterId => $files) {
            $photos[(int) $meterId] = is_array($files) ? $files : [$files];
        }

        return new self(
            readings: $readings,
            photos: $photos,
        );
    }
}
