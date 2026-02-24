<?php

declare(strict_types=1);

namespace Modules\Export\DTOs;

use Carbon\Carbon;
use Modules\Export\Http\Requests\ExportMeterReadingsRequest;

final readonly class ExportRequestData
{
    public function __construct(
        public array $addressIds,
        public Carbon $fromDate,
        public Carbon $toDate,
        public string $format,
    ) {}

    public static function fromRequest(ExportMeterReadingsRequest $request): self
    {
        return new self(
            addressIds: $request->validated('address_ids'),
            fromDate: Carbon::parse($request->validated('from_date')),
            toDate: Carbon::parse($request->validated('to_date')),
            format: $request->validated('format'),
        );
    }
}
