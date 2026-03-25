<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case WALLET_TOP_UP = 'wallet_top_up';
    case AD_ACCOUNT_FUNDING = 'ad_account_funding';
    case ADJUSTMENT = 'adjustment';
    case REFUND = 'refund';
}
