<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Coa as CoaModel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Coa extends Component
{
    use WithPagination, WithToast, Otorisasi, ActiveCompany;

    // Harus sama dengan key menu di Casbin / config/navigation.php
    private const RESOURCE = 'coa';

    private const SORTABLE = ['account_number', 'name', 'account_type', 'account_method'];

    private const RE_HEADER  = '/^[1-9][0-9]{3}$/';          // 4 angka, tidak diawali 0
    private const RE_LEDGER  = '/^[1-9][0-9]{5}$/';          // header(4) + urut(2)
    private const RE_SUFFIX  = '/^(0[1-9]|[1-9][0-9])$/';    // 01 - 99

    // Pemisah kolom CSV. ';' cocok untuk Excel locale Indonesia, ganti ',' kalau perlu.
    private const CSV_DELIMITER = ';';

    /* MODE HALAMAN: list | form (hanya server yang boleh mengubah) */
    #[Locked]
    public string $mode = 'list';

    /* STATE TABEL */
    public string $search        = '';
    public string $levelFilter   = '';
    public string $typeFilter    = '';
    public int    $perPage       = 25;
    public string $sortField     = 'account_number';
    public string $sortDirection = 'asc';

    /* STATE TREE: nomor akun (header / ledger) yang sedang terbuka */
    #[Locked]
    public array $expanded = [];

    /* STATE DOWNLOAD */
    // daftar company yang boleh dipilih di modal (diisi server saat modal dibuka)
    #[Locked]
    public array $dlCompanyOptions = [];

    public bool   $dl_all       = false; // true = abaikan filter nomor akun & tipe
    public string $dl_from      = '';    // nomor header awal (4 angka)
    public string $dl_to        = '';    // nomor header akhir (4 angka)
    public array  $dl_companies = [];
    public array  $dl_types     = [];

    /* STATE FORM */
    #[Locked]
    public ?int $selectedId = null;

    // company saat form dibuka; kalau company di-switch, form otomatis ditutup
    #[Locked]
    public string $formCompany = '';

    public string $level            = 'header'; // header | ledger | subledger
    public string $account_number   = '';       // diisi user hanya untuk header
    public string $code_suffix      = '';       // 2 angka urut (ledger / subledger)
    public string $header_code      = '';
    public string $reference_code   = '';
    public string $name             = '';
    public string $description      = '';
    public string $account_type     = '';
    public string $account_method   = '';
    public string $account_group    = '';
    public string $cost_center_type = '';
    public string $tbag_1 = '';
    public string $tbag_2 = '';
    public string $tbag_3 = '';

    protected array $messages = [
        'level.in'                   => 'Level akun tidak valid.',
        'account_number.required'    => 'Nomor header wajib diisi.',
        'account_number.regex'       => 'Nomor header wajib 4 angka dan tidak boleh diawali 0.',
        'header_code.required'       => 'Header wajib dipilih.',
        'header_code.regex'          => 'Header tidak valid.',
        'header_code.exists'         => 'Header tidak valid untuk perusahaan ini.',
        'reference_code.required'    => 'Ledger wajib dipilih untuk subledger.',
        'reference_code.regex'       => 'Ledger tidak valid.',
        'reference_code.exists'      => 'Ledger tidak valid atau bukan milik header yang dipilih.',
        'code_suffix.required'       => 'Nomor urut wajib diisi (2 angka, contoh 01).',
        'code_suffix.regex'          => 'Nomor urut wajib 2 angka, dari 01 sampai 99.',
        'name.required'              => 'Nama akun wajib diisi.',
        'description.required'       => 'Deskripsi wajib diisi.',
        'account_type.required_with' => 'Tipe akun wajib diisi jika Trial Balance Group 1 dipilih.',
        'tbag_1.required_with'       => 'Group 1 wajib dipilih jika Group 2 dipilih.',
        'tbag_2.required_with'       => 'Group 2 wajib dipilih jika Group 3 dipilih.',
        'tbag_1.exists'              => 'Group 1 tidak valid untuk tipe akun ini.',
        'tbag_2.exists'              => 'Group 2 tidak valid untuk Group 1 ini.',
        'tbag_3.exists'              => 'Group 3 tidak valid untuk Group 2 ini.',
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

    public function updatingLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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
        $this->reset(['search', 'levelFilter', 'typeFilter', 'expanded']);
        $this->sortField     = 'account_number';
        $this->sortDirection = 'asc';
        $this->resetPage();

        $this->toast('Data COA berhasil dimuat ulang.', 'info');
    }

    /* TREE: buka / tutup node */
    public function toggle(int $number): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        if (in_array($number, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$number]));
        } else {
            $this->expanded[] = $number;
        }
    }

    public function expandAll(): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        // semua header + semua ledger di company aktif
        $this->expanded = CoaModel::query()
            ->where('company_code', (string) $this->tabCompany)
            ->where(function ($q) {
                $q->whereNull('header_code') // header
                    ->orWhere(fn($q2) => $q2->whereNotNull('header_code')->whereNull('reference_code')); // ledger
            })
            ->pluck('account_number')
            ->map(fn($n) => (int) $n)
            ->all();
    }

    public function collapseAll(): void
    {
        $this->expanded = [];
    }

    /**
     * Susun baris tree untuk header di halaman aktif:
     * header -> (jika terbuka) ledger -> (jika terbuka) subledger
     */
    private function buildTreeRows(Collection $headers, string $company, int $startNo): Collection
    {
        $headerNos = $headers->pluck('account_number')->map(fn($n) => (int) $n)->all();

        // header mana yang punya ledger (untuk menampilkan panah)
        $headersWithKids = $headerNos
            ? CoaModel::query()->where('company_code', $company)
            ->whereIn('header_code', $headerNos)->whereNull('reference_code')
            ->distinct()->pluck('header_code')->map(fn($n) => (int) $n)->all()
            : [];

        // ledger dari header yang terbuka
        $openHeaders = array_values(array_intersect($headerNos, $this->expanded));
        $ledgers = $openHeaders
            ? CoaModel::query()->where('company_code', $company)
            ->whereIn('header_code', $openHeaders)->whereNull('reference_code')
            ->orderBy('account_number')->get()->groupBy('header_code')
            : collect();

        $ledgerNos = $ledgers->flatten()->pluck('account_number')->map(fn($n) => (int) $n)->all();

        // ledger mana yang punya subledger
        $ledgersWithKids = $ledgerNos
            ? CoaModel::query()->where('company_code', $company)
            ->whereIn('reference_code', $ledgerNos)
            ->distinct()->pluck('reference_code')->map(fn($n) => (int) $n)->all()
            : [];

        // subledger dari ledger yang terbuka
        $openLedgers = array_values(array_intersect($ledgerNos, $this->expanded));
        $subs = $openLedgers
            ? CoaModel::query()->where('company_code', $company)
            ->whereIn('reference_code', $openLedgers)
            ->orderBy('account_number')->get()->groupBy('reference_code')
            : collect();

        $rows = collect();
        $no   = $startNo;

        foreach ($headers as $h) {
            $hNo = (int) $h->account_number;

            $rows->push([
                'model'       => $h,
                'no'          => $no++,
                'hasChildren' => in_array($hNo, $headersWithKids, true),
                'open'        => in_array($hNo, $this->expanded, true),
            ]);

            if (! in_array($hNo, $this->expanded, true)) {
                continue;
            }

            foreach ($ledgers->get($hNo, collect()) as $l) {
                $lNo = (int) $l->account_number;

                $rows->push([
                    'model'       => $l,
                    'no'          => null,
                    'hasChildren' => in_array($lNo, $ledgersWithKids, true),
                    'open'        => in_array($lNo, $this->expanded, true),
                ]);

                if (! in_array($lNo, $this->expanded, true)) {
                    continue;
                }

                foreach ($subs->get($lNo, collect()) as $s) {
                    $rows->push([
                        'model'       => $s,
                        'no'          => null,
                        'hasChildren' => false,
                        'open'        => false,
                    ]);
                }
            }
        }

        return $rows;
    }

    /* DOWNLOAD */
    public function openDownload(): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        $this->resetErrorBag();
        $this->reset(['dl_all', 'dl_from', 'dl_to', 'dl_types']);

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
     * CATATAN: sekarang = semua company yang punya COA + company aktif.
     * Kalau user dibatasi hanya ke company tertentu, ganti isi method ini
     * dengan daftar company milik user tersebut.
     */
    private function downloadableCompanies(): array
    {
        return CoaModel::withoutGlobalScopes()
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
            $rules += [
                'dl_from'      => ['nullable', 'string', 'regex:' . self::RE_HEADER],
                'dl_to'        => ['nullable', 'string', 'regex:' . self::RE_HEADER],
                'dl_types'     => ['array'],
                'dl_types.*'   => ['string', Rule::in(CoaModel::ACCOUNT_TYPES)],
            ];
        }

        $this->validate($rules, [
            'dl_companies.required' => 'Pilih minimal satu company.',
            'dl_companies.min'      => 'Pilih minimal satu company.',
            'dl_companies.*.in'     => 'Company tidak valid.',
            'dl_from.regex'         => 'Nomor akun awal wajib 4 angka (header), tidak diawali 0.',
            'dl_to.regex'           => 'Nomor akun akhir wajib 4 angka (header), tidak diawali 0.',
            'dl_types.*.in'         => 'Tipe akun tidak valid.',
        ]);

        if (! $this->dl_all && $this->dl_from !== '' && $this->dl_to !== '' && (int) $this->dl_from > (int) $this->dl_to) {
            throw ValidationException::withMessages([
                'dl_to' => 'Nomor akhir tidak boleh lebih kecil dari nomor awal.',
            ]);
        }

        $query = CoaModel::withoutGlobalScopes()
            ->whereIn('company_code', $this->dl_companies);

        if (! $this->dl_all) {
            // Filter pada nomor HEADER induknya, jadi ledger & subledger ikut terunduh.
            // Contoh 1002 - 1003 -> 1002, 100201, 10020101, 1003, dst.
            if ($this->dl_from !== '') {
                $query->whereRaw('COALESCE(header_code, account_number) >= ?', [(int) $this->dl_from]);
            }
            if ($this->dl_to !== '') {
                $query->whereRaw('COALESCE(header_code, account_number) <= ?', [(int) $this->dl_to]);
            }
            if ($this->dl_types !== []) {
                $query->whereIn('account_type', $this->dl_types);
            }
        }

        $query->orderBy('company_code')->orderBy('account_number');

        if (! (clone $query)->exists()) {
            $this->toast('Tidak ada data yang cocok dengan filter download.', 'error');

            return null;
        }

        $scope    = count($this->dl_companies) === 1 ? $this->dl_companies[0] : 'multi-company';
        $filename = 'coa_' . Str::slug($scope) . '_' . now()->format('Ymd_His') . '.csv';

        $this->toast('Download COA dimulai.');
        $this->dispatch('download-close');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8

            fputcsv($out, [
                'Company',
                'No. Akun',
                'Nama Akun',
                'Deskripsi',
                'Level',
                'Header',
                'Ledger',
                'Tipe',
                'Metode',
                'Group',
                'Cost Center',
                'TB Group 1',
                'TB Group 2',
                'TB Group 3',
            ], self::CSV_DELIMITER, '"', '');

            foreach ($query->cursor() as $a) {
                fputcsv($out, [
                    $this->csvCell($a->company_code),
                    $this->csvCell($a->account_number),
                    $this->csvCell($a->name),
                    $this->csvCell($a->description),
                    $this->csvCell($a->level),
                    $this->csvCell($a->header_code),
                    $this->csvCell($a->reference_code),
                    $this->csvCell($a->account_type),
                    $this->csvCell($a->account_method),
                    $this->csvCell($a->account_group),
                    $this->csvCell($a->cost_center_type),
                    $this->csvCell($a->tbag_1),
                    $this->csvCell($a->tbag_2),
                    $this->csvCell($a->tbag_3),
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

    /* HOOK FORM (dropdown berantai + nomor urut otomatis) */
    public function updatedLevel(): void
    {
        if (! in_array($this->level, ['header', 'ledger', 'subledger'], true)) {
            $this->level = 'header';
        }

        $this->account_number = '';
        if ($this->level === 'header') {
            $this->header_code = $this->reference_code = '';
        } elseif ($this->level === 'ledger') {
            $this->reference_code = '';
        }

        $this->resetErrorBag(['account_number', 'header_code', 'reference_code', 'code_suffix']);
        $this->refreshSuffix();
    }

    public function updatedHeaderCode(): void
    {
        $this->reference_code = '';
        $this->refreshSuffix();
    }

    public function updatedReferenceCode(): void
    {
        $this->refreshSuffix();
    }

    public function updatedAccountType(): void
    {
        $this->tbag_1 = $this->tbag_2 = $this->tbag_3 = '';
    }

    public function updatedTbag1(): void
    {
        $this->tbag_2 = $this->tbag_3 = '';
    }

    public function updatedTbag2(): void
    {
        $this->tbag_3 = '';
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
        $coa = CoaModel::query()
            ->where('company_code', (string) $this->tabCompany)
            ->findOrFail($id);

        $this->selectedId       = $coa->id;
        $this->formCompany      = (string) $this->tabCompany;
        $this->level            = $coa->level;
        $this->account_number   = (string) $coa->account_number;
        $this->header_code      = (string) ($coa->header_code ?? '');
        $this->reference_code   = (string) ($coa->reference_code ?? '');
        $this->name             = $coa->name ?? '';
        $this->description      = $coa->description ?? '';
        $this->account_type     = (string) ($coa->account_type ?? '');
        $this->account_method   = (string) ($coa->account_method ?? '');
        $this->account_group    = (string) ($coa->account_group ?? '');
        $this->cost_center_type = (string) ($coa->cost_center_type ?? '');
        $this->tbag_1           = (string) ($coa->tbag_1 ?? '');
        $this->tbag_2           = (string) ($coa->tbag_2 ?? '');
        $this->tbag_3           = (string) ($coa->tbag_3 ?? '');

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

        // EDIT: struktur (level, nomor, header, ledger) selalu diambil dari database,
        // nilai kiriman browser untuk field ini diabaikan.
        $existing = null;
        if ($this->selectedId) {
            $existing = CoaModel::query()
                ->where('company_code', $company)
                ->findOrFail($this->selectedId);

            $this->level          = $existing->level;
            $this->account_number = (string) $existing->account_number;
            $this->header_code    = (string) ($existing->header_code ?? '');
            $this->reference_code = (string) ($existing->reference_code ?? '');
            $this->code_suffix    = '';
        }

        // Normalisasi input
        if ($this->level === 'header') {
            $this->header_code = $this->reference_code = $this->code_suffix = '';
        } elseif ($this->level === 'ledger') {
            $this->reference_code = '';
        }
        if ($this->level !== 'header') {
            $this->account_number = '';
        }

        $this->account_number = trim($this->account_number);
        $this->code_suffix    = trim($this->code_suffix);
        $this->name           = Str::upper(Str::squish($this->name));
        $this->description    = Str::squish($this->description);

        try {
            $this->validate($this->rules($company, $existing !== null));
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        $data = [
            'name'             => $this->name,
            'description'      => $this->description,
            'account_type'     => $this->account_type ?: null,
            'account_method'   => $this->account_method ?: null,
            'account_group'    => $this->account_group ?: null,
            'cost_center_type' => $this->cost_center_type ?: null,
            'tbag_1'           => $this->tbag_1 ?: null,
            'tbag_2'           => $this->tbag_2 ?: null,
            'tbag_3'           => $this->tbag_3 ?: null,
        ];

        try {
            if ($existing) {
                $existing->update($data);
                $this->toast("Akun '{$existing->name}' berhasil diperbarui.");
            } else {
                $coa = DB::transaction(function () use ($company, $data) {
                    // Kunci baris induk supaya dua user tidak mengambil nomor urut yang sama
                    $parent = match ($this->level) {
                        'ledger'    => (int) $this->header_code,
                        'subledger' => (int) $this->reference_code,
                        default     => null,
                    };
                    if ($parent !== null) {
                        CoaModel::query()
                            ->where('company_code', $company)
                            ->where('account_number', $parent)
                            ->lockForUpdate()
                            ->first();
                    }

                    // Nomor akun disusun server, bukan dari browser
                    $number = $this->buildNumber();

                    $dipakai = CoaModel::query()
                        ->where('company_code', $company)
                        ->where('account_number', $number)
                        ->exists();

                    if ($dipakai) {
                        throw ValidationException::withMessages([
                            $this->level === 'header' ? 'account_number' : 'code_suffix'
                            => "Nomor akun {$number} sudah digunakan pada perusahaan ini.",
                        ]);
                    }

                    return CoaModel::create($data + [
                        'company_code'   => $company,
                        'account_number' => $number,
                        'header_code'    => $this->level !== 'header' ? (int) $this->header_code : null,
                        'reference_code' => $this->level === 'subledger' ? (int) $this->reference_code : null,
                    ]);
                });

                $this->toast("Akun '{$coa->name}' ({$coa->account_number}) berhasil ditambahkan.");
            }
        } catch (ValidationException $e) {
            $this->toast('Nomor akun bentrok, periksa kembali.', 'error');
            throw $e;
        } catch (UniqueConstraintViolationException $e) {
            report($e);
            $this->toast('Nomor akun sudah digunakan, silakan coba lagi.', 'error');

            return;
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal menyimpan data. Periksa hierarki akun lalu coba lagi.', 'error');

            return;
        }

        // Akun baru: buka induknya supaya langsung terlihat di tree.
        // Harus sebelum backToList() karena resetForm() mengosongkan header_code / reference_code.
        if (! $existing) {
            foreach ([(int) $this->header_code, (int) $this->reference_code] as $p) {
                if ($p > 0 && ! in_array($p, $this->expanded, true)) {
                    $this->expanded[] = $p;
                }
            }
        }

        $this->backToList();
    }

    /* HAPUS */
    public function delete(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'delete');
        $this->ensureSameCompany();

        try {
            $coa = CoaModel::query()
                ->where('company_code', (string) $this->tabCompany)
                ->findOrFail($id);
            $name = $coa->name;

            if ($coa->hasChildren()) {
                $this->toast("Akun '{$name}' masih punya akun turunan, hapus turunannya dulu.", 'error');
                $this->dispatch('delete-close');

                return;
            }

            $coa->delete();
            $this->toast("Akun '{$name}' berhasil dihapus.");
        } catch (QueryException $e) {
            report($e);
            $this->toast('Akun tidak bisa dihapus karena masih dipakai data lain.', 'error');
        }

        $this->dispatch('delete-close');
    }

    /* PENOMORAN */
    private function buildNumber(): int
    {
        return (int) match ($this->level) {
            'header'    => $this->account_number,
            'ledger'    => $this->header_code . $this->code_suffix,
            'subledger' => $this->reference_code . $this->code_suffix,
        };
    }

    // tombol "Auto" di form: ambil nomor akun terbesar + 1 dari induk yang dipilih
    public function suggestSuffix(): void
    {
        $this->izinkan(self::RESOURCE, 'create');
        $this->refreshSuffix();
    }

    /**
     * Nomor urut berikutnya berdasarkan NOMOR AKUN terbesar di bawah induk yang dipilih.
     *   ledger    : induk = header (1001)     -> rentang 100101 .. 100199
     *   subledger : induk = ledger (100101)   -> rentang 10010101 .. 10010199
     *
     * Return: ''   = induk belum dipilih / tidak valid
     *         null = nomor urut sudah penuh (99)
     *         "02" = nomor berikutnya (2 karakter)
     */
    private function nextSuffix(): ?string
    {
        $parent = match ($this->level) {
            'ledger'    => preg_match(self::RE_HEADER, $this->header_code) ? (int) $this->header_code : null,
            'subledger' => preg_match(self::RE_LEDGER, $this->reference_code) ? (int) $this->reference_code : null,
            default     => null,
        };

        if ($parent === null) {
            return '';
        }

        $base = $parent * 100;

        // Company dari form yang dibuka (Locked), global scope BelongsToCompany tetap berlaku
        $max = CoaModel::query()
            ->where('company_code', $this->formCompany)
            ->whereBetween('account_number', [$base + 1, $base + 99])
            ->max('account_number');

        $next = $max ? ((int) $max - $base) + 1 : 1;

        return $next > 99 ? null : str_pad((string) $next, 2, '0', STR_PAD_LEFT);
    }

    private function refreshSuffix(): void
    {
        // hook Livewire tidak lewat render(), jadi company dipastikan dulu
        $this->ensureSameCompany();

        $this->resetErrorBag('code_suffix');
        $this->code_suffix = '';

        if ($this->selectedId || $this->level === 'header') {
            return;
        }

        // form harus dibuka lewat create() dan masih di company yang sama
        if ($this->formCompany === '' || $this->formCompany !== (string) $this->tabCompany) {
            return;
        }

        $next = $this->nextSuffix();

        if ($next === null) {
            $this->addError('code_suffix', 'Nomor urut sudah penuh (maksimal 99).');

            return;
        }

        $this->code_suffix = $next;
    }

    private function previewNumber(): string
    {
        if ($this->selectedId || $this->level === 'header') {
            return $this->account_number;
        }

        $prefix = $this->level === 'ledger' ? $this->header_code : $this->reference_code;

        return ($prefix !== '' && $this->code_suffix !== '') ? $prefix . $this->code_suffix : '';
    }

    /* ATURAN VALIDASI */
    private function rules(string $company, bool $isEdit): array
    {
        $inCompany = fn(string $table, string $column) => Rule::exists($table, $column)
            ->where('company_code', $company);

        $rules = [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],

            'account_type'     => ['nullable', Rule::in(CoaModel::ACCOUNT_TYPES), 'required_with:tbag_1'],
            'account_method'   => ['nullable', Rule::in(CoaModel::ACCOUNT_METHODS)],
            'account_group'    => ['nullable', Rule::in(CoaModel::ACCOUNT_GROUPS)],
            'cost_center_type' => ['nullable', Rule::in(CoaModel::COST_CENTER_TYPES)],

            'tbag_1' => [
                'nullable',
                'string',
                'max:20',
                'required_with:tbag_2',
                $inCompany('trial_balance_account_group_1', 'code')
                    ->where('account_type', $this->account_type),
            ],
            'tbag_2' => [
                'nullable',
                'string',
                'max:20',
                'required_with:tbag_3',
                $inCompany('trial_balance_account_group_2', 'code')
                    ->where('tbag_1_code', $this->tbag_1)
                    ->where('tbag_1_account_type', $this->account_type),
            ],
            'tbag_3' => [
                'nullable',
                'string',
                'max:20',
                $inCompany('trial_balance_account_group_3', 'code')
                    ->where('tbag_2_code', $this->tbag_2)
                    ->where('tbag_1_code', $this->tbag_1)
                    ->where('tbag_1_account_type', $this->account_type),
            ],
        ];

        // Edit: struktur tidak diubah, jadi tidak divalidasi ulang
        if ($isEdit) {
            return $rules;
        }

        $isHeader    = $this->level === 'header';
        $isSubledger = $this->level === 'subledger';

        return $rules + [
            'level' => ['required', Rule::in(['header', 'ledger', 'subledger'])],

            // header: 4 angka, tidak boleh diawali 0 (keunikan dicek di transaksi)
            'account_number' => $isHeader
                ? ['required', 'string', 'regex:' . self::RE_HEADER]
                : ['nullable'],

            // ledger & subledger: header harus akun level header di company ini
            'header_code' => ! $isHeader
                ? [
                    'required',
                    'string',
                    'regex:' . self::RE_HEADER,
                    $inCompany('chart_of_accounts', 'account_number')->whereNull('header_code'),
                ]
                : ['nullable'],

            // subledger: ledger harus milik header yang dipilih
            'reference_code' => $isSubledger
                ? [
                    'required',
                    'string',
                    'regex:' . self::RE_LEDGER,
                    $inCompany('chart_of_accounts', 'account_number')
                        ->where('header_code', (int) $this->header_code)
                        ->whereNull('reference_code'),
                ]
                : ['nullable'],

            // nomor urut 2 angka (01 - 99), boleh diawali 0
            'code_suffix' => ! $isHeader
                ? ['required', 'string', 'regex:' . self::RE_SUFFIX]
                : ['nullable'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'selectedId',
            'formCompany',
            'account_number',
            'code_suffix',
            'header_code',
            'reference_code',
            'name',
            'description',
            'account_type',
            'account_method',
            'account_group',
            'cost_center_type',
            'tbag_1',
            'tbag_2',
            'tbag_3',
        ]);
        $this->level = 'header';
        $this->resetErrorBag();
    }

    /* OPSI DROPDOWN FORM (semua per company aktif) */
    private function formOptions(string $company): array
    {
        $headers = CoaModel::query()->headers()->orderBy('account_number')
            ->get(['account_number', 'name']);

        $ledgers = preg_match(self::RE_HEADER, $this->header_code)
            ? CoaModel::query()->ledgers()->where('header_code', (int) $this->header_code)
            ->orderBy('account_number')->get(['account_number', 'name'])
            : collect();

        $group1 = $this->account_type !== ''
            ? DB::table('trial_balance_account_group_1')
            ->where('company_code', $company)
            ->where('account_type', $this->account_type)
            ->orderBy('code')->get(['code', 'description'])
            : collect();

        $group2 = ($this->tbag_1 !== '' && $this->account_type !== '')
            ? DB::table('trial_balance_account_group_2')
            ->where('company_code', $company)
            ->where('tbag_1_code', $this->tbag_1)
            ->where('tbag_1_account_type', $this->account_type)
            ->orderBy('code')->get(['code', 'description'])
            : collect();

        $group3 = ($this->tbag_2 !== '' && $this->tbag_1 !== '' && $this->account_type !== '')
            ? DB::table('trial_balance_account_group_3')
            ->where('company_code', $company)
            ->where('tbag_2_code', $this->tbag_2)
            ->where('tbag_1_code', $this->tbag_1)
            ->where('tbag_1_account_type', $this->account_type)
            ->orderBy('code')->get(['code', 'description'])
            : collect();

        return compact('headers', 'ledgers', 'group1', 'group2', 'group3');
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

        if ($this->mode === 'form') {
            return view('livewire.master.coa-form', [
                'activeCompany' => $company,
                'opsi'          => $this->formOptions($company),
                'preview'       => $this->previewNumber(),
            ]);
        }

        // Tree hanya aktif kalau tidak ada search / filter
        $isTree = $this->search === '' && $this->levelFilter === '' && $this->typeFilter === '';

        if ($isTree) {
            // Mode tree: yang dipaginasi hanya header
            $accounts = CoaModel::query()
                ->headers()
                ->orderBy($this->sortField, $this->sortDirection)
                ->orderBy('account_number')
                ->paginate($this->perPage);

            $rows = $this->buildTreeRows(
                $accounts->getCollection(),
                $company,
                (int) ($accounts->firstItem() ?? 1)
            );
        } else {
            // Mode flat: ada search / filter
            $accounts = CoaModel::query()
                ->search($this->search)
                ->level($this->levelFilter)
                ->when($this->typeFilter !== '', fn($q) => $q->where('account_type', $this->typeFilter))
                ->orderBy($this->sortField, $this->sortDirection)
                ->orderBy('account_number')
                ->paginate($this->perPage);

            $first = (int) ($accounts->firstItem() ?? 1);

            $rows = $accounts->getCollection()->values()->map(fn($a, $i) => [
                'model'       => $a,
                'no'          => $first + $i,
                'hasChildren' => false,
                'open'        => false,
            ]);
        }

        return view('livewire.master.coa', [
            'accounts'      => $accounts,
            'rows'          => $rows,
            'isTree'        => $isTree,
            'activeCompany' => $company,
        ]);
    }
}
