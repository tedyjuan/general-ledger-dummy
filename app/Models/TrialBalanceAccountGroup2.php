<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrialBalanceAccountGroup2 extends Model
{
    protected $table = 'trial_balance_account_group_2';

    protected $guarded = ['id'];

    /** Induk di group 1 */
    public function grup1(): ?TrialBalanceAccountGroup1
    {
        return TrialBalanceAccountGroup1::query()
            ->where('company_code', $this->company_code)
            ->where('code', $this->tbag_1_code)
            ->where('account_type', $this->tbag_1_account_type)
            ->first();
    }

    /** Anak di group 3 */
    public function groups3(): Builder
    {
        return TrialBalanceAccountGroup3::query()
            ->where('company_code', $this->company_code)
            ->where('tbag_2_code', $this->code)
            ->where('tbag_1_code', $this->tbag_1_code)
            ->where('tbag_1_account_type', $this->tbag_1_account_type);
    }

    public function scopeCompany(Builder $query, string $company): Builder
    {
        return $query->where('company_code', $company);
    }
}
