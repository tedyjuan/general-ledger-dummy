<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialBalanceAccountGroup1 extends Model
{
    use HasFactory;

    protected $table = 'trial_balance_account_group_1';

    protected $fillable = [
        'company_id',
        'code',
        'description',
        'account_type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'tbag_1');
    }
}
