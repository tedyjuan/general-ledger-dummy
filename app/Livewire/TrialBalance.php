<?php

namespace App\Livewire;

use App\Models\TrialBalanceAccountGroup1;
use App\Models\TrialBalanceAccountGroup2;
use App\Models\TrialBalanceAccountGroup3;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TrialBalance extends Component
{
    use WithPagination;

    private const TABS = ['group1', 'group2', 'group3'];

    private const SORTABLE = ['company_code', 'code', 'description', 'account_type', 'updated_at'];

    // ---------- State daftar ----------
    #[Url(as: 'tab')]
    public string $tab = 'group1';

    #[Url(as: 'q')]
    public string $search = '';

    public string $filterCompany = '';

    public string $filterType = '';

    public int $perPage = 10;

    public string $sortField = 'code';

    public string $sortDirection = 'asc';

    // ---------- State form CRUD Group 3 ----------
    public bool $isModalOpen = false;

    public ?int $selectedId = null;

    public string $company_code = '';

    public string $code = '';

    public string $description = '';

    public string $tbag_2 = '';

    // ---------- State hapus ----------
    public bool $isDeleteModalOpen = false;

    public ?int $deleteId = null;

    public string $deleteName = '';

    // =========================================================
    // Validasi
    // =========================================================
    protected function rules(): array
    {
        return [
            'company_code' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:150'],
            'tbag_2' => ['required', 'integer', 'exists:trial_balance_account_group_2,id'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'integer' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'company_code' => 'Perusahaan',
            'code' => 'Kode',
            'description' => 'Deskripsi',
            'tbag_2' => 'Group 2 (induk)',
        ];
    }

    // =========================================================
    // Tab, sort, filter
    // =========================================================
    public function setTab(string $tab): void
    {
        if (! in_array($tab, self::TABS, true) || $tab === $this->tab) {
            return;
        }

        $this->tab = $tab;
        $this->filterCompany = '';
        $this->filterType = '';
        $this->sortField = 'code';
        $this->sortDirection = 'asc';
        $this->closeModal();
        $this->closeDeleteModal();
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
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function refreshData(): void
    {
        $this->reset(['search', 'filterCompany', 'filterType']);
        $this->resetPage();

        $this->dispatch('toast', type: 'success', message: 'Data trial balance diperbarui');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCompany(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // Ganti perusahaan -> pilihan Group 2 berubah, jadi induk harus dipilih ulang
    public function updatedCompanyCode(): void
    {
        $this->tbag_2 = '';
        $this->resetValidation('tbag_2');
    }

    // =========================================================
    // CRUD Group 3
    // =========================================================
    public function create(): void
    {
        if ($this->tab !== 'group3') {
            return;
        }

        $this->resetForm();

        $companies = $this->formCompanies();
        if ($companies->count() === 1) {
            $this->company_code = (string) $companies->first();
        }

        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        if ($this->tab !== 'group3') {
            return;
        }

        $row = TrialBalanceAccountGroup3::findOrFail($id);

        $this->resetForm();
        $this->selectedId = $row->id;
        $this->company_code = (string) $row->company_code;
        $this->code = (string) $row->code;
        $this->description = (string) ($row->description ?? '');
        $this->tbag_2 = $row->tbag_2 ? (string) $row->tbag_2 : '';

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        if ($this->tab !== 'group3') {
            return;
        }

        $this->company_code = trim($this->company_code);
        $this->code = trim($this->code);
        $this->description = trim($this->description);

        $this->validate();

        $description = $this->description !== '' ? $this->description : null;

        // Induk Group 2 harus milik perusahaan yang sama
        $parent = TrialBalanceAccountGroup2::find((int) $this->tbag_2);
        if (! $parent || $parent->company_code !== $this->company_code) {
            $this->addError('tbag_2', 'Group 2 yang dipilih bukan milik perusahaan ini.');

            return;
        }

        // Cek kombinasi unik (company_code, code, description, tbag_2)
        $duplicate = TrialBalanceAccountGroup3::query()
            ->where('company_code', $this->company_code)
            ->where('code', $this->code)
            ->where('tbag_2', (int) $this->tbag_2)
            ->when(
                $description === null,
                fn($q) => $q->whereNull('description'),
                fn($q) => $q->where('description', $description)
            )
            ->when($this->selectedId, fn($q) => $q->where('id', '!=', $this->selectedId))
            ->exists();

        if ($duplicate) {
            $this->addError('code', 'Kombinasi perusahaan, kode, deskripsi, dan Group 2 ini sudah ada.');

            return;
        }

        $payload = [
            'company_code' => $this->company_code,
            'code' => $this->code,
            'description' => $description,
            'tbag_2' => (int) $this->tbag_2,
        ];

        if ($this->selectedId) {
            TrialBalanceAccountGroup3::findOrFail($this->selectedId)->update($payload);
            $message = 'Group 3 berhasil diperbarui';
        } else {
            TrialBalanceAccountGroup3::create($payload);
            $message = 'Group 3 berhasil ditambahkan';
        }

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        if ($this->tab !== 'group3') {
            return;
        }

        $row = TrialBalanceAccountGroup3::findOrFail($id);

        $this->deleteId = $row->id;
        $this->deleteName = trim($row->code . ' - ' . ($row->description ?? ''), ' -');
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if ($this->tab !== 'group3' || ! $this->deleteId) {
            return;
        }

        TrialBalanceAccountGroup3::whereKey($this->deleteId)->delete();

        $this->closeDeleteModal();
        $this->dispatch('toast', type: 'success', message: 'Group 3 berhasil dihapus');
    }

    public function closeDeleteModal(): void
    {
        $this->isDeleteModalOpen = false;
        $this->deleteId = null;
        $this->deleteName = '';
    }

    protected function resetForm(): void
    {
        $this->reset(['selectedId', 'company_code', 'code', 'description', 'tbag_2']);
        $this->resetValidation();
    }

    // Daftar perusahaan untuk form (diambil dari data Group 2 & Group 3 yang sudah ada)
    protected function formCompanies()
    {
        return DB::table('trial_balance_account_group_2')
            ->select('company_code')
            ->union(DB::table('trial_balance_account_group_3')->select('company_code'))
            ->orderBy('company_code')
            ->pluck('company_code');
    }

    // Pilihan Group 2 (beserta induk Group 1) untuk perusahaan yang dipilih di form
    protected function formGroup2Options()
    {
        if ($this->company_code === '') {
            return collect();
        }

        return DB::table('trial_balance_account_group_2 as g2')
            ->leftJoin('trial_balance_account_group_1 as g1', 'g1.id', '=', 'g2.tbag_1')
            ->where('g2.company_code', $this->company_code)
            ->orderBy('g1.code')
            ->orderBy('g2.code')
            ->get([
                'g2.id',
                'g2.code',
                'g2.description',
                'g1.code as g1_code',
                'g1.description as g1_description',
            ]);
    }

    // =========================================================
    // Query daftar
    // =========================================================
    protected function baseQuery()
    {
        return match ($this->tab) {
            'group2' => TrialBalanceAccountGroup2::query()
                ->from('trial_balance_account_group_2 as g2')
                ->leftJoin('trial_balance_account_group_1 as g1', 'g1.id', '=', 'g2.tbag_1')
                ->select([
                    'g2.*',
                    'g1.code as g1_code',
                    'g1.description as g1_description',
                    'g1.account_type as account_type',
                ])
                ->selectSub(
                    DB::table('trial_balance_account_group_3 as c')
                        ->selectRaw('count(*)')
                        ->whereColumn('c.tbag_2', 'g2.id'),
                    'children_count'
                ),

            'group3' => TrialBalanceAccountGroup3::query()
                ->from('trial_balance_account_group_3 as g3')
                ->leftJoin('trial_balance_account_group_2 as g2', 'g2.id', '=', 'g3.tbag_2')
                ->leftJoin('trial_balance_account_group_1 as g1', 'g1.id', '=', 'g2.tbag_1')
                ->select([
                    'g3.*',
                    'g2.code as g2_code',
                    'g2.description as g2_description',
                    'g1.code as g1_code',
                    'g1.description as g1_description',
                    'g1.account_type as account_type',
                ]),

            default => TrialBalanceAccountGroup1::query()
                ->from('trial_balance_account_group_1 as g1')
                ->select('g1.*')
                ->selectSub(
                    DB::table('trial_balance_account_group_2 as c')
                        ->selectRaw('count(*)')
                        ->whereColumn('c.tbag_1', 'g1.id'),
                    'children_count'
                ),
        };
    }

    protected function currentAlias(): string
    {
        return ['group1' => 'g1', 'group2' => 'g2', 'group3' => 'g3'][$this->tab];
    }

    protected function currentTable(): string
    {
        return [
            'group1' => 'trial_balance_account_group_1',
            'group2' => 'trial_balance_account_group_2',
            'group3' => 'trial_balance_account_group_3',
        ][$this->tab];
    }

    public function render()
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'group1';
        }

        $a = $this->currentAlias();
        $query = $this->baseQuery();

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';

            $query->where(function ($q) use ($a, $term) {
                $q->where("$a.code", 'ilike', $term)
                    ->orWhere("$a.description", 'ilike', $term);

                if ($a !== 'g1') {
                    $q->orWhere('g1.code', 'ilike', $term)
                        ->orWhere('g1.description', 'ilike', $term);
                }

                if ($a === 'g3') {
                    $q->orWhere('g2.code', 'ilike', $term)
                        ->orWhere('g2.description', 'ilike', $term);
                }
            });
        }

        if ($this->filterCompany !== '') {
            $query->where("$a.company_code", $this->filterCompany);
        }

        if ($this->filterType !== '') {
            $query->where('g1.account_type', $this->filterType);
        }

        $sortColumn = $this->sortField === 'account_type'
            ? 'g1.account_type'
            : "$a.{$this->sortField}";

        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;

        $query->orderBy($sortColumn, $this->sortDirection)->orderBy("$a.id");

        $rows = $query->paginate($perPage);

        // Setelah hapus data terakhir di sebuah halaman, mundur ke halaman terakhir yang valid
        if ($rows->isEmpty() && $rows->currentPage() > 1) {
            $this->setPage($rows->lastPage());
            $rows = $query->paginate($perPage);
        }

        return view('livewire.master.trial-balance', [
            'rows' => $rows,
            'counts' => [
                'group1' => TrialBalanceAccountGroup1::count(),
                'group2' => TrialBalanceAccountGroup2::count(),
                'group3' => TrialBalanceAccountGroup3::count(),
            ],
            'companies' => DB::table($this->currentTable())
                ->distinct()
                ->orderBy('company_code')
                ->pluck('company_code'),
            'accountTypes' => DB::table('trial_balance_account_group_1')
                ->whereNotNull('account_type')
                ->distinct()
                ->orderBy('account_type')
                ->pluck('account_type'),
            'formCompanies' => $this->isModalOpen ? $this->formCompanies() : collect(),
            'group2Options' => $this->isModalOpen
                ? $this->formGroup2Options()->groupBy('g1_code')
                : collect(),
        ]);
    }
}
