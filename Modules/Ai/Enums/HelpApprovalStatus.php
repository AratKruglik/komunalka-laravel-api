<?php

declare(strict_types=1);

namespace Modules\Ai\Enums;

enum HelpApprovalStatus: string
{
    case Pending = 'pending';
    case Executing = 'executing';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
