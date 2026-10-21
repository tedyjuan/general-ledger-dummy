<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialBalanceAccountGroup3 extends Model
{
    use HasFactory;

    protected $table = 'trial_balance_account_group_3';

    protected $fillable = [
        'company_id',
        'code',
        'description',
        'tbag_2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function group2(): BelongsTo
    {
        return $this->belongsTo(TrialBalanceAccountGroup2::class, 'tbag_2');
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'tbag_3');
    }
}
