<?php

namespace App\Models;

use App\Enums\AdAccountStatus;
use Illuminate\Database\Eloquent\Model;

class AdAccount extends Model
{
    protected function casts(): array
    {
        return [
            'disable_reason' => 'array',
            'status' => AdAccountStatus::class,
        ];
    }

    public function businessManager()
    {
        return $this->belongsTo(BusinessManager::class, 'bm_id');
    }
}
