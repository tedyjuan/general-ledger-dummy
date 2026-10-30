<?php

namespace App\Livewire;

use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Company;
use App\Models\Depo as DepoModel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Depo extends Component
{
    // CekIzin ditambahkan: menyediakan method izinkan($key, $aksi)
    use WithPagination, WithToast, Otorisasi;

    // Key menu di Casbin. Harus sama dengan 'key' di config/navigation.php
    // dan dengan middleware permission:depo,read di route.
    private const RESOURCE = 'depo';

    /* [B] STATE TABEL */
    public string $search        = '';
    public string $filterStatus  = '';
    public string $filterCompany = '';
    public int    $perPage       = 10;
    public string $sortField     = 'id';
    public string $sortDirection = 'desc';

    private const SORTABLE = [
        'id',
        'company_code',
        'code',
        'name',
        'alias',
        'area_code',
        'city',
        'status_depo',
        'fiscal_year',
    ];

    /* [C] STATE MODAL */
    public bool   $isModalOpen       = false;
    public bool   $isDeleteModalOpen = false;

    // #[Locked]: nilai ini TIDAK bisa diubah dari browser.
    // Tanpa ini, user bisa memalsukan selectedId lewat console Livewire
    // (misalnya mengubah mode tambah menjadi edit, atau menghapus ID sembarang).
    // Perubahan dari dalam class (create, edit, resetForm) tetap boleh.
    #[Locked]
    public ?int   $selectedId        = null;

    #[Locked]
    public ?int   $deleteId          = null;

    public string $deleteName        = '';

    /* [D] STATE FORM */
    public string $company_code = '';
    public string $code         = '';
    public string $name         = '';
    public string $alias        = '';
    public string $area_code    = '';
    public string $status_depo  = 'depo';
    public string $npwp         = '';
    public string $phone_no     = '';
    public string $city         = '';
    public string $address      = '';
    public string $postal_code  = '';
    public ?int   $fiscal_year  = null;

    protected array $messages = [
        'company_code.required' => 'Perusahaan wajib dipilih.',
        'company_code.exists'   => 'Perusahaan tidak valid.',
        'code.required'         => 'Kode depo wajib diisi.',
        'code.unique'           => 'Kode depo sudah digunakan pada perusahaan ini.',
        'name.required'         => 'Nama depo wajib diisi.',
        'name.unique'           => 'Nama depo sudah digunakan pada perusahaan ini.',
        'alias.required'        => 'Alias wajib diisi.',
        'alias.unique'          => 'Alias sudah digunakan pada perusahaan ini.',
        'area_code.required'    => 'Kode area wajib diisi.',
        'area_code.unique'      => 'Kode area sudah digunakan pada perusahaan ini.',
        'status_depo.required'  => 'Status depo wajib dipilih.',
        'status_depo.in'        => 'Status depo harus Pusat atau Depo.',
    ];

    /* [E] HOOK TABEL (tidak diubah) */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }
    public function updatingFilterCompany(): void
    {
        $this->resetPage();
    }
    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function refreshData(): void
    {
        $this->reset(['search', 'filterStatus', 'filterCompany']);
        $this->sortField     = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();

        $this->toast('Data depo berhasil dimuat ulang.', 'info');
    }

    /* [F] MODAL TAMBAH / EDIT */
    public function create(): void
    {
        // Hanya user dengan izin "create" yang boleh membuka form tambah
        $this->izinkan(self::RESOURCE, 'create');

        $this->resetForm();
        $this->resetErrorBag();
        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        // Hanya user dengan izin "update" yang boleh membuka form edit
        $this->izinkan(self::RESOURCE, 'update');

        $this->resetErrorBag();
        $depo = DepoModel::findOrFail($id);

        $this->selectedId   = $depo->id;
        $this->company_code = $depo->company_code;
        $this->code         = $depo->code ?? '';
        $this->name         = $depo->name ?? '';
        $this->alias        = $depo->alias ?? '';
        $this->area_code    = $depo->area_code ?? '';
        $this->status_depo  = $depo->status_depo ?? 'depo';
        $this->npwp         = $depo->npwp ?? '';
        $this->phone_no     = $depo->phone_no ?? '';
        $this->city         = $depo->city ?? '';
        $this->address      = $depo->address ?? '';
        $this->postal_code  = $depo->postal_code ?? '';
        $this->fiscal_year  = $depo->fiscal_year ?? (int) date('Y');

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        // save() dipakai untuk tambah dan edit, jadi izinnya mengikuti mode:
        // selectedId terisi = edit (update), kosong = tambah (create).
        // Dicek ulang di sini karena method ini bisa dipanggil langsung
        // tanpa melewati create()/edit().
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');

        $uniquePerCompany = fn(string $column) => Rule::unique('depos', $column)
            ->where(fn($q) => $q->where('company_code', $this->company_code))
            ->ignore($this->selectedId);

        try {
            $validated = $this->validate([
                'company_code' => ['required', 'exists:companies,code'],
                'code'         => ['required', 'string', 'max:20', $uniquePerCompany('code')],
                'name'         => ['required', 'string', 'max:255', $uniquePerCompany('name')],
                'alias'        => ['required', 'string', 'max:50', $uniquePerCompany('alias')],
                'area_code'    => ['required', 'string', 'max:50', $uniquePerCompany('area_code')],
                'status_depo'  => ['required', 'in:pusat,depo'],
                'npwp'         => ['nullable', 'string', 'max:30'],
                'phone_no'     => ['nullable', 'string', 'max:30'],
                'city'         => ['nullable', 'string', 'max:100'],
                'address'      => ['nullable', 'string'],
                'postal_code'  => ['nullable', 'string', 'max:20'],
                'fiscal_year'  => ['nullable', 'integer', 'min:2000', 'max:2100'],
            ]);
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        try {
            if ($this->selectedId) {
                $depo = DepoModel::findOrFail($this->selectedId);
                $depo->update($validated);
                $this->toast("Depo '{$depo->name}' berhasil diperbarui.");
            } else {
                $depo = DepoModel::create($validated);
                $this->toast("Depo '{$depo->name}' berhasil ditambahkan.");
            }
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal menyimpan data. Silakan coba lagi.', 'error');

            return;
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetErrorBag();
        $this->resetForm();
    }

    /* [G] MODAL HAPUS */
    public function confirmDelete(int $id): void
    {
        // Hanya user dengan izin "delete" yang boleh membuka konfirmasi hapus
        $this->izinkan(self::RESOURCE, 'delete');

        $depo = DepoModel::findOrFail($id);

        $this->deleteId          = $id;
        $this->deleteName        = $depo->name;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        // Wajib dicek ulang: tanpa ini, user bisa memanggil delete()
        // langsung dari console tanpa membuka modal konfirmasi.
        $this->izinkan(self::RESOURCE, 'delete');

        if ($this->deleteId) {
            try {
                $depo = DepoModel::findOrFail($this->deleteId);
                $name = $depo->name;
                $depo->delete();

                $this->toast("Depo '{$name}' berhasil dihapus.");
            } catch (QueryException $e) {
                report($e);
                $this->toast('Depo tidak bisa dihapus karena masih dipakai data lain.', 'error');
            }
        }

        $this->closeDeleteModal();
    }

    public function closeDeleteModal(): void
    {
        $this->isDeleteModalOpen = false;
        $this->deleteId          = null;
        $this->deleteName        = '';
    }

    private function resetForm(): void
    {
        $this->selectedId   = null;
        $this->company_code = '';
        $this->code         = '';
        $this->name         = '';
        $this->alias        = '';
        $this->area_code    = '';
        $this->status_depo  = 'depo';
        $this->npwp         = '';
        $this->phone_no     = '';
        $this->city         = '';
        $this->address      = '';
        $this->postal_code  = '';
        $this->fiscal_year  = (int) date('Y');
    }

    /* [H] RENDER */
    public function render()
    {
        // Middleware route hanya jalan saat halaman dimuat pertama kali.
        // Request Livewire berikutnya (search, sort, pagination) tidak
        // selalu melewatinya, jadi read dicek lagi di sini.
        $this->izinkan(self::RESOURCE, 'read');

        $depos = DepoModel::query()
            ->with('company')
            ->search($this->search)
            ->status($this->filterStatus)
            ->forCompany($this->filterCompany ?: null)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.master.depo', [
            'depos'     => $depos,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }
}
