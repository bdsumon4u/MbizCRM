<?php

namespace App\Models;

use App\Enums\FundingStatus;
use App\Enums\PricingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdAccountCreditRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_usd_micros' => 'integer',
            'resolved_rate_bdt_per_usd_poisha' => 'integer',
            'required_bdt_poisha' => 'integer',
            'pricing_scope' => PricingScope::class,
            'status' => FundingStatus::class,
            'facebook_request_payload' => 'array',
            'facebook_response_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
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
}
