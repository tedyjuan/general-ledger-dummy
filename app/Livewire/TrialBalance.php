<?php

namespace App\Livewire;

use App\Livewire\Concerns\Otorisasi;
use App\Models\TrialBalanceAccountGroup1;
use App\Models\TrialBalanceAccountGroup2;
use App\Models\TrialBalanceAccountGroup3;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TrialBalance extends Component
{
    use Otorisasi, WithPagination;

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

    /** id baris Group 2 yang dipilih (hanya untuk UI; yang disimpan tetap kode) */
    public string $parent_id = '';

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
            'code' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:150'],
            'parent_id' => ['required', 'integer', 'exists:trial_balance_account_group_2,id'],
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
            'parent_id' => 'Group 2 (induk)',
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
        $this->parent_id = '';
        $this->resetValidation('parent_id');
    }

    // =========================================================
    // CRUD Group 3
    // =========================================================
    public function create(): void
    {
        $this->izinkan('trial-balance', 'create');

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
        $this->izinkan('trial-balance', 'update');

        if ($this->tab !== 'group3') {
            return;
        }

        $row = TrialBalanceAccountGroup3::findOrFail($id);

        // Cari baris Group 2 induknya lewat kode (komposit)
        $parent = TrialBalanceAccountGroup2::query()
            ->where('company_code', $row->company_code)
            ->where('code', $row->tbag_2_code)
            ->where('tbag_1_code', $row->tbag_1_code)
            ->where('tbag_1_account_type', $row->tbag_1_account_type)
            ->first();

        $this->resetForm();
        $this->selectedId = $row->id;
        $this->company_code = (string) $row->company_code;
        $this->code = (string) $row->code;
        $this->description = (string) ($row->description ?? '');
        $this->parent_id = $parent ? (string) $parent->id : '';

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $this->izinkan('trial-balance', $this->selectedId ? 'update' : 'create');

        if ($this->tab !== 'group3') {
            return;
        }

        $this->company_code = strtoupper(trim($this->company_code));
        $this->code = strtoupper(trim($this->code));
        $this->description = trim($this->description);

        $this->validate();

        $description = $this->description !== '' ? $this->description : null;

        // Induk Group 2 harus milik perusahaan yang sama
        $parent = TrialBalanceAccountGroup2::find((int) $this->parent_id);
        if (! $parent || $parent->company_code !== $this->company_code) {
            $this->addError('parent_id', 'Group 2 yang dipilih bukan milik perusahaan ini.');

            return;
        }

        // Kunci unik: company_code + code + tbag_2_code + tbag_1_code + tbag_1_account_type
        $duplicate = TrialBalanceAccountGroup3::query()
            ->where('company_code', $this->company_code)
            ->where('code', $this->code)
            ->where('tbag_2_code', $parent->code)
            ->where('tbag_1_code', $parent->tbag_1_code)
            ->where('tbag_1_account_type', $parent->tbag_1_account_type)
            ->when($this->selectedId, fn($q) => $q->where('id', '!=', $this->selectedId))
            ->exists();

        if ($duplicate) {
            $this->addError('code', 'Kode ini sudah ada di bawah Group 2 yang dipilih.');

            return;
        }

        // Kode rantai disalin dari baris induk, bukan diketik user
        $payload = [
            'company_code' => $this->company_code,
            'code' => $this->code,
            'description' => $description,
            'tbag_2_code' => $parent->code,
            'tbag_1_code' => $parent->tbag_1_code,
            'tbag_1_account_type' => $parent->tbag_1_account_type,
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
        $this->izinkan('trial-balance', 'delete');

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
        $this->izinkan('trial-balance', 'delete');

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
        $this->reset(['selectedId', 'company_code', 'code', 'description', 'parent_id']);
        $this->resetValidation();
    }

    // Daftar perusahaan untuk form (dari data Group 2 yang sudah ada)
    protected function formCompanies()
    {
        return DB::table('trial_balance_account_group_2')
            ->distinct()
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
            ->leftJoin('trial_balance_account_group_1 as g1', function ($j) {
                $j->on('g1.company_code', '=', 'g2.company_code')
                    ->on('g1.code', '=', 'g2.tbag_1_code')
                    ->on('g1.account_type', '=', 'g2.tbag_1_account_type');
            })
            ->where('g2.company_code', $this->company_code)
            ->orderBy('g2.tbag_1_code')
            ->orderBy('g2.tbag_1_account_type')
            ->orderBy('g2.code')
            ->get([
                'g2.id',
                'g2.code',
                'g2.description',
                'g2.tbag_1_code as g1_code',
                'g2.tbag_1_account_type as g1_type',
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
                ->leftJoin('trial_balance_account_group_1 as g1', function ($j) {
                    $j->on('g1.company_code', '=', 'g2.company_code')
                        ->on('g1.code', '=', 'g2.tbag_1_code')
                        ->on('g1.account_type', '=', 'g2.tbag_1_account_type');
                })
                ->select([
                    'g2.*',
                    'g2.tbag_1_code as g1_code',
                    'g1.description as g1_description',
                    'g2.tbag_1_account_type as account_type',
                ])
                ->selectSub(
                    DB::table('trial_balance_account_group_3 as c')
                        ->selectRaw('count(*)')
                        ->whereColumn('c.company_code', 'g2.company_code')
                        ->whereColumn('c.tbag_2_code', 'g2.code')
                        ->whereColumn('c.tbag_1_code', 'g2.tbag_1_code')
                        ->whereColumn('c.tbag_1_account_type', 'g2.tbag_1_account_type'),
                    'children_count'
                ),

            'group3' => TrialBalanceAccountGroup3::query()
                ->from('trial_balance_account_group_3 as g3')
                ->leftJoin('trial_balance_account_group_2 as g2', function ($j) {
                    $j->on('g2.company_code', '=', 'g3.company_code')
                        ->on('g2.code', '=', 'g3.tbag_2_code')
                        ->on('g2.tbag_1_code', '=', 'g3.tbag_1_code')
                        ->on('g2.tbag_1_account_type', '=', 'g3.tbag_1_account_type');
                })
                ->leftJoin('trial_balance_account_group_1 as g1', function ($j) {
                    $j->on('g1.company_code', '=', 'g3.company_code')
                        ->on('g1.code', '=', 'g3.tbag_1_code')
                        ->on('g1.account_type', '=', 'g3.tbag_1_account_type');
                })
                ->select([
                    'g3.*',
                    'g3.tbag_2_code as g2_code',
                    'g2.description as g2_description',
                    'g3.tbag_1_code as g1_code',
                    'g1.description as g1_description',
                    'g3.tbag_1_account_type as account_type',
                ]),

            default => TrialBalanceAccountGroup1::query()
                ->from('trial_balance_account_group_1 as g1')
                ->select('g1.*')
                ->selectSub(
                    DB::table('trial_balance_account_group_2 as c')
                        ->selectRaw('count(*)')
                        ->whereColumn('c.company_code', 'g1.company_code')
                        ->whereColumn('c.tbag_1_code', 'g1.code')
                        ->whereColumn('c.tbag_1_account_type', 'g1.account_type'),
                    'children_count'
                ),
        };
    }

    protected function currentAlias(): string
    {
        return ['group1' => 'g1', 'group2' => 'g2', 'group3' => 'g3'][$this->tab];
    }

    /** Kolom tipe akun sesuai tab aktif */
    protected function typeColumn(): string
    {
        return match ($this->tab) {
            'group2' => 'g2.tbag_1_account_type',
            'group3' => 'g3.tbag_1_account_type',
            default => 'g1.account_type',
        };
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
                $q->where("$a.code", 'like', $term)
                    ->orWhere("$a.description", 'like', $term);

                if ($a !== 'g1') {
                    $q->orWhere('g1.code', 'like', $term)
                        ->orWhere('g1.description', 'like', $term);
                }

                if ($a === 'g3') {
                    $q->orWhere('g2.code', 'like', $term)
                        ->orWhere('g2.description', 'like', $term);
                }
            });
        }

        if ($this->filterCompany !== '') {
            $query->where("$a.company_code", $this->filterCompany);
        }

        if ($this->filterType !== '') {
            $query->where($this->typeColumn(), $this->filterType);
        }

        $sortColumn = $this->sortField === 'account_type'
            ? $this->typeColumn()
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
