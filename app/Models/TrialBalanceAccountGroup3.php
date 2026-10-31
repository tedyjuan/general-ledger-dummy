<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrialBalanceAccountGroup3 extends Model
{
    protected $table = 'trial_balance_account_group_3';

    protected $guarded = ['id'];

    /** Induk di group 2 */
    public function grup2(): ?TrialBalanceAccountGroup2
    {
        return TrialBalanceAccountGroup2::query()
            ->where('company_code', $this->company_code)
            ->where('code', $this->tbag_2_code)
            ->where('tbag_1_code', $this->tbag_1_code)
            ->where('tbag_1_account_type', $this->tbag_1_account_type)
            ->first();
    }

    /** Induk teratas di group 1 (lewat rantai yang dibawa) */
    public function grup1(): ?TrialBalanceAccountGroup1
    {
        return TrialBalanceAccountGroup1::query()
            ->where('company_code', $this->company_code)
            ->where('code', $this->tbag_1_code)
            ->where('account_type', $this->tbag_1_account_type)
            ->first();
    }

    public function scopeCompany(Builder $query, string $company): Builder
    {
        return $query->where('company_code', $company);
    }
}
