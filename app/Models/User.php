<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Lauthz\Facades\Enforcer;

#[Fillable(['role_code', 'name', 'username', 'password_hash', 'is_password_default'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    /** Cache izin per request (memoize), supaya izin() berulang tidak membaca cache store lagi. */
    protected ?array $izinCache = null;

    /**
     * Beritahu Laravel kolom password-nya bukan "password".
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    protected function casts(): array
    {
        return [
            'is_password_default' => 'boolean',
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_code', 'code');
    }

    /**
     * Cek izin berdasarkan role_code (subjek Casbin).
     * Tidak menyentuh enforcer/DB kalau cache sudah terisi.
     */
    public function izin(string $key, string $aksi): bool
    {
        // dd($key, $aksi, $this->role_code);

        if (blank($this->role_code)) {
            return false;
        }
        return isset($this->daftarIzin()["{$key}:{$aksi}"]);
    }

    /**
     * Daftar izin milik role user ini, bentuk: ['depo:read' => true, ...].
     * Dibangun sekali dari Casbin, lalu disimpan di cache.
     */

    protected function daftarIzin(): array
    {
        // Jika izin sudah tersedia di property, gunakan langsung.
        if ($this->izinCache !== null) {
            return $this->izinCache;
        }

        // Ambil izin dari cache berdasarkan kode role.
        $izin = Cache::remember(
            self::kunciCacheIzin($this->role_code),
            now()->addHours(6),
            function (): array {

                $set = [];

                // Mengambil seluruh permission termasuk inheritance role Casbin.
                foreach (Enforcer::getImplicitPermissionsForUser($this->role_code) as $p) {

                    // Format policy: [subjek, objek, aksi]
                    // Contoh: ['ACCOUNTING', 'user', 'read']
                    $set["{$p[1]}:{$p[2]}"] = true;
                }

                return $set;
            }
        );

        // Simpan hasil cache ke property agar bisa digunakan kembali.
        $this->izinCache = $izin;
        // baca izin user 
        // dd($this->izinCache);

        return $this->izinCache;
    }


    protected static function kunciCacheIzin(string $roleCode): string
    {
        /**
         * Membuat key cache permission berdasarkan versi cache dan kode role.
         *
         * Format key:
         * izin:v{versi}:role:{kode_role}
         *
         * Contoh:
         * izin:v1:role:ICTT
         * izin:v1:role:ACCOUNTING
         */
        // Mengambil nomor versi cache.
        // Jika belum tersedia, gunakan versi default 1.
        $versi = Cache::get('izin:versi', 1);

        // Membuat key cache berdasarkan versi dan kode role.
        return 'izin:v' . $versi . ':role:' . $roleCode;
    }

    /**
     * Panggil setiap kali policy Casbin atau role_code user berubah
     * (juga di akhir seeder), supaya cache izin langsung usang.
     */
    public static function resetCacheIzin(): void
    {
        // add() membuat key bernilai 1 kalau belum ada, lalu increment aman dipakai
        Cache::add('izin:versi', 1);
        Cache::increment('izin:versi');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%");
        });
    }
}
