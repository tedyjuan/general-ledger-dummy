<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Depo as DepoModel;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TrialBalanceGroup extends Component
{
    use WithPagination;

    // Pencarian dan Filter
    public string $search        = '';
    public string $filterStatus  = '';
    public string $filterCompany = '';
    public int $perPage          = 10;
    public string $sortField     = 'id';
    public string $sortDirection = 'desc';

    // State Modal
    public bool $isModalOpen       = false;
    public bool $isDeleteModalOpen = false;
    public ?int $selectedId        = null;
    public ?int $deleteId          = null;
    public string $deleteName      = '';

    // Field Form
    public ?int $company_id    = null;
    public string $depo_code   = '';
    public string $name        = '';
    public string $alias       = '';
    public string $area_code   = '';
    public string $status_depo = 'depo';
    public string $npwp        = '';
    public string $phone_no    = '';
    public string $city        = '';
    public string $address     = '';
    public string $postal_code = '';
    public ?int $fiscal_year   = null;

    protected array $messages = [
        'company_id.required'  => 'Perusahaan wajib dipilih.',
        'company_id.exists'    => 'Perusahaan tidak valid.',
        'depo_code.required'   => 'Kode depo wajib diisi.',
        'depo_code.unique'     => 'Kode depo sudah digunakan pada perusahaan ini.',
        'name.required'        => 'Nama depo wajib diisi.',
        'name.unique'          => 'Nama depo sudah digunakan pada perusahaan ini.',
        'alias.required'       => 'Alias wajib diisi.',
        'alias.unique'         => 'Alias sudah digunakan pada perusahaan ini.',
        'area_code.required'   => 'Kode area wajib diisi.',
        'area_code.unique'     => 'Kode area sudah digunakan pada perusahaan ini.',
        'status_depo.required' => 'Status depo wajib dipilih.',
        'status_depo.in'       => 'Status depo harus Pusat atau Depo.',
    ];

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
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Membuka modal tambah depo baru
     */
    public function create(): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        $defaultCompany = Company::firstOrCreate([
            'name' => 'PT. Berkah Sentosa Abadi',
        ]);
        $this->company_id = $defaultCompany->id;

        $this->fiscal_year = (int) date('Y');
        $this->status_depo = 'depo';
        $this->selectedId = null;
        $this->isModalOpen = true;
    }

    /**
     * Memuat ulang data dan me-reset filter
     */
    public function refreshData(): void
    {
        $this->search        = '';
        $this->filterStatus  = '';
        $this->filterCompany = '';
        $this->sortField     = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();
        session()->flash('success', 'Data depo berhasil dimuat ulang (refresh).');
    }

    public function resetFilters(): void
    {
        $this->refreshData();
    }

    /**
     * Membuka modal edit data depo
     */
    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $depo = DepoModel::findOrFail($id);

        $this->selectedId  = $depo->id;
        $this->company_id  = $depo->company_id;
        $this->depo_code   = $depo->depo_code ?? '';
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

    /**
     * Menyimpan data (Create atau Update)
     */
    public function save(): void
    {
        $validated = $this->validate([
            'company_id'  => ['required', 'exists:companies,id'],
            'depo_code'   => [
                'required',
                'string',
                'max:20',
                Rule::unique('depos', 'depo_code')
                    ->where(fn($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->selectedId),
            ],
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('depos', 'name')
                    ->where(fn($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->selectedId),
            ],
            'alias'       => [
                'required',
                'string',
                'max:50',
                Rule::unique('depos', 'alias')
                    ->where(fn($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->selectedId),
            ],
            'area_code'   => [
                'required',
                'string',
                'max:50',
                Rule::unique('depos', 'area_code')
                    ->where(fn($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->selectedId),
            ],
            'status_depo' => ['required', 'in:pusat,depo'],
            'npwp'        => ['nullable', 'string', 'max:30'],
            'phone_no'    => ['nullable', 'string', 'max:30'],
            'city'        => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'fiscal_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        if ($this->selectedId) {
            $depo = DepoModel::findOrFail($this->selectedId);
            $depo->update($validated);
            session()->flash('success', "Depo '{$depo->name}' berhasil diperbarui.");
        } else {
            $depo = DepoModel::create($validated);
            session()->flash('success', "Depo '{$depo->name}' berhasil ditambahkan.");
        }

        $this->closeModal();
    }

    /**
     * Membuka modal konfirmasi hapus
     */
    public function confirmDelete(int $id): void
    {
        $depo = DepoModel::findOrFail($id);
        $this->deleteId = $id;
        $this->deleteName = $depo->name;
        $this->isDeleteModalOpen = true;
    }

    /**
     * Mengeksekusi penghapusan data
     */
    public function delete(): void
    {
        if ($this->deleteId) {
            $depo = DepoModel::findOrFail($this->deleteId);
            $name = $depo->name;
            $depo->delete();

            session()->flash('success', "Depo '{$name}' berhasil dihapus.");
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
        $this->deleteId = null;
        $this->deleteName = '';
    }

    private function resetForm(): void
    {
        $this->selectedId  = null;
        $this->company_id  = null;
        $this->depo_code   = '';
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

    public function render()
    {
        $query = DepoModel::query()
            ->with('company')
            ->search($this->search)
            ->status($this->filterStatus)
            ->forCompany($this->filterCompany !== '' ? (int) $this->filterCompany : null);

        $depos = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $companies = Company::orderBy('name')->get();

        return view('livewire.master.depo', [
            'depos' => $depos,
            'companies' => $companies,
        ]);
    }
}
