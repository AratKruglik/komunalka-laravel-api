<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
