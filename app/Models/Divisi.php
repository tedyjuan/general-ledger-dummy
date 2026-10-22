<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisions';

    protected $fillable = [
        'company_code',
        'code',
        'name',
        'alias',
    ];

    protected function casts(): array
    {
        return [
            'company_code' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_code', 'code');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('alias', 'like', "%{$term}%");
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
