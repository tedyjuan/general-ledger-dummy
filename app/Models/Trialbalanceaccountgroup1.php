<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrialBalanceAccountGroup1 extends Model
{
    protected $table = 'trial_balance_account_group_1';

    protected $guarded = ['id'];

    /** Anak di group 2: company_code + code + account_type */
    public function groups2(): Builder
    {
        return TrialBalanceAccountGroup2::query()
            ->where('company_code', $this->company_code)
            ->where('tbag_1_code', $this->code)
            ->where('tbag_1_account_type', $this->account_type);
    }

    public function scopeCompany(Builder $query, string $company): Builder
    {
        return $query->where('company_code', $company);
    }
}
