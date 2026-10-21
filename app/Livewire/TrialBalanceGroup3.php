<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\TrialBalanceAccountGroup2;
use App\Models\TrialBalanceAccountGroup3;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TrialBalanceGroup3 extends Component
{
    use WithPagination;

    // --- Filter & Tampilan ---
    public string $search        = '';
    public string $filterCompany = '';
    public string $filterTbag2   = '';
    public int    $perPage       = 15;
    public string $sortField     = 'code';
    public string $sortDirection = 'asc';

    // --- State Modal ---
    public bool   $isModalOpen       = false;
    public bool   $isDeleteModalOpen = false;
    public ?int   $selectedId        = null;
    public ?int   $deleteId          = null;
    public string $deleteName        = '';

    // --- Field Form ---
    public ?int   $company_id  = null;
    public string $code        = '';
    public string $description = '';
    public ?int   $tbag_2      = null;

    protected array $messages = [
        'company_id.required'  => 'Perusahaan wajib dipilih.',
        'company_id.exists'    => 'Perusahaan tidak valid.',
        'code.required'        => 'Kode kelompok wajib diisi.',
        'code.unique'          => 'Kode kelompok sudah digunakan pada perusahaan ini.',
        'description.required' => 'Deskripsi kelompok wajib diisi.',
    ];

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterCompany(): void { $this->resetPage(); }
    public function updatingFilterTbag2(): void   { $this->resetPage(); }
    public function updatingPerPage(): void       { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        $defaultCompany   = Company::firstOrCreate(['name' => 'PT. Berkah Sentosa Abadi']);
        $this->company_id = $defaultCompany->id;
        $this->selectedId = null;
        $this->isModalOpen = true;
    }

    public function refreshData(): void
    {
        $this->search        = '';
        $this->filterCompany = '';
        $this->filterTbag2   = '';
        $this->sortField     = 'code';
        $this->sortDirection = 'asc';
        $this->resetPage();
        session()->flash('success', 'Data Kelompok Akun 3 berhasil dimuat ulang.');
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $record = TrialBalanceAccountGroup3::findOrFail($id);

        $this->selectedId  = $record->id;
        $this->company_id  = $record->company_id;
        $this->code        = $record->code ?? '';
        $this->description = $record->description ?? '';
        $this->tbag_2      = $record->tbag_2;

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'company_id'  => ['required', 'exists:companies,id'],
            'code'        => [
                'required', 'string', 'max:100',
                Rule::unique('trial_balance_account_group_3', 'code')
                    ->where(fn($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->selectedId),
            ],
            'description' => ['required', 'string', 'max:150'],
            'tbag_2'      => ['nullable', 'exists:trial_balance_account_group_2,id'],
        ]);

        if ($this->selectedId) {
            $record = TrialBalanceAccountGroup3::findOrFail($this->selectedId);
            $record->update($validated);
            session()->flash('success', "Kelompok Akun 3 '[{$record->code}] {$record->description}' berhasil diperbarui.");
        } else {
            $record = TrialBalanceAccountGroup3::create($validated);
            session()->flash('success', "Kelompok Akun 3 '[{$record->code}] {$record->description}' berhasil ditambahkan.");
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $record = TrialBalanceAccountGroup3::findOrFail($id);
        $this->deleteId          = $id;
        $this->deleteName        = "[{$record->code}] {$record->description}";
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            $record = TrialBalanceAccountGroup3::findOrFail($this->deleteId);
            $name   = "[{$record->code}] {$record->description}";
            $record->delete();
            session()->flash('success', "Kelompok Akun 3 '{$name}' berhasil dihapus.");
        }
        $this->closeDeleteModal();
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetErrorBag();
        $this->resetForm();
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
        $this->company_id  = null;
        $this->code        = '';
        $this->description = '';
        $this->tbag_2      = null;
    }

    public function render()
    {
        $query = TrialBalanceAccountGroup3::query()
            ->with(['company', 'group2.group1'])
            ->when($this->search, fn($q) => $q->where(function ($q2) {
                $q2->where('code', 'like', "%{$this->search}%")
                   ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->when($this->filterTbag2 !== '', fn($q) => $q->where('tbag_2', (int) $this->filterTbag2))
            ->when(
                $this->filterCompany !== '',
                fn($q) => $q->where('company_id', (int) $this->filterCompany)
            );

        $records   = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
        $companies = Company::orderBy('name')->get();

        $tbag2List = TrialBalanceAccountGroup2::with('group1')
            ->when(
                $this->company_id,
                fn($q) => $q->where('company_id', $this->company_id)
            )->orderBy('code')->get();

        $tbag2FilterList = TrialBalanceAccountGroup2::with('group1')->orderBy('code')->get();

        return view('livewire.master.trial-balance-group3', compact('records', 'companies', 'tbag2List', 'tbag2FilterList'));
    }
}
