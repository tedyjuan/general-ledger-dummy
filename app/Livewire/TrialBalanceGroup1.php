<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\TrialBalanceAccountGroup1;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TrialBalanceGroup1 extends Component
{
    use WithPagination;

    // --- Filter & Tampilan ---
    public string $search        = '';
    public string $filterCompany = '';
    public string $filterType    = '';
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
    public ?int   $company_id   = null;
    public string $code         = '';
    public string $description  = '';
    public string $account_type = '';

    public const ACCOUNT_TYPES = [
        'asset'                    => 'Asset / Aktiva',
        'liability'                => 'Liability / Kewajiban',
        'equity'                   => 'Equity / Modal',
        'income'                   => 'Income / Pendapatan',
        'expense'                  => 'Expense / Beban',
        'retained earning account' => 'Retained Earning',
    ];

    protected array $messages = [
        'company_id.required'  => 'Perusahaan wajib dipilih.',
        'company_id.exists'    => 'Perusahaan tidak valid.',
        'code.required'        => 'Kode kelompok wajib diisi.',
        'code.unique'          => 'Kode kelompok sudah digunakan pada perusahaan ini.',
        'description.required' => 'Deskripsi kelompok wajib diisi.',
    ];

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterCompany(): void { $this->resetPage(); }
    public function updatingFilterType(): void    { $this->resetPage(); }
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
        $this->filterType    = '';
        $this->sortField     = 'code';
        $this->sortDirection = 'asc';
        $this->resetPage();
        session()->flash('success', 'Data Kelompok Akun 1 berhasil dimuat ulang.');
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $record = TrialBalanceAccountGroup1::findOrFail($id);

        $this->selectedId   = $record->id;
        $this->company_id   = $record->company_id;
        $this->code         = $record->code ?? '';
        $this->description  = $record->description ?? '';
        $this->account_type = $record->account_type ?? '';

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'company_id'   => ['required', 'exists:companies,id'],
            'code'         => [
                'required', 'string', 'max:100',
                Rule::unique('trial_balance_account_group_1', 'code')
                    ->where(fn($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->selectedId),
            ],
            'description'  => ['required', 'string', 'max:150'],
            'account_type' => ['nullable', Rule::in(array_keys(self::ACCOUNT_TYPES))],
        ]);

        if ($validated['account_type'] === '') {
            $validated['account_type'] = null;
        }

        if ($this->selectedId) {
            $record = TrialBalanceAccountGroup1::findOrFail($this->selectedId);
            $record->update($validated);
            session()->flash('success', "Kelompok Akun 1 '[{$record->code}] {$record->description}' berhasil diperbarui.");
        } else {
            $record = TrialBalanceAccountGroup1::create($validated);
            session()->flash('success', "Kelompok Akun 1 '[{$record->code}] {$record->description}' berhasil ditambahkan.");
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $record = TrialBalanceAccountGroup1::findOrFail($id);
        $this->deleteId          = $id;
        $this->deleteName        = "[{$record->code}] {$record->description}";
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            $record = TrialBalanceAccountGroup1::findOrFail($this->deleteId);
            $name   = "[{$record->code}] {$record->description}";
            $record->delete();
            session()->flash('success', "Kelompok Akun 1 '{$name}' berhasil dihapus.");
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
        $this->selectedId   = null;
        $this->company_id   = null;
        $this->code         = '';
        $this->description  = '';
        $this->account_type = '';
    }

    public function render()
    {
        $query = TrialBalanceAccountGroup1::query()
            ->with('company')
            ->when($this->search, fn($q) => $q->where(function ($q2) {
                $q2->where('code', 'like', "%{$this->search}%")
                   ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->when($this->filterType, fn($q) => $q->where('account_type', $this->filterType))
            ->when(
                $this->filterCompany !== '',
                fn($q) => $q->where('company_id', (int) $this->filterCompany)
            );

        $records   = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
        $companies = Company::orderBy('name')->get();

        return view('livewire.master.trial-balance-group1', compact('records', 'companies'));
    }
}
