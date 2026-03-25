<?php

namespace App\Models;

use App\Enums\AdAccountStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdAccount extends Model
{
    protected function casts(): array
    {
        return [
            'disable_reason' => 'array',
            'status' => AdAccountStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessManager(): BelongsTo
    {
        return $this->belongsTo(BusinessManager::class);
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
