<?php

namespace App\Livewire;

use App\Livewire\Concerns\Otorisasi;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Support\Facades\DB;
use Lauthz\Facades\Enforcer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Akses per Menu')]
class AksesPerMenu extends Component
{
    use Otorisasi;
    private const AKSI_STANDAR = [
        'read',
        'create',
        'update',
        'delete',
    ];

    private const ROLE_DEFAULT = 'ICTT';

    public string $menu = '';

    /**
     * Struktur:
     * matrix[role_code][action] = true/false
     */
    public array $matrix = [];

    public bool $tersimpan = false;

    public function mount(): void
    {
        $this->izinkan('permissions', 'manage');

        // Generate policy default ICTT untuk menu baru
        $this->generatePolicyIctt();

        $daftarMenu = Navigation::permissionables();

        $this->menu = (string) array_key_first($daftarMenu);

        $this->muat();
    }

    /**
     * Ketika menu diganti
     */
    public function updatedMenu(): void
    {
        abort_unless(
            array_key_exists($this->menu, Navigation::permissionables()),
            422
        );

        $this->tersimpan = false;

        $this->muat();
    }

    /**
     * Mengambil daftar action menu.
     * Action standar + action tambahan.
     */
    private function aksiMenu(string $menu): array
    {
        $extra = Navigation::permissionables()[$menu]['actions'] ?? [];

        return array_values(array_unique(array_merge(
            self::AKSI_STANDAR,
            $extra
        )));
    }

    /**
     * Generate policy default untuk ICTT.
     *
     * Jika belum ada policy pada menu:
     * insert read, create, update, delete.
     *
     * Jika sudah ada:
     * tidak melakukan perubahan.
     */
    private function generatePolicyIctt(): void
    {
        $dibuat = 0;

        DB::transaction(function () use (&$dibuat) {

            foreach (array_keys(Navigation::permissionables()) as $menu) {

                $sudahAda = Enforcer::getFilteredPolicy(
                    0,
                    self::ROLE_DEFAULT,
                    $menu
                );

                if (! empty($sudahAda)) {
                    continue;
                }

                foreach (self::AKSI_STANDAR as $aksi) {

                    if (Enforcer::addPolicy(
                        self::ROLE_DEFAULT,
                        $menu,
                        $aksi
                    )) {
                        $dibuat++;
                    }
                }
            }
        });

        if ($dibuat > 0) {
            User::resetCacheIzin();
        }
    }

    /**
     * Load policy berdasarkan kode role.
     */
    protected function muat(): void
    {
        $aksiMenu = $this->aksiMenu($this->menu);

        $perans = Role::query()
            ->orderBy('name')
            ->pluck('code');

        $this->matrix = [];

        foreach ($perans as $peran) {

            foreach ($aksiMenu as $aksi) {

                $this->matrix[$peran][$aksi] = Enforcer::hasPolicy(
                    $peran,
                    $this->menu,
                    $aksi
                );
            }
        }
    }

    /**
     * Simpan perubahan permission.
     */
    public function simpan(): void
    {
        $this->izinkan('permissions', 'manage');

        abort_unless(
            array_key_exists($this->menu, Navigation::permissionables()),
            422
        );

        $aksiMenu = $this->aksiMenu($this->menu);

        $perans = Role::query()
            ->orderBy('name')
            ->pluck('code');

        DB::transaction(function () use ($perans, $aksiMenu) {

            foreach ($perans as $peran) {

                $baris = $this->matrix[$peran] ?? [];

                $mau = [];

                // Ambil nilai masing-masing checkbox
                foreach ($aksiMenu as $aksi) {

                    $mau[$aksi] = (bool) ($baris[$aksi] ?? false);
                }

                // Jika create, update, atau delete aktif,
                // maka read otomatis aktif.
                if (
                    ($mau['create'] ?? false) ||
                    ($mau['update'] ?? false) ||
                    ($mau['delete'] ?? false)
                ) {
                    $mau['read'] = true;
                }

                // ICTT wajib memiliki seluruh akses
                // pada menu permissions.
                if (
                    $peran === self::ROLE_DEFAULT &&
                    $this->menu === 'permissions'
                ) {
                    $mau = array_fill_keys($aksiMenu, true);
                }

                // Sinkronisasi policy
                foreach ($aksiMenu as $aksi) {

                    $punya = Enforcer::hasPolicy(
                        $peran,
                        $this->menu,
                        $aksi
                    );

                    // Insert policy jika dicentang
                    if ($mau[$aksi] && ! $punya) {

                        Enforcer::addPolicy(
                            $peran,
                            $this->menu,
                            $aksi
                        );
                    }

                    // Hapus policy jika tidak dicentang
                    elseif (! $mau[$aksi] && $punya) {

                        Enforcer::removePolicy(
                            $peran,
                            $this->menu,
                            $aksi
                        );
                    }
                }
            }
        });

        // Reset cache permission
        User::resetCacheIzin();

        // Refresh checkbox dari database
        $this->muat();

        $this->tersimpan = true;
    }

    public function render()
    {
        return view('livewire.akses-per-menu', [
            'daftarMenu' => Navigation::permissionables(),

            'aksiMenu' => $this->aksiMenu($this->menu),

            'perans' => Role::query()
                ->orderBy('name')
                ->get(['code', 'name']),
        ]);
    }
}
