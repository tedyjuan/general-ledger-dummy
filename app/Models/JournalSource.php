<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalSource extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'journal_sources';
    private const LIKE_COLUMNS = ['code', 'depo_code', 'description'];
    protected $fillable = [
        'company_code',
        'depo_code',
        'code',
        'description',
    ];
    private static function likePattern(string $value): string
    {
        return '%' . addcslashes(trim($value), '\\%_') . '%';
    }
    public function scopeLikeIf(Builder $q, string $column, ?string $value): Builder
    {
        if (! in_array($column, self::LIKE_COLUMNS, true) || trim((string) $value) === '') {
            return $q;
        }

        return $q->where($column, 'like', self::likePattern($value));
    }

    protected function casts(): array
    {
        return [
            'company_code' => 'string',
            'depo_code'    => 'string',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_code', 'code');
    }


    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (trim((string) $term) === '') {
            return $q;
        }

        $pattern = self::likePattern($term);

        return $q->where(function ($w) use ($pattern) {
            foreach (self::LIKE_COLUMNS as $col) {
                $w->orWhere($col, 'like', $pattern);
            }
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
