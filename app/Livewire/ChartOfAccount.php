<?php

namespace App\Livewire;

use App\Models\ChartOfAccount as CoaModel;
use App\Models\Company;
use App\Models\TrialBalanceAccountGroup1;
use App\Models\TrialBalanceAccountGroup2;
use App\Models\TrialBalanceAccountGroup3;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ChartOfAccount extends Component
{
    use WithPagination;

    // --- Filter & Tampilan ---
    public string $search        = '';
    public string $filterType    = '';
    public string $filterMethod  = '';
    public string $filterGroup   = '';
    public string $filterCompany = '';
    public int    $perPage       = 15;
    public string $sortField     = 'account_number';
    public string $sortDirection = 'asc';

    // --- State Modal ---
    public bool   $isModalOpen       = false;
    public bool   $isDeleteModalOpen = false;
    public ?int   $selectedId        = null;
    public ?int   $deleteId          = null;
    public string $deleteName        = '';

    // --- Field Form ---
    public ?int   $account_number   = null;
    public ?int   $company_id       = null;
    public ?int   $header_id        = null;
    public ?int   $reference_id     = null;
    public string $name             = '';
    public string $description      = '';
    public ?int   $tbag_1           = null;
    public ?int   $tbag_2           = null;
    public ?int   $tbag_3           = null;
    public string $account_type     = '';
    public string $account_method   = '';
    public string $account_group    = '';
    public string $cost_center_type = '';

    public const ACCOUNT_TYPES   = ['asset', 'liability', 'equity', 'income', 'expense', 'retained earning account'];
    public const ACCOUNT_METHODS = ['debit', 'credit'];
    public const ACCOUNT_GROUPS  = ['kas', 'bank', 'inventory', 'sales'];
    public const CC_TYPES        = ['all', 'depo', 'unit'];

    protected array $messages = [
        'account_number.required' => 'Nomor akun wajib diisi.',
        'account_number.integer'  => 'Nomor akun harus berupa angka.',
        'account_number.unique'   => 'Nomor akun sudah digunakan pada perusahaan ini.',
        'company_id.required'     => 'Perusahaan wajib dipilih.',
        'company_id.exists'       => 'Perusahaan tidak valid.',
        'name.required'           => 'Nama akun wajib diisi.',
    ];

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterType(): void    { $this->resetPage(); }
    public function updatingFilterMethod(): void  { $this->resetPage(); }
    public function updatingFilterGroup(): void   { $this->resetPage(); }
    public function updatingFilterCompany(): void { $this->resetPage(); }
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
        $this->filterType    = '';
        $this->filterMethod  = '';
        $this->filterGroup   = '';
        $this->filterCompany = '';
        $this->sortField     = 'account_number';
        $this->sortDirection = 'asc';
        $this->resetPage();
        session()->flash('success', 'Data COA berhasil dimuat ulang.');
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $coa = CoaModel::findOrFail($id);

        $this->selectedId       = $coa->id;
        $this->account_number   = $coa->account_number;
        $this->company_id       = $coa->company_id;
        $this->header_id        = $coa->header_id;
        $this->reference_id     = $coa->reference_id;
        $this->name             = $coa->name ?? '';
        $this->description      = $coa->description ?? '';
        $this->tbag_1           = $coa->tbag_1;
        $this->tbag_2           = $coa->tbag_2;
        $this->tbag_3           = $coa->tbag_3;
        $this->account_type     = $coa->account_type ?? '';
        $this->account_method   = $coa->account_method ?? '';
        $this->account_group    = $coa->account_group ?? '';
        $this->cost_center_type = $coa->cost_center_type ?? '';

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'account_number'   => [
                'required', 'integer',
                Rule::unique('chart_of_accounts', 'account_number')
                    ->where(fn($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->selectedId),
            ],
            'company_id'       => ['required', 'exists:companies,id'],
            'header_id'        => ['nullable', 'exists:chart_of_accounts,id'],
            'reference_id'     => ['nullable', 'exists:chart_of_accounts,id'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:500'],
            'tbag_1'           => ['nullable', 'exists:trial_balance_account_group_1,id'],
            'tbag_2'           => ['nullable', 'exists:trial_balance_account_group_2,id'],
            'tbag_3'           => ['nullable', 'exists:trial_balance_account_group_3,id'],
            'account_type'     => ['nullable', Rule::in(self::ACCOUNT_TYPES)],
            'account_method'   => ['nullable', Rule::in(self::ACCOUNT_METHODS)],
            'account_group'    => ['nullable', Rule::in(self::ACCOUNT_GROUPS)],
            'cost_center_type' => ['nullable', Rule::in(self::CC_TYPES)],
        ]);

        // Konversi string kosong ke null
        foreach (['header_id', 'reference_id', 'tbag_1', 'tbag_2', 'tbag_3', 'account_type', 'account_method', 'account_group', 'cost_center_type'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        if ($this->selectedId) {
            $coa = CoaModel::findOrFail($this->selectedId);
            $coa->update($validated);
            session()->flash('success', "Akun '{$coa->name}' berhasil diperbarui.");
        } else {
            $coa = CoaModel::create($validated);
            session()->flash('success', "Akun '{$coa->name}' berhasil ditambahkan.");
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $coa = CoaModel::findOrFail($id);
        $this->deleteId          = $id;
        $this->deleteName        = "[{$coa->account_number}] {$coa->name}";
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            $coa  = CoaModel::findOrFail($this->deleteId);
            $name = $coa->name;
            $coa->delete();
            session()->flash('success', "Akun '{$name}' berhasil dihapus.");
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
        $this->selectedId       = null;
        $this->account_number   = null;
        $this->company_id       = null;
        $this->header_id        = null;
        $this->reference_id     = null;
        $this->name             = '';
        $this->description      = '';
        $this->tbag_1           = null;
        $this->tbag_2           = null;
        $this->tbag_3           = null;
        $this->account_type     = '';
        $this->account_method   = '';
        $this->account_group    = '';
        $this->cost_center_type = '';
    }

    public function render()
    {
        $query = CoaModel::query()
            ->with(['company', 'header'])
            ->search($this->search)
            ->type($this->filterType)
            ->method($this->filterMethod)
            ->accountGroup($this->filterGroup)
            ->forCompany($this->filterCompany !== '' ? (int) $this->filterCompany : null);

        $accounts = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $companies = Company::orderBy('name')->get();

        $headers = CoaModel::when(
            $this->company_id,
            fn($q) => $q->where('company_id', $this->company_id)
        )->orderBy('account_number')->get();

        $tbag1List = TrialBalanceAccountGroup1::when(
            $this->company_id,
            fn($q) => $q->where('company_id', $this->company_id)
        )->orderBy('code')->get();

        $tbag2List = TrialBalanceAccountGroup2::when(
            $this->company_id,
            fn($q) => $q->where('company_id', $this->company_id)
        )->orderBy('code')->get();

        $tbag3List = TrialBalanceAccountGroup3::when(
            $this->company_id,
            fn($q) => $q->where('company_id', $this->company_id)
        )->orderBy('code')->get();

        return view('livewire.master.chart-of-account', compact(
            'accounts', 'companies', 'headers', 'tbag1List', 'tbag2List', 'tbag3List'
        ));
    }
}
