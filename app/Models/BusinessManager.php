<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessManager extends Model
{
    public function adAccounts()
    {
        return $this->hasMany(AdAccount::class, 'bm_id');
    }
}
