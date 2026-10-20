<?php

declare(strict_types=1);

namespace Modules\Ai\DTO;

use Modules\Ai\Enums\HelpIntent;

final readonly class HelpIntentDecision
{
    public function __construct(
        public HelpIntent $intent,
        public string $reason,
    ) {}
}
