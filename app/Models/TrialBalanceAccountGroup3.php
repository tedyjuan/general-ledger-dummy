<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialBalanceAccountGroup3 extends Model
{
    protected $table = 'trial_balance_account_group_3';

    protected $guarded = [];

    public function group2(): BelongsTo
    {
        return $this->belongsTo(TrialBalanceAccountGroup2::class, 'tbag_2');
    }
}
