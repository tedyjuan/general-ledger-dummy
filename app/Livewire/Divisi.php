<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithToast;
use App\Models\Company;
use App\Models\Divisi as DivisiModel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Divisi extends Component
{
    use WithPagination, WithToast;

    /* [B] STATE TABEL */
    public string $search        = '';
    public string $filterStatus  = '';
    public string $filterCompany = '';   // company code (TVIP/ASA), BUKAN id
    public int    $perPage       = 10;
    public string $sortField     = 'id';
    public string $sortDirection = 'desc';

    /** Whitelist kolom sort */
    private const SORTABLE = [
        'id',
        'code',
        'name',
        'alias',
        'company_code',
    ];


    /* [C] STATE MODAL */
    public ?int $selectedId = null;

    /* [D] STATE FORM */
    public string $company_code = '';
    public string $code         = '';
    public string $name         = '';
    public string $alias        = '';

    protected array $messages = [
        'company_code.required' => 'Perusahaan wajib dipilih.',
        'company_code.exists'   => 'Perusahaan tidak valid.',
        'code.required'         => 'Kode Divisi wajib diisi.',
        'code.unique'           => 'Kode Divisi sudah digunakan pada perusahaan ini.',
        'name.required'         => 'Nama Divisi wajib diisi.',
        'name.unique'           => 'Nama Divisi sudah digunakan pada perusahaan ini.',
        'alias.required'        => 'Alias wajib diisi.',
        'alias.unique'          => 'Alias sudah digunakan pada perusahaan ini.',
    ];

    /* [E] HOOK TABEL */
    public function updatingSearch(): void
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
        $this->reset(['search', 'filterCompany']);
        $this->sortField     = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();

        $this->toast('Data Divisi berhasil dimuat ulang.', 'info');
    }

    /* [F] MODAL TAMBAH / EDIT */
    public function create(): void
    {
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $divisi = DivisiModel::findOrFail($id);

        $this->selectedId   = $divisi->id;
        $this->company_code = $divisi->company_code;
        $this->code         = $divisi->code ?? '';
        $this->name         = $divisi->name ?? '';
        $this->alias        = $divisi->alias ?? '';
    }

    public function save(): void
    {
        $uniquePerCompany = fn(string $column) => Rule::unique('divisions', $column)
            ->where(fn($q) => $q->where('company_code', $this->company_code))
            ->ignore($this->selectedId);

        try {
            $validated = $this->validate([
                'company_code' => ['required', 'exists:companies,code'],
                'code'         => ['required', 'string', 'max:20', $uniquePerCompany('code')],
                'name'         => ['required', 'string', 'max:255', $uniquePerCompany('name')],
                'alias'        => ['required', 'string', 'max:50', $uniquePerCompany('alias')]
            ]);
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        try {
            if ($this->selectedId) {
                $divisi = DivisiModel::findOrFail($this->selectedId);
                $divisi->update($validated);
                $this->toast("Divisi '{$divisi->name}' berhasil diperbarui.");
            } else {
                $divisi = DivisiModel::create($validated);
                $this->toast("Divisi '{$divisi->name}' berhasil ditambahkan.");
            }
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal menyimpan data. Silakan coba lagi.', 'error');

            return;
        }

        $this->closeModal();
        $this->dispatch('modal-close');
    }

    public function closeModal(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
    }



    public function delete(): void
    {
        try {
            $divisi = DivisiModel::findOrFail($this->deleteId);
            $name = $divisi->name;
            $divisi->delete();

            $this->toast("Divisi '{$name}' berhasil dihapus.");
        } catch (QueryException $e) {
            report($e);
            $this->toast('Divisi tidak bisa dihapus karena masih dipakai data lain.', 'error');
        }
        $this->dispatch('delete-close');
    }


    private function resetForm(): void
    {
        $this->selectedId   = null;
        $this->company_code = '';
        $this->code         = '';
        $this->name         = '';
        $this->alias        = '';
    }

    /* [H] RENDER */
    public function render()
    {
        $divisions = DivisiModel::query()
            ->with('company')
            ->search($this->search)
            ->forCompany($this->filterCompany ?: null)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.master.divisi', [
            'divisions' => $divisions,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }
}
