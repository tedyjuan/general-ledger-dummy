<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depo extends Model
{
    use HasFactory;

    protected $table = 'depos';

    protected $fillable = [
        'company_id',
        'depo_code',
        'npwp',
        'name',
        'address',
        'city',
        'postal_code',
        'phone_no',
        'alias',
        'area_code',
        'fiscal_year',
        'status_depo',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'fiscal_year' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('depo_code', 'like', "%{$term}%")
              ->orWhere('alias', 'like', "%{$term}%")
              ->orWhere('area_code', 'like', "%{$term}%")
              ->orWhere('city', 'like', "%{$term}%")
              ->orWhere('npwp', 'like', "%{$term}%")
              ->orWhere('phone_no', 'like', "%{$term}%");
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status_depo', $status);
    }

    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        if (empty($companyId)) {
            return $query;
        }

        return $query->where('company_id', $companyId);
    }
}
