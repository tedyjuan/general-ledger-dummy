<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabel fiscal_periods tidak punya company_code.
 * Company ditentukan lewat depo: depo_code -> depos.code -> depos.company_code.
 */
class FiscalPeriod extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_UNDEFINED = 'undefined';

    protected $fillable = ['depo_code', 'year', 'period', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',   // kolom string 'Y-m-d' -> Carbon
        'end_date'   => 'date',
    ];

    /** "2,024" / "2.024" dari Excel -> "2024" (kolom year bertipe string) */
    protected function year(): Attribute
    {
        return Attribute::set(fn($v) => preg_replace('/\D/', '', (string) $v));
    }

    public function depo(): BelongsTo
    {
        return $this->belongsTo(Depo::class, 'depo_code', 'code');
    }

    /** Hanya periode milik depo pada company yang diberikan. */
    public function scopeForCompany(Builder $query, string $companyCode): Builder
    {
        return $query->whereIn(
            'depo_code',
            Depo::query()->where('company_code', $companyCode)->select('code')
        );
    }

    /** Status kosong (NULL) dianggap "undefined". */
    public function getStatusNormalAttribute(): string
    {
        return in_array($this->status, ['open', 'closed'], true) ? $this->status : self::STATUS_UNDEFINED;
    }

    /** Periode 13 & 14 adalah periode penyesuaian (tanggalnya sama dengan periode 12). */
    public function getIsPenyesuaianAttribute(): bool
    {
        return (int) $this->period > 12;
    }

    /** Periode yang tanggal hari ini berada di dalamnya. */
    public function getIsBerjalanAttribute(): bool
    {
        return ! $this->is_penyesuaian
            && $this->start_date && $this->end_date
            && today()->between($this->start_date, $this->end_date);
    }

    public function getPeriodeLabelAttribute(): string
    {
        return str_pad((string) $this->period, 2, '0', STR_PAD_LEFT);
    }

    public function getNamaPeriodeAttribute(): string
    {
        return $this->is_penyesuaian
            ? 'Penyesuaian'
            : ($this->start_date?->translatedFormat('F') ?? '-');
    }
}
