<?php

declare(strict_types=1);

namespace Modules\Ai\DTO;

use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;

final readonly class MeterReadingProposal
{
    /** @param list<Tariff> $tariffOptions */
    public function __construct(
        public Meter $meter,
        public ?Tariff $tariff,
        public array $tariffOptions,
        public float $previousValue,
        public ?string $previousDate,
        public float $readingValue,
        public string $readingDate,
        public string $dateSource,
        public ?string $exifDate,
        public AssistantMeterPhoto $photo,
    ) {}

    public function consumption(): float
    {
        return round($this->readingValue - $this->previousValue, 4);
    }
}
