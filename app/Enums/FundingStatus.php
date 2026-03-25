<?php

declare(strict_types=1);

namespace App\Enums;

enum FundingStatus: string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
