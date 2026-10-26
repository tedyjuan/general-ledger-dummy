<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialBalanceAccountGroup2 extends Model
{
    protected $table = 'trial_balance_account_group_2';

    protected $guarded = [];

    public function group1(): BelongsTo
    {
        return $this->belongsTo(TrialBalanceAccountGroup1::class, 'tbag_1');
    }

    public function groups3(): HasMany
    {
        return $this->hasMany(TrialBalanceAccountGroup3::class, 'tbag_2');
    }
}
