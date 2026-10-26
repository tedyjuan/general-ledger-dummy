<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialBalanceAccountGroup1 extends Model
{
    protected $table = 'trial_balance_account_group_1';

    protected $guarded = [];

    public function groups2(): HasMany
    {
        return $this->hasMany(TrialBalanceAccountGroup2::class, 'tbag_1');
    }
}
