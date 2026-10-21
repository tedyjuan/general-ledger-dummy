<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'account_number',
        'company_id',
        'header_id',
        'reference_id',
        'name',
        'description',
        'tbag_1',
        'tbag_2',
        'tbag_3',
        'account_type',
        'account_method',
        'account_group',
        'cost_center_type',
    ];

    protected function casts(): array
    {
        return [
            'account_number'   => 'integer',
            'company_id'       => 'integer',
            'header_id'        => 'integer',
            'reference_id'     => 'integer',
            'tbag_1'           => 'integer',
            'tbag_2'           => 'integer',
            'tbag_3'           => 'integer',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'header_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'header_id');
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'reference_id');
    }

    public function group1(): BelongsTo
    {
        return $this->belongsTo(TrialBalanceAccountGroup1::class, 'tbag_1');
    }

    public function group2(): BelongsTo
    {
        return $this->belongsTo(TrialBalanceAccountGroup2::class, 'tbag_2');
    }

    public function group3(): BelongsTo
    {
        return $this->belongsTo(TrialBalanceAccountGroup3::class, 'tbag_3');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('account_number', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        if (empty($type)) {
            return $query;
        }

        return $query->where('account_type', $type);
    }

    public function scopeMethod(Builder $query, ?string $method): Builder
    {
        if (empty($method)) {
            return $query;
        }

        return $query->where('account_method', $method);
    }

    public function scopeAccountGroup(Builder $query, ?string $group): Builder
    {
        if (empty($group)) {
            return $query;
        }

        return $query->where('account_group', $group);
    }

    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        if (empty($companyId)) {
            return $query;
        }

        return $query->where('company_id', $companyId);
    }
}
