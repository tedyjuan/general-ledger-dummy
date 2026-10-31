<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Divisi as DivisiModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Divisi extends Component
{
    use WithPagination, WithToast, Otorisasi, ActiveCompany;

    // Harus sama dengan key menu di Casbin / config/navigation.php
    private const RESOURCE = 'divisi';

    /* STATE TABEL */
    public string $search        = '';
    public int    $perPage       = 10;
    public string $sortField     = 'id';
    public string $sortDirection = 'desc';

    private const SORTABLE = ['id', 'code', 'name', 'alias'];

    /* STATE MODAL */
    #[Locked]
    public ?int $selectedId = null;

    /* STATE FORM (company_code dihapus: selalu dari session) */
    public string $code  = '';
    public string $name  = '';
    public string $alias = '';

    protected array $messages = [
        'code.required'  => 'Kode Divisi wajib diisi.',
        'code.unique'    => 'Kode Divisi sudah digunakan pada perusahaan ini.',
        'name.required'  => 'Nama Divisi wajib diisi.',
        'name.unique'    => 'Nama Divisi sudah digunakan pada perusahaan ini.',
        'alias.required' => 'Alias wajib diisi.',
        'alias.unique'   => 'Alias sudah digunakan pada perusahaan ini.',
    ];

    /* HOOK TABEL */
    public function updatingSearch(): void
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
        $this->reset(['search']);
        $this->sortField     = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();

        $this->toast('Data Divisi berhasil dimuat ulang.', 'info');
    }

    /* TAMBAH / EDIT */
    public function create(): void
    {
        $this->izinkan(self::RESOURCE, 'create');
        $this->ensureSameCompany();

        $this->resetForm();
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        $this->resetErrorBag();

        // Global scope: ID milik company lain otomatis 404
        $divisi = DivisiModel::findOrFail($id);

        $this->selectedId = $divisi->id;
        $this->code       = $divisi->code ?? '';
        $this->name       = $divisi->name ?? '';
        $this->alias      = Str::upper($divisi->alias ?? '');
    }

    public function save(): void
    {
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');
        $this->ensureSameCompany();

        $company     = $this->tabCompany;
        $this->alias = Str::upper(trim($this->alias));

        // Rule::unique langsung ke database (tidak lewat global scope),
        // jadi where company_code tetap wajib
        $uniquePerCompany = fn(string $column) => Rule::unique('divisions', $column)
            ->where(fn($q) => $q->where('company_code', $company))
            ->ignore($this->selectedId);

        try {
            $validated = $this->validate([
                'code'  => ['required', 'string', 'max:20', $uniquePerCompany('code')],
                'name'  => ['required', 'string', 'max:255', $uniquePerCompany('name')],
                'alias' => ['required', 'string', 'max:50', $uniquePerCompany('alias')],
            ]);
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        // Company selalu dari session, bukan dari form
        $validated['company_code'] = $company;

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

    /* HAPUS */
    public function delete(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'delete');
        $this->ensureSameCompany();

        try {
            // Global scope: ID milik company lain otomatis 404
            $divisi = DivisiModel::findOrFail($id);
            $name   = $divisi->name;

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
        $this->selectedId = null;
        $this->code       = '';
        $this->name       = '';
        $this->alias      = '';
    }

    /* RENDER */
    public function render()
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        // Tidak ada where company_code: sudah ditangani global scope di model
        $divisions = DivisiModel::query()
            ->search($this->search)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.master.divisi', [
            'divisions'     => $divisions,
            'activeCompany' => $this->tabCompany,
        ]);
    }
}
