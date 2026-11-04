<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Role;
use App\Livewire\Concerns\Otorisasi;
use App\Support\Navigation;
use Lauthz\Facades\Enforcer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\DB;


#[Layout('components.layouts.app')] // sesuaikan dengan layout utama Anda
#[Title('Akses per Menu')]
class AksesPerMenu extends Component
{
    use Otorisasi;

    /** Key menu yang sedang diatur, mis. "depo" */
    public string $menu = '';

    /** $matrix[indeksPeran][aksi] = true/false */
    public array $matrix = [];
    private const AKSI_STANDAR = ['read', 'create', 'update', 'delete'];

    public bool $tersimpan = false;

    public function mount(): void
    {
        $this->izinkan('permissions', 'manage');

        $this->menu = (string) array_key_first(Navigation::permissionables());
        $this->muat();
    }

    public function updatedMenu(): void
    {
        abort_unless(array_key_exists($this->menu, Navigation::permissionables()), 422);

        $this->tersimpan = false;
        $this->muat();
    }

    protected function muat(): void
    {
        $aksiMenu = Navigation::permissionables()[$this->menu]['actions'] ?? [];

        $perans = Role::query()
            ->orderBy('name')
            ->pluck('code')
            ->toArray();

        $this->matrix = [];



        foreach ($perans as $i => $peran) {
            foreach ($aksiMenu as $aksi) {
                $this->matrix[$i][$aksi] = Enforcer::hasPolicy(
                    $peran,
                    $this->menu,
                    $aksi
                );
            }
        }
    }





    public function simpan(): void
    {
        $this->izinkan('permissions', 'manage');

        $daftarMenu = Navigation::permissionables();

        abort_unless(isset($daftarMenu[$this->menu]), 422);

        // Pastikan 4 aksi standar selalu diproses, ditambah aksi khusus menu (jika ada)
        $aksiMenu = array_values(array_unique(array_merge(
            self::AKSI_STANDAR,
            $daftarMenu[$this->menu]['actions'] ?? []
        )));

        $perans = Role::query()
            ->orderBy('name')
            ->get(['code']);

        DB::transaction(function () use ($perans, $aksiMenu) {
            foreach ($perans as $i => $role) {
                $peran = $role->code;

                // Prioritaskan key berdasarkan kode role, fallback ke index
                $baris = $this->matrix[$peran] ?? $this->matrix[$i] ?? [];

                $mau = [];
                foreach ($aksiMenu as $aksi) {
                    $mau[$aksi] = (bool) ($baris[$aksi] ?? false);
                }

                // Jika punya aksi apa pun, otomatis boleh read
                if (in_array(true, $mau, true)) {
                    $mau['read'] = true;
                }

                // Role ICTT wajib punya semua akses di menu permissions
                if ($peran === 'ICTT' && $this->menu === 'permissions') {
                    $mau = array_fill_keys($aksiMenu, true);
                }

                foreach ($aksiMenu as $aksi) {
                    $punya = Enforcer::hasPolicy($peran, $this->menu, $aksi);

                    if ($mau[$aksi] && ! $punya) {
                        // Belum ada di Casbin -> insert
                        Enforcer::addPolicy($peran, $this->menu, $aksi);
                    } elseif (! $mau[$aksi] && $punya) {
                        // Sudah ada tapi dimatikan -> hapus
                        Enforcer::removePolicy($peran, $this->menu, $aksi);
                    }
                    // Sudah ada dan tetap aktif -> tidak perlu diubah
                }
            }
        });

        // Policy sudah tersimpan, buat cache izin semua role usang
        User::resetCacheIzin();

        $this->muat();

        $this->tersimpan = true;
    }

    public function render()
    {
        $daftarMenu = Navigation::permissionables();

        $perans = Role::query()
            ->orderBy('name')
            ->get(['code', 'name']);

        return view('livewire.akses-per-menu', [
            'daftarMenu' => $daftarMenu,
            'aksiMenu'   => $daftarMenu[$this->menu]['actions'] ?? [],
            'perans'     => $perans,
        ]);
    }
}
