<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coa extends Model
{
    use HasFactory, BelongsToCompany;

    public const ACCOUNT_TYPES = [
        'asset',
        'liability',
        'equity',
        'income',
        'expense',
        'retained earning account',
    ];
    public const ACCOUNT_METHODS     = ['debit', 'credit'];
    public const ACCOUNT_GROUPS      = ['kas', 'bank', 'inventory', 'sales'];
    public const COST_CENTER_TYPES   = ['all', 'depo', 'unit'];

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'company_code',
        'account_number',
        'header_code',
        'reference_code',
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
            'company_code'   => 'string',
            'account_number' => 'integer',
            'header_code'    => 'integer',
            'reference_code' => 'integer',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    /* LEVEL: header (header_code NULL) | ledger (reference_code NULL) | subledger */
    public function getLevelAttribute(): string
    {
        return match (true) {
            $this->header_code === null    => 'header',
            $this->reference_code === null => 'ledger',
            default                        => 'subledger',
        };
    }

    /* Punya akun turunan? (dipakai untuk mengunci struktur & mencegah hapus) */
    public function hasChildren(): bool
    {
        return match ($this->level) {
            'header'  => static::query()->where('header_code', $this->account_number)->exists(),
            'ledger'  => static::query()->where('reference_code', $this->account_number)->exists(),
            default   => false,
        };
    }

    /* RELASI */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_code', 'code');
    }

    // header dari ledger / subledger
    public function header(): BelongsTo
    {
        return $this->belongsTo(self::class, 'header_code', 'account_number')
            ->where('company_code', $this->company_code);
    }

    // ledger dari subledger
    public function ledger(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_code', 'account_number')
            ->where('company_code', $this->company_code);
    }

    // dari header: semua ledger-nya
    public function ledgers(): HasMany
    {
        return $this->hasMany(self::class, 'header_code', 'account_number')
            ->where('company_code', $this->company_code)
            ->whereNull('reference_code');
    }

    // dari ledger: semua subledger-nya
    public function subledgers(): HasMany
    {
        return $this->hasMany(self::class, 'reference_code', 'account_number')
            ->where('company_code', $this->company_code);
    }

    /* SCOPE */
    public function scopeHeaders(Builder $query): Builder
    {
        return $query->whereNull('header_code');
    }
    // dari header: semua ledger-nya
    public function childLedgers(): HasMany
    {
        return $this->hasMany(self::class, 'header_code', 'account_number')
            ->where('company_code', $this->company_code)
            ->whereNull('reference_code');
    }

    // dari ledger: semua subledger-nya
    public function childSubledgers(): HasMany
    {
        return $this->hasMany(self::class, 'reference_code', 'account_number')
            ->where('company_code', $this->company_code);
    }
    public function scopeLedgers(Builder $query): Builder
    {
        return $query->whereNotNull('header_code')->whereNull('reference_code');
    }

    public function scopeSubledgers(Builder $query): Builder
    {
        return $query->whereNotNull('reference_code');
    }

    public function scopeLevel(Builder $query, ?string $level): Builder
    {
        return match ($level) {
            'header'    => $query->headers(),
            'ledger'    => $query->ledgers(),
            'subledger' => $query->subledgers(),
            default     => $query,
        };
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

    public function scopeForCompany(Builder $query, ?string $companyCode): Builder
    {
        if (empty($companyCode)) {
            return $query;
        }

        return $query->where('company_code', $companyCode);
    }
}
