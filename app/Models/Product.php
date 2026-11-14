<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'products';

    protected $fillable = [
        'company_code',
        'product_code',
        'product_name',
        'coa_code',
    ];

    protected function casts(): array
    {
        return [
            'company_code' => 'string',
            'product_code' => 'string',
            'coa_code'     => 'string',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
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
            $q->where('product_code', 'like', "%{$term}%")
                ->orWhere('product_name', 'like', "%{$term}%")
                ->orWhere('coa_code', 'like', "%{$term}%");
        });
    }
}
