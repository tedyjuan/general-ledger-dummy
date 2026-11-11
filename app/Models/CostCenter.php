<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostCenter extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'cost_centers';

    protected $fillable = [
        'cost_center',
        'name',
        'company_code',
        'area_code',
        'department_code',
        'division_code',
        'segment_code',
        'description',
        'manager',
    ];

    protected function casts(): array
    {
        return [
            'cost_center'     => 'string',
            'name'            => 'string',
            'company_code'    => 'string',
            'area_code'       => 'string',
            'department_code' => 'string',
            'division_code'   => 'string',
            'segment_code'    => 'string',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }

    /* RELASI */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_code', 'code');
    }

    /* SCOPE */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('cost_center', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('manager', 'like', "%{$term}%");
        });
    }

    public function scopeForCompany(Builder $query, ?string $companyCode): Builder
    {
        if (empty($companyCode)) {
            return $query;
        }

        return $query->where('company_code', $companyCode);
    }
}
