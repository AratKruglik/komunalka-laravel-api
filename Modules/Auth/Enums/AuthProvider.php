<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum AuthProvider: string
{
    case Local = 'local';
    case Google = 'google';
    case GitHub = 'github';
}
