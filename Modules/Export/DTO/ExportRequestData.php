<?php

declare(strict_types=1);

namespace Modules\Export\DTO;

use Carbon\CarbonImmutable;
use Modules\Export\Http\Requests\ExportMeterReadingsRequest;

final readonly class ExportRequestData
{
    public function __construct(
        public array $addressIds,
        public CarbonImmutable $fromDate,
        public CarbonImmutable $toDate,
        public string $format,
    ) {}

    public static function fromRequest(ExportMeterReadingsRequest $request): self
    {
        return new self(
            addressIds: $request->validated('address_ids'),
            fromDate: CarbonImmutable::parse($request->validated('from_date')),
            toDate: CarbonImmutable::parse($request->validated('to_date')),
            format: $request->validated('format'),
        );
    }
}
