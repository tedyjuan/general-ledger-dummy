<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Depo as DepoModel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Depo extends Component
{
    use WithPagination, WithToast, Otorisasi, ActiveCompany;

    private const RESOURCE = 'depo';

    /* STATE TABEL */
    public string $search        = '';
    public string $filterStatus  = '';
    public int    $perPage       = 10;
    public string $sortField     = 'id';
    public string $sortDirection = 'desc';

    private const SORTABLE = [
        'id',
        'code',
        'name',
        'alias',
        'area_code',
        'city',
        'status_depo',
        'fiscal_year',
    ];

    /* STATE MODAL */
    public bool $isModalOpen       = false;
    public bool $isDeleteModalOpen = false;

    #[Locked]
    public ?int $selectedId = null;

    #[Locked]
    public ?int $deleteId = null;

    public string $deleteName = '';

    /* STATE FORM (company_code dihapus: selalu dari session) */
    public string $code        = '';
    public string $name        = '';
    public string $alias       = '';
    public string $area_code   = '';
    public string $status_depo = 'depo';
    public string $npwp        = '';
    public string $phone_no    = '';
    public string $city        = '';
    public string $address     = '';
    public string $postal_code = '';
    public ?int   $fiscal_year = null;

    protected array $messages = [
        'code.required'        => 'Kode depo wajib diisi.',
        'code.unique'          => 'Kode depo sudah digunakan pada perusahaan ini.',
        'name.required'        => 'Nama depo wajib diisi.',
        'name.unique'          => 'Nama depo sudah digunakan pada perusahaan ini.',
        'alias.required'       => 'Alias wajib diisi.',
        'alias.unique'         => 'Alias sudah digunakan pada perusahaan ini.',
        'area_code.required'   => 'Kode area wajib diisi.',
        'area_code.unique'     => 'Kode area sudah digunakan pada perusahaan ini.',
        'status_depo.required' => 'Status depo wajib dipilih.',
        'status_depo.in'       => 'Status depo harus Pusat atau Depo.',
    ];

    /* HOOK TABEL */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
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
        $this->reset(['search', 'filterStatus']);
        $this->sortField     = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();

        $this->toast('Data depo berhasil dimuat ulang.', 'info');
    }

    /* MODAL TAMBAH / EDIT */
    public function create(): void
    {
        $this->izinkan(self::RESOURCE, 'create');
        $this->ensureSameCompany();

        $this->resetForm();
        $this->resetErrorBag();
        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        $this->resetErrorBag();

        // Global scope: ID milik company lain otomatis 404
        $depo = DepoModel::findOrFail($id);

        $this->selectedId  = $depo->id;
        $this->code        = $depo->code ?? '';
        $this->name        = $depo->name ?? '';
        $this->alias       = $depo->alias ?? '';
        $this->area_code   = $depo->area_code ?? '';
        $this->status_depo = $depo->status_depo ?? 'depo';
        $this->npwp        = $depo->npwp ?? '';
        $this->phone_no    = $depo->phone_no ?? '';
        $this->city        = $depo->city ?? '';
        $this->address     = $depo->address ?? '';
        $this->postal_code = $depo->postal_code ?? '';
        $this->fiscal_year = $depo->fiscal_year ?? (int) date('Y');

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');
        $this->ensureSameCompany();

        $company = $this->tabCompany;

        // Rule::unique langsung ke database (tidak lewat global scope),
        // jadi where company_code tetap wajib
        $uniquePerCompany = fn(string $column) => Rule::unique('depos', $column)
            ->where(fn($q) => $q->where('company_code', $company))
            ->ignore($this->selectedId);

        try {
            $validated = $this->validate([
                'code'        => ['required', 'string', 'max:20', $uniquePerCompany('code')],
                'name'        => ['required', 'string', 'max:255', $uniquePerCompany('name')],
                'alias'       => ['required', 'string', 'max:50', $uniquePerCompany('alias')],
                'area_code'   => ['required', 'string', 'max:50', $uniquePerCompany('area_code')],
                'status_depo' => ['required', 'in:pusat,depo'],
                'npwp'        => ['nullable', 'string', 'max:30'],
                'phone_no'    => ['nullable', 'string', 'max:30'],
                'city'        => ['nullable', 'string', 'max:100'],
                'address'     => ['nullable', 'string'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'fiscal_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            ]);
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        // Company selalu dari session, bukan dari form
        $validated['company_code'] = $company;

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

    /* MODAL HAPUS */
    public function confirmDelete(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'delete');
        $this->ensureSameCompany();

        $depo = DepoModel::findOrFail($id);

        $this->deleteId          = $id;
        $this->deleteName        = $depo->name;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        $this->izinkan(self::RESOURCE, 'delete');
        $this->ensureSameCompany();

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
        $this->selectedId  = null;
        $this->code        = '';
        $this->name        = '';
        $this->alias       = '';
        $this->area_code   = '';
        $this->status_depo = 'depo';
        $this->npwp        = '';
        $this->phone_no    = '';
        $this->city        = '';
        $this->address     = '';
        $this->postal_code = '';
        $this->fiscal_year = (int) date('Y');
    }

    /* RENDER */
    public function render()
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        // Tidak ada where company_code di sini: sudah ditangani global scope di model
        $depos = DepoModel::query()
            ->search($this->search)
            ->status($this->filterStatus)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.master.depo', [
            'depos'         => $depos,
            'activeCompany' => $this->tabCompany,
        ]);
    }
}
