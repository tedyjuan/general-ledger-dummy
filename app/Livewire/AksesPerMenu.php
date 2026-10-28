<?php

namespace App\Livewire;

use App\Models\Role;
use App\Livewire\Concerns\Otorisasi;
use App\Support\Navigation;
use Lauthz\Facades\Enforcer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')] // sesuaikan dengan layout utama Anda
#[Title('Akses per Menu')]
class AksesPerMenu extends Component
{
    use Otorisasi;

    /** Key menu yang sedang diatur, mis. "depo" */
    public string $menu = '';

    /** $matrix[indeksPeran][aksi] = true/false */
    public array $matrix = [];

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
        // Validasi hak akses user yang sedang login
        $this->izinkan('permissions', 'manage');

        $daftarMenu = Navigation::permissionables();

        abort_unless(isset($daftarMenu[$this->menu]), 422);

        $aksiMenu = $daftarMenu[$this->menu]['actions'];

        // Ambil daftar role dari database
        $perans = Role::query()
            ->orderBy('name')
            ->get(['code']);

        foreach ($perans as $i => $role) {
            $peran = $role->code;

            $mau = [];

            foreach ($aksiMenu as $aksi) {
                $mau[$aksi] = (bool) ($this->matrix[$i][$aksi] ?? false);
            }

            // Jika aksi lain dicentang, read otomatis aktif
            if (in_array('read', $aksiMenu, true) && in_array(true, $mau, true)) {
                $mau['read'] = true;
            }

            // Pengaman: role ICTT tidak boleh kehilangan akses
            // untuk mengelola hak akses
            if ($peran === 'ICTT' && $this->menu === 'permissions') {
                $mau = array_fill_keys($aksiMenu, true);
            }

            foreach ($aksiMenu as $aksi) {
                $punya = Enforcer::hasPolicy($peran, $this->menu, $aksi);

                if ($mau[$aksi] && ! $punya) {
                    Enforcer::addPolicy($peran, $this->menu, $aksi);
                } elseif (! $mau[$aksi] && $punya) {
                    Enforcer::removePolicy($peran, $this->menu, $aksi);
                }
            }
        }

        // Muat ulang permission setelah disimpan
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
