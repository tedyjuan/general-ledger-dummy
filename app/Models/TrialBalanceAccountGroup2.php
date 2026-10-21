<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialBalanceAccountGroup2 extends Model
{
    use HasFactory;

    protected $table = 'trial_balance_account_group_2';

    protected $fillable = [
        'company_id',
        'code',
        'description',
        'tbag_1',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function group1(): BelongsTo
    {
        return $this->belongsTo(TrialBalanceAccountGroup1::class, 'tbag_1');
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'tbag_2');
    }
}
