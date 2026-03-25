<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'current_balance_poisha' => 'integer',
            'reserved_balance_poisha' => 'integer',
            'lifetime_credit_poisha' => 'integer',
            'lifetime_debit_poisha' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
