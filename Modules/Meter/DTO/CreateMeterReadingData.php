<?php

declare(strict_types=1);

namespace Modules\Meter\DTO;

final readonly class CreateMeterReadingData
{
    public function __construct(
        public int $meterId,
        public float $readingValue,
        public string $readingDate,
        public ?string $notes,
        public bool $isEstimated,
    ) {}
}
