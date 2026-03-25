<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessManager extends Model
{
    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function creditRequests(): HasMany
    {
        return $this->hasMany(AdAccountCreditRequest::class);
    }
}
