<?php

declare(strict_types=1);

namespace App\Enums;

enum PricingScope: string
{
    case GLOBAL = 'global';
    case USER = 'user';
}
