<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabel pivot akses user ke company.
 * Primary key komposit (company_code, username), tanpa kolom id.
 *
 * Catatan: Eloquent tidak mendukung composite key untuk update/delete via
 * instance. Untuk hapus/ubah, pakai query:
 *   CompanyUser::where('company_code', $c)->where('username', $u)->delete();
 */
class CompanyUser extends Model
{
    use HasFactory;

    // Wajib eksplisit: tanpa ini Laravel mencari tabel "company_users"
    protected $table = 'company_user';

    // Composite PK: matikan asumsi kolom "id" auto increment
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'company_code',
        'username',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_code', 'code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('username', 'like', "%{$term}%")
                ->orWhere('company_code', 'like', "%{$term}%");
        });
    }

    public function scopeForCompany(Builder $query, ?string $companyCode): Builder
    {
        return empty($companyCode) ? $query : $query->where('company_code', $companyCode);
    }

    public function scopeForUser(Builder $query, ?string $username): Builder
    {
        return empty($username) ? $query : $query->where('username', $username);
    }
}
