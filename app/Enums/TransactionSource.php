<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionSource: string
{
    case ADMIN_PANEL = 'admin_panel';
    case USER_PANEL = 'user_panel';
    case SYSTEM = 'system';
}
