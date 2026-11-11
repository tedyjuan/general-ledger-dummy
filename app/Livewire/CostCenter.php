<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\CostCenter as CostCenterModel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CostCenter extends Component
{
    use WithPagination, WithToast, Otorisasi, ActiveCompany;

    // Harus sama dengan key menu di Casbin / config/navigation.php
    private const RESOURCE = 'cost-center';

    private const SORTABLE = ['cost_center', 'name', 'manager'];

    // Pemisah kolom CSV. ';' cocok untuk Excel locale Indonesia, ganti ',' kalau perlu.
    private const CSV_DELIMITER = ';';

    // Pemisah alias pada nama: ALS/SOPR/OPR/W-GLN
    private const NAME_SEPARATOR = '/';

    /* MODE HALAMAN: list | form (hanya server yang boleh mengubah) */
    #[Locked]
    public string $mode = 'list';

    /* STATE TABEL */
    public string $search           = '';
    public string $areaFilter       = '';
    public string $departmentFilter = '';
    public string $divisionFilter   = '';
    public string $segmentFilter    = '';
    public int    $perPage          = 25;
    public string $sortField        = 'cost_center';
    public string $sortDirection    = 'asc';

    /* STATE DOWNLOAD */
    #[Locked]
    public array $dlCompanyOptions = [];

    public bool   $dl_all        = false; // true = abaikan filter area / dept / division / segment
    public array  $dl_companies  = [];
    public string $dl_area       = '';
    public string $dl_department = '';
    public string $dl_division   = '';
    public string $dl_segment    = '';

    /* STATE FORM */
    #[Locked]
    public ?int $selectedId = null;

    // company saat form dibuka; kalau company di-switch, form otomatis ditutup
    #[Locked]
    public string $formCompany = '';

    // Hanya untuk tampilan saat EDIT (diisi server dari database)
    #[Locked]
    public string $cost_center = '';
    #[Locked]
    public string $name = '';

    // Pilihan struktur (hanya bisa diubah saat tambah)
    public string $area_code       = '';
    public string $department_code = '';
    public string $division_code   = '';
    public string $segment_code    = '';

    // Input user
    public string $manager     = '';
    public string $description = '';

    protected array $messages = [
        'area_code.required'       => 'Area (depo) wajib dipilih.',
        'area_code.exists'         => 'Area tidak valid untuk perusahaan ini.',
        'department_code.required' => 'Department wajib dipilih.',
        'department_code.exists'   => 'Department tidak valid untuk perusahaan ini.',
        'division_code.required'   => 'Division wajib dipilih.',
        'division_code.exists'     => 'Division tidak valid untuk perusahaan ini.',
        'segment_code.required'    => 'Segment wajib dipilih.',
        'segment_code.exists'      => 'Segment tidak valid untuk perusahaan ini.',
        'manager.max'              => 'Nama manager maksimal 100 karakter.',
    ];

    /* HOOK TABEL */
    public function updating(string $name, mixed $value): void
    {
        if (in_array($name, ['search', 'perPage', 'areaFilter', 'departmentFilter', 'divisionFilter', 'segmentFilter'], true)) {
            $this->resetPage();
        }
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
        $this->reset(['search', 'areaFilter', 'departmentFilter', 'divisionFilter', 'segmentFilter']);
        $this->sortField     = 'cost_center';
        $this->sortDirection = 'asc';
        $this->resetPage();

        $this->toast('Data cost center berhasil dimuat ulang.', 'info');
    }

    /**
     * Data referensi untuk dropdown, label, dan penyusunan nama.
     * Semuanya per company (depos, departments, divisions, segments punya company_code).
     *   area       : [area_code => alias]
     *   area_name  : [area_code => nama depo]
     *   department : [code => alias]
     *   division   : [code => alias]
     *   segment    : [code => alias]
     */
    private function lookups(string $company): array
    {
        $depos = DB::table('depos')
            ->where('company_code', $company)
            ->orderBy('area_code')
            ->get(['area_code', 'alias', 'name']);

        $byCompany = fn(string $table) => DB::table($table)
            ->where('company_code', $company)
            ->orderBy('code')
            ->pluck('alias', 'code')
            ->all();

        return [
            'area'       => $depos->pluck('alias', 'area_code')->all(),
            'area_name'  => $depos->pluck('name', 'area_code')->all(),
            'department' => $byCompany('departments'),
            'division'   => $byCompany('divisions'),
            'segment'    => $byCompany('segments'),
        ];
    }

    /**
     * Susun kode & nama dari 4 pilihan.
     *   kode : 02 + 03 + 01 + 07           = 02030107
     *   nama : ALS/SOPR/OPR/W-GLN
     * Return null kalau ada pilihan yang belum diisi / tidak ada di master.
     */
    private function compose(array $ref): ?array
    {
        $a = $this->area_code;
        $d = $this->department_code;
        $v = $this->division_code;
        $s = $this->segment_code;

        if (
            ! array_key_exists($a, $ref['area']) ||
            ! array_key_exists($d, $ref['department']) ||
            ! array_key_exists($v, $ref['division']) ||
            ! array_key_exists($s, $ref['segment'])
        ) {
            return null;
        }

        return [
            'code' => $a . $d . $v . $s,
            'name' => Str::upper(implode(self::NAME_SEPARATOR, [
                $ref['area'][$a],
                $ref['department'][$d],
                $ref['division'][$v],
                $ref['segment'][$s],
            ])),
        ];
    }

    /* DOWNLOAD */
    public function openDownload(): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        $this->resetErrorBag();
        $this->reset(['dl_all', 'dl_area', 'dl_department', 'dl_division', 'dl_segment']);

        $this->dlCompanyOptions = $this->downloadableCompanies();
        $this->dl_companies     = [(string) $this->tabCompany];

        $this->dispatch('download-open');
    }

    public function selectAllDownloadCompanies(): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->dl_companies = $this->dlCompanyOptions;
    }

    public function clearDownloadCompanies(): void
    {
        $this->dl_companies = [];
    }

    /**
     * Daftar company yang boleh diunduh.
     * CATATAN: sekarang = semua company yang punya cost center + company aktif.
     * Kalau user dibatasi hanya ke company tertentu, ganti isi method ini.
     */
    private function downloadableCompanies(): array
    {
        return CostCenterModel::withoutGlobalScopes()
            ->select('company_code')
            ->distinct()
            ->orderBy('company_code')
            ->pluck('company_code')
            ->map(fn($c) => (string) $c)
            ->push((string) $this->tabCompany)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function download(): ?StreamedResponse
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        // daftar company dicek ulang di server, bukan dari nilai kiriman browser
        $allowed = $this->downloadableCompanies();

        $rules = [
            'dl_companies'   => ['required', 'array', 'min:1'],
            'dl_companies.*' => ['string', Rule::in($allowed)],
        ];

        if (! $this->dl_all) {
            // area bisa dari beberapa company, jadi cukup dicek ada di tabel depos
            $rules += [
                'dl_area'       => ['nullable', 'string', 'max:10', Rule::exists('depos', 'area_code')],
                'dl_department' => ['nullable', 'string', 'max:10', Rule::exists('departments', 'code')],
                'dl_division'   => ['nullable', 'string', 'max:10', Rule::exists('divisions', 'code')],
                'dl_segment'    => ['nullable', 'string', 'max:10', Rule::exists('segments', 'code')],
            ];
        }

        $this->validate($rules, [
            'dl_companies.required' => 'Pilih minimal satu company.',
            'dl_companies.min'      => 'Pilih minimal satu company.',
            'dl_companies.*.in'     => 'Company tidak valid.',
            'dl_area.exists'        => 'Area tidak valid.',
            'dl_department.exists'  => 'Department tidak valid.',
            'dl_division.exists'    => 'Division tidak valid.',
            'dl_segment.exists'     => 'Segment tidak valid.',
        ]);

        $query = CostCenterModel::withoutGlobalScopes()
            ->whereIn('company_code', $this->dl_companies);

        if (! $this->dl_all) {
            foreach (['area', 'department', 'division', 'segment'] as $key) {
                $value = $this->{'dl_' . $key};

                if ($value !== '') {
                    $query->where($key . '_code', $value);
                }
            }
        }

        $query->orderBy('company_code')->orderBy('cost_center');

        if (! (clone $query)->exists()) {
            $this->toast('Tidak ada data yang cocok dengan filter download.', 'error');

            return null;
        }

        $scope    = count($this->dl_companies) === 1 ? $this->dl_companies[0] : 'multi-company';
        $filename = 'cost_center_' . Str::slug($scope) . '_' . now()->format('Ymd_His') . '.csv';

        $this->toast('Download cost center dimulai.');
        $this->dispatch('download-close');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8

            fputcsv($out, [
                'Company',
                'Cost Center',
                'Nama',
                'Area',
                'Department',
                'Division',
                'Segment',
                'Manager',
                'Deskripsi',
            ], self::CSV_DELIMITER, '"', '');

            foreach ($query->cursor() as $a) {
                fputcsv($out, [
                    $this->csvCell($a->company_code),
                    // dipaksa teks supaya Excel tidak membuang angka 0 di depan (02030107)
                    $this->csvCell($a->cost_center),
                    $this->csvCell($a->name),
                    $this->csvCell($a->area_code),
                    $this->csvCell($a->department_code),
                    $this->csvCell($a->division_code),
                    $this->csvCell($a->segment_code),
                    $this->csvCell($a->manager),
                    $this->csvCell($a->description),
                ], self::CSV_DELIMITER, '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // cegah CSV/formula injection (sel yang diawali = + - @ dibaca Excel sebagai rumus)
    private function csvCell(mixed $value): string
    {
        $v = (string) ($value ?? '');

        return ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;
    }

    /* BUKA FORM: TAMBAH / EDIT */
    public function create(): void
    {
        $this->izinkan(self::RESOURCE, 'create');
        $this->ensureSameCompany();

        $this->resetForm();
        $this->formCompany = (string) $this->tabCompany;
        $this->mode        = 'form';
    }

    public function edit(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        $this->resetForm();

        // company dicek eksplisit selain global scope
        $cc = CostCenterModel::query()
            ->where('company_code', (string) $this->tabCompany)
            ->findOrFail($id);

        $this->selectedId      = $cc->id;
        $this->formCompany     = (string) $this->tabCompany;
        $this->cost_center     = (string) $cc->cost_center;
        $this->name            = (string) $cc->name;
        $this->area_code       = (string) $cc->area_code;
        $this->department_code = (string) $cc->department_code;
        $this->division_code   = (string) $cc->division_code;
        $this->segment_code    = (string) $cc->segment_code;
        $this->manager         = (string) ($cc->manager ?? '');
        $this->description     = (string) ($cc->description ?? '');

        $this->mode = 'form';
    }

    public function backToList(): void
    {
        $this->resetForm();
        $this->mode = 'list';
    }

    /* SIMPAN */
    public function save(): void
    {
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        // Pastikan form milik company yang sedang aktif
        if ($this->formCompany === '' || $this->formCompany !== $company) {
            $this->backToList();
            $this->toast('Perusahaan berubah, form ditutup. Silakan ulangi.', 'error');

            return;
        }

        $this->manager     = Str::upper(Str::squish($this->manager));
        $this->description = Str::squish($this->description);

        /* ---------- EDIT: hanya manager & deskripsi ---------- */
        if ($this->selectedId) {
            $existing = CostCenterModel::query()
                ->where('company_code', $company)
                ->findOrFail($this->selectedId);

            try {
                $this->validate($this->editRules());
            } catch (ValidationException $e) {
                $this->toast('Data belum valid, periksa kembali isian form.', 'error');
                throw $e;
            }

            $existing->update([
                'manager'     => $this->manager ?: null,
                'description' => $this->description ?: null,
            ]);

            $this->toast("Cost center '{$existing->cost_center}' berhasil diperbarui.");
            $this->backToList();

            return;
        }

        /* ---------- TAMBAH ---------- */
        try {
            $this->validate($this->createRules($company));
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        // Kode & nama disusun server dari master, bukan dari browser
        $composed = $this->compose($this->lookups($company));

        if ($composed === null || strlen($composed['code']) > 30) {
            $this->addError('area_code', 'Kombinasi tidak valid, kode cost center gagal disusun.');
            $this->toast('Kombinasi tidak valid.', 'error');

            return;
        }

        $dipakai = CostCenterModel::withoutGlobalScopes()
            ->where('company_code', $company)
            ->where('cost_center', $composed['code'])
            ->exists();

        if ($dipakai) {
            $this->addError('area_code', "Cost center {$composed['code']} ({$composed['name']}) sudah ada pada perusahaan ini.");
            $this->toast('Cost center sudah ada, periksa kembali pilihan.', 'error');

            return;
        }

        try {
            $cc = CostCenterModel::create([
                'company_code'    => $company,
                'cost_center'     => $composed['code'],
                'name'            => $composed['name'],
                'area_code'       => $this->area_code,
                'department_code' => $this->department_code,
                'division_code'   => $this->division_code,
                'segment_code'    => $this->segment_code,
                'manager'         => $this->manager ?: null,
                'description'     => $this->description ?: null,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // unique index cost_center di database (kalau masih global lintas company)
            report($e);
            $this->addError('area_code', "Kode {$composed['code']} sudah dipakai.");
            $this->toast('Kode cost center sudah digunakan.', 'error');

            return;
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal menyimpan data. Periksa area, department, division, dan segment lalu coba lagi.', 'error');

            return;
        }

        $this->toast("Cost center '{$cc->cost_center}' ({$cc->name}) berhasil ditambahkan.");
        $this->resetPage();
        $this->backToList();
    }

    /* HAPUS */
    public function delete(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'delete');
        $this->ensureSameCompany();

        try {
            $cc = CostCenterModel::query()
                ->where('company_code', (string) $this->tabCompany)
                ->findOrFail($id);
            $label = $cc->cost_center;

            $cc->delete();
            $this->toast("Cost center '{$label}' berhasil dihapus.");
        } catch (QueryException $e) {
            // FK restrictOnDelete dari tabel lain yang memakai cost center ini
            report($e);
            $this->toast('Cost center tidak bisa dihapus karena masih dipakai data lain.', 'error');
        }

        $this->dispatch('delete-close');
    }

    /* ATURAN VALIDASI */
    private function editRules(): array
    {
        return [
            'manager'     => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }

    private function createRules(string $company): array
    {
        return $this->editRules() + [
            // semua pilihan harus milik company aktif
            'area_code' => [
                'required',
                'string',
                'max:10',
                Rule::exists('depos', 'area_code')->where('company_code', $company),
            ],
            'department_code' => [
                'required',
                'string',
                'max:10',
                Rule::exists('departments', 'code')->where('company_code', $company),
            ],
            'division_code' => [
                'required',
                'string',
                'max:10',
                Rule::exists('divisions', 'code')->where('company_code', $company),
            ],
            'segment_code' => [
                'required',
                'string',
                'max:10',
                Rule::exists('segments', 'code')->where('company_code', $company),
            ],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'selectedId',
            'formCompany',
            'cost_center',
            'name',
            'area_code',
            'department_code',
            'division_code',
            'segment_code',
            'manager',
            'description',
        ]);
        $this->resetErrorBag();
    }

    /* RENDER */
    public function render()
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        // Company di-switch saat form terbuka: tutup form, data kembali ke company baru
        if ($this->mode === 'form' && ($this->formCompany === '' || $this->formCompany !== $company)) {
            $this->resetForm();
            $this->mode = 'list';
            $this->resetPage();
            $this->toast('Perusahaan diganti, form ditutup.', 'info');
        }

        $ref = $this->lookups($company);

        if ($this->mode === 'form') {
            // Edit: tampilkan yang tersimpan. Tambah: pratinjau dari pilihan saat ini.
            $preview = $this->selectedId
                ? ['code' => $this->cost_center, 'name' => $this->name]
                : $this->compose($ref);

            return view('livewire.master.cost-center-form', [
                'activeCompany' => $company,
                'ref'           => $ref,
                'preview'       => $preview,
            ]);
        }

        $costCenters = CostCenterModel::query()
            ->search($this->search)
            ->when($this->areaFilter !== '', fn($q) => $q->where('area_code', $this->areaFilter))
            ->when($this->departmentFilter !== '', fn($q) => $q->where('department_code', $this->departmentFilter))
            ->when($this->divisionFilter !== '', fn($q) => $q->where('division_code', $this->divisionFilter))
            ->when($this->segmentFilter !== '', fn($q) => $q->where('segment_code', $this->segmentFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('cost_center')
            ->paginate($this->perPage);

        return view('livewire.master.cost-center', [
            'costCenters'   => $costCenters,
            'ref'           => $ref,
            'activeCompany' => $company,
        ]);
    }
}
