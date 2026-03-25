<?php

namespace App\Models;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WalletTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'source' => TransactionSource::class,
            'amount_bdt_poisha' => 'integer',
            'balance_before_poisha' => 'integer',
            'balance_after_poisha' => 'integer',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function businessManager(): BelongsTo
    {
        return $this->belongsTo(BusinessManager::class);
    }

    public function performedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'performed_by_admin_id');
    }

    public function adAccountCreditRequest(): HasOne
    {
        return $this->hasOne(AdAccountCreditRequest::class);
    }
}
