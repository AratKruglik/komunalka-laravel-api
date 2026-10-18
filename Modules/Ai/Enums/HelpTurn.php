<?php

declare(strict_types=1);

namespace Modules\Ai\Enums;

enum HelpTurn: string
{
    case Question = 'question';
    case MeterPhoto = 'meter_photo';
    case MeterFollowUp = 'meter_follow_up';
    case ApprovalResume = 'approval_resume';

    public function usesMeterTools(): bool
    {
        return $this !== self::Question;
    }
}
