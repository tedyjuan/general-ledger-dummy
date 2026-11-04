<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Manajemen User')]
class Users extends Component
{
    use WithPagination;

    private const KOLOM_URUT = ['name', 'username', 'role_code'];
    private const OPSI_PER_HALAMAN = [5, 10, 25, 50];

    #[Url(as: 'q')]
    public string $search = '';

    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Modal dibuka/ditutup oleh Alpine di browser, server hanya menyimpan data form
    public ?int $selectedId = null;

    public string $name = '';
    public string $username = '';
    public string $role_code = '';
    public string $password = '';
    public bool $is_password_default = true;

    /** @var array<int,string> kode company yang boleh diakses user */
    public array $companyCodes = [];

    public function mount(): void
    {
        $this->otorisasi('read');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::KOLOM_URUT, true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc')
            ? 'desc'
            : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function refreshData(): void
    {
        $this->reset('search');
        $this->resetPage();

        // Muat ulang daftar role & company (di-cache 5 menit)
        Cache::forget('opsi:user:role');
        Cache::forget('opsi:user:company');
    }

    public function create(): void
    {
        $this->otorisasi('create');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->otorisasi('update');
        $this->resetForm();

        $user = User::findOrFail($id);

        $this->selectedId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->role_code = $user->role_code;
        $this->is_password_default = (bool) $user->is_password_default;
        $this->companyCodes = CompanyUser::where('username', $user->username)
            ->pluck('company_code')
            ->all();
    }

    public function save(): void
    {
        $editing = $this->selectedId !== null;
        $this->otorisasi($editing ? 'update' : 'create');

        // Username tidak boleh diubah saat edit (dipakai sebagai kunci di company_user).
        // Diambil ulang dari DB supaya tidak bisa dimanipulasi dari browser.
        if ($editing) {
            $this->username = User::findOrFail($this->selectedId)->username;
        }

        $this->name = trim($this->name);
        $this->username = Str::lower(trim($this->username));

        $this->validate();

        DB::transaction(function () use ($editing) {
            if ($editing) {
                $user = User::findOrFail($this->selectedId);
                $user->name = $this->name;
                $user->is_password_default = $this->is_password_default;

                // Cegah super admin mengunci dirinya sendiri dengan mengganti role sendiri
                if ($user->id !== auth()->id()) {
                    $user->role_code = $this->role_code;
                }

                if ($this->password !== '') {
                    $user->password_hash = Hash::make($this->password);
                }

                $user->save();
            } else {
                $user = User::create([
                    'name'                => $this->name,
                    'username'            => $this->username,
                    'role_code'           => $this->role_code,
                    'password_hash'       => Hash::make($this->password),
                    'is_password_default' => $this->is_password_default,
                ]);
            }

            $this->sinkronCompany($user->username, $this->companyCodes);
        });

        $this->dispatch('modal-close');
        $this->dispatch(
            'toast',
            type: 'success',
            message: $editing ? 'User berhasil diperbarui.' : 'User berhasil ditambahkan.'
        );

        $this->resetForm();
    }

    public function pilihSemuaCompany(): void
    {
        $this->otorisasi('update');
        $this->companyCodes = array_column($this->daftarCompany(), 'code');
    }

    public function kosongkanCompany(): void
    {
        $this->companyCodes = [];
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    /**
     * Samakan isi company_user dengan pilihan: hapus yang dicabut, tambah yang baru.
     * Hanya menyentuh baris yang berubah.
     */
    protected function sinkronCompany(string $username, array $codes): void
    {
        $codes = array_values(array_unique($codes));
        $lama  = CompanyUser::where('username', $username)->pluck('company_code')->all();

        $hapus  = array_diff($lama, $codes);
        $tambah = array_diff($codes, $lama);

        if ($hapus) {
            CompanyUser::where('username', $username)
                ->whereIn('company_code', $hapus)
                ->delete();

            // Cache validasi akses company (kalau middleware memakai key ini)
            foreach ($hapus as $code) {
                Cache::forget("uc:{$username}:{$code}");
            }
        }

        if ($tambah) {
            CompanyUser::insert(array_map(
                fn($code) => ['company_code' => $code, 'username' => $username],
                $tambah
            ));
        }
    }

    protected function otorisasi(string $aksi): void
    {
        // Dipanggil di setiap aksi: method Livewire bisa dipanggil langsung dari browser.
        abort_unless(auth()->user()?->izin('user', $aksi), 403);
    }

    protected function resetForm(): void
    {
        $this->reset(['selectedId', 'name', 'username', 'role_code', 'password', 'companyCodes']);
        $this->is_password_default = true;
        $this->resetValidation();
    }

    /** @return array<int,string> */
    protected function daftarRole(): array
    {
        return Cache::remember(
            'opsi:user:role:v2',
            300,
            fn() => Role::orderBy('code')->get(['code', 'name'])
                ->map(fn($r) => ['code' => $r->code, 'name' => $r->name])
                ->all()
        );
    }

    /** @return array<int,array{code:string,name:string}> */
    protected function daftarCompany(): array
    {
        return Cache::remember(
            'opsi:user:company',
            300,
            fn() => Company::orderBy('code')->get(['code', 'name'])
                ->map(fn($c) => ['code' => $c->code, 'name' => $c->name])
                ->all()
        );
    }

    protected function rules(): array
    {
        $editing = $this->selectedId !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($this->selectedId),
            ],
            'role_code' => ['required', Rule::exists('roles', 'code')],
            'password' => $editing
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'is_password_default' => ['boolean'],
            'companyCodes' => ['required', 'array', 'min:1'],
            'companyCodes.*' => ['string', Rule::exists('companies', 'code')],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'         => 'Nama wajib diisi.',
            'username.required'     => 'Username wajib diisi.',
            'username.unique'       => 'Username sudah dipakai.',
            'username.regex'        => 'Username hanya boleh huruf kecil, angka, titik, garis bawah, dan strip.',
            'username.max'          => 'Username maksimal 50 karakter.',
            'role_code.required'    => 'Role wajib dipilih.',
            'role_code.exists'      => 'Role tidak valid.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 8 karakter.',
            'companyCodes.required' => 'Pilih minimal 1 company.',
            'companyCodes.min'      => 'Pilih minimal 1 company.',
        ];
    }

    public function render()
    {
        // Nilai dari browser bisa dimanipulasi: validasi sebelum dipakai
        $perPage = in_array($this->perPage, self::OPSI_PER_HALAMAN, true) ? $this->perPage : 10;
        $field   = in_array($this->sortField, self::KOLOM_URUT, true) ? $this->sortField : 'name';
        $arah    = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $users = User::search($this->search)
            ->orderBy($field, $arah)
            ->orderBy('id')
            ->paginate($perPage);

        // 1 query untuk company semua user di halaman ini (hindari N+1)
        $akses = CompanyUser::whereIn('username', $users->pluck('username'))
            ->get(['username', 'company_code'])
            ->groupBy('username');

        return view('livewire.master.users', [
            'users'     => $users,
            'akses'     => $akses,
            'roles'     => $this->daftarRole(),
            'companies' => $this->daftarCompany(),
        ]);
    }
}
