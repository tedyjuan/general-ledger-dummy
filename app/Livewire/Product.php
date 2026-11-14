<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Coa as CoaModel;
use App\Models\Product as ProductModel;
use App\Models\ProductMapped as MappedModel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Product extends Component
{
    use WithPagination, WithToast, Otorisasi, ActiveCompany;

    // Harus sama dengan key menu di Casbin / config/navigation.php
    private const RESOURCE = 'product';

    private const SORTABLE = ['product_code', 'product_name', 'coa_code'];

    private const RE_CODE = '/^[A-Za-z0-9][A-Za-z0-9._\-\/]*$/';

    private const MAX_MAPPING = 200;

    /* MODE HALAMAN: list | form (hanya server yang boleh mengubah) */
    #[Locked]
    public string $mode = 'list';

    /* STATE TABEL */
    public string $search        = '';
    public int    $perPage       = 25;
    public string $sortField     = 'product_code';
    public string $sortDirection = 'asc';

    // id produk yang barisnya sedang terbuka (menampilkan mapping)
    #[Locked]
    public array $expanded = [];

    // company saat list terakhir dirender; kalau berbeda = company di-switch
    #[Locked]
    public string $listCompany = '';

    /* STATE FORM */
    #[Locked]
    public ?int $selectedId = null;

    // company saat form dibuka; kalau company di-switch, form otomatis ditutup
    #[Locked]
    public string $formCompany = '';

    public string $product_code = '';
    public string $product_name = '';
    public string $coa_code     = '';

    // tiap baris: key, transaction_type, operational_type, cost_center_code, coa_code, kind, do_type
    public array $mappings = [];

    protected array $messages = [
        'product_code.required' => 'Kode produk wajib diisi.',
        'product_code.regex'    => 'Kode produk hanya boleh huruf, angka, titik, minus, garis miring, atau underscore.',
        'product_code.max'      => 'Kode produk maksimal 50 karakter.',
        'product_code.unique'   => 'Kode produk sudah digunakan pada perusahaan ini.',
        'product_name.required' => 'Nama produk wajib diisi.',
        'coa_code.required'     => 'Akun COA wajib dipilih.',
        'coa_code.exists'       => 'Akun COA tidak valid untuk perusahaan ini.',
        'mappings.max'          => 'Mapping terlalu banyak (maksimal 200 baris).',

        'mappings.*.transaction_type.required' => 'Tipe transaksi wajib dipilih.',
        'mappings.*.transaction_type.in'       => 'Tipe transaksi tidak valid.',
        'mappings.*.operational_type.required' => 'Tipe operasional wajib dipilih.',
        'mappings.*.operational_type.in'       => 'Tipe operasional tidak valid.',
        'mappings.*.do_type.required'          => 'DO type wajib dipilih.',
        'mappings.*.do_type.in'                => 'DO type tidak valid.',
        'mappings.*.cost_center_code.required' => 'Cost center wajib dipilih.',
        'mappings.*.cost_center_code.exists'   => 'Cost center tidak valid untuk perusahaan ini.',
        'mappings.*.coa_code.required'         => 'Akun COA wajib dipilih.',
        'mappings.*.coa_code.exists'           => 'Akun COA tidak valid untuk perusahaan ini.',
        'mappings.*.kind.required'             => 'Pilih Debit atau Kredit.',
        'mappings.*.kind.in'                   => 'Pilihan Debit / Kredit tidak valid.',
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
        $this->reset(['search', 'expanded']);
        $this->sortField     = 'product_code';
        $this->sortDirection = 'asc';
        $this->resetPage();

        $this->toast('Data produk berhasil dimuat ulang.', 'info');
    }

    /* BARIS: buka / tutup detail mapping */
    public function toggle(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        if (in_array($id, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$id]));
        } else {
            $this->expanded[] = $id;
        }
    }

    public function expandAll(): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        // hanya produk yang punya mapping
        $this->expanded = ProductModel::query()
            ->where('company_code', $company)
            ->whereIn('product_code', MappedModel::query()
                ->where('company_code', $company)
                ->select('product_code'))
            ->pluck('id')
            ->map(fn($n) => (int) $n)
            ->all();
    }

    public function collapseAll(): void
    {
        $this->expanded = [];
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

        $company = (string) $this->tabCompany;

        // company dicek eksplisit selain global scope
        $product = ProductModel::query()
            ->where('company_code', $company)
            ->findOrFail($id);

        $this->selectedId   = $product->id;
        $this->formCompany  = $company;
        $this->product_code = (string) $product->product_code;
        $this->product_name = (string) $product->product_name;
        $this->coa_code     = (string) $product->coa_code;

        $this->mappings = MappedModel::query()
            ->where('company_code', $company)
            ->where('product_code', $product->product_code)
            ->orderBy('id')
            ->get()
            ->map(fn($m) => [
                'key'              => Str::random(8),
                'transaction_type' => (string) $m->transaction_type,
                'operational_type' => (string) $m->operational_type,
                'cost_center_code' => (string) $m->cost_center_code,
                'coa_code'         => (string) $m->coa_code,
                'kind'             => (string) $m->kind,
                'do_type'          => (string) $m->do_type,
            ])
            ->all();

        $this->mode = 'form';
    }

    public function backToList(): void
    {
        $this->resetForm();
        $this->mode = 'list';
    }

    /* BARIS MAPPING DI FORM */
    public function addMapping(): void
    {
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');
        $this->ensureSameCompany();

        if (count($this->mappings) >= self::MAX_MAPPING) {
            $this->toast('Mapping sudah mencapai batas maksimal.', 'error');

            return;
        }

        $rows   = $this->mappings;
        $rows[] = $this->blankMapping();

        $this->mappings = $rows;
    }

    public function duplicateMapping(int $index): void
    {
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');
        $this->ensureSameCompany();

        if (! isset($this->mappings[$index]) || count($this->mappings) >= self::MAX_MAPPING) {
            return;
        }

        $rows         = $this->mappings;
        $copy         = $rows[$index];
        $copy['key']  = Str::random(8);

        array_splice($rows, $index + 1, 0, [$copy]);

        $this->mappings = $rows;
    }

    public function removeMapping(int $index): void
    {
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');
        $this->ensureSameCompany();

        if (! isset($this->mappings[$index])) {
            return;
        }

        $rows = $this->mappings;
        unset($rows[$index]);

        $this->mappings = array_values($rows);
        $this->resetErrorBag();
    }

    private function blankMapping(): array
    {
        return [
            'key'              => Str::random(8),
            'transaction_type' => '',
            'operational_type' => '',
            'cost_center_code' => '',
            'coa_code'         => '',
            'kind'             => 'debit',
            'do_type'          => '',
        ];
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

        // EDIT: kode produk selalu diambil dari database (tidak boleh diubah)
        $existing = null;
        if ($this->selectedId) {
            $existing = ProductModel::query()
                ->where('company_code', $company)
                ->findOrFail($this->selectedId);

            $this->product_code = (string) $existing->product_code;
        }

        // Normalisasi input
        $this->product_code = Str::upper(trim($this->product_code));
        $this->product_name = Str::squish($this->product_name);
        $this->coa_code     = trim($this->coa_code);

        $rows = [];
        foreach ($this->mappings as $m) {
            $rows[] = [
                'key'              => (string) ($m['key'] ?? Str::random(8)),
                'transaction_type' => Str::squish((string) ($m['transaction_type'] ?? '')),
                'operational_type' => Str::squish((string) ($m['operational_type'] ?? '')),
                'cost_center_code' => trim((string) ($m['cost_center_code'] ?? '')),
                'coa_code'         => trim((string) ($m['coa_code'] ?? '')),
                'kind'             => (string) ($m['kind'] ?? ''),
                'do_type'          => Str::squish((string) ($m['do_type'] ?? '')),
            ];
        }
        $this->mappings = $rows;

        try {
            $this->validate($this->rules($company, $existing !== null));

            // Cegah baris mapping kembar (sama dengan unique key tabel product_mapped)
            $seen = [];
            foreach ($this->mappings as $i => $m) {
                $sig = Str::lower(implode('|', [
                    $m['transaction_type'],
                    $m['operational_type'],
                    $m['cost_center_code'],
                    $m['coa_code'],
                    $m['kind'],
                    $m['do_type'],
                ]));

                if (isset($seen[$sig])) {
                    throw ValidationException::withMessages([
                        "mappings.{$i}.coa_code" => 'Baris ini sama persis dengan baris ' . ($seen[$sig] + 1) . '.',
                    ]);
                }

                $seen[$sig] = $i;
            }
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        try {
            $product = DB::transaction(function () use ($company, $existing) {
                if ($existing) {
                    // Kunci baris produk supaya dua user tidak menyimpan bersamaan
                    $product = ProductModel::query()
                        ->where('company_code', $company)
                        ->lockForUpdate()
                        ->findOrFail($existing->id);

                    $product->update([
                        'product_name' => $this->product_name,
                        'coa_code'     => $this->coa_code,
                    ]);
                } else {
                    $dipakai = ProductModel::query()
                        ->where('company_code', $company)
                        ->where('product_code', $this->product_code)
                        ->lockForUpdate()
                        ->exists();

                    if ($dipakai) {
                        throw ValidationException::withMessages([
                            'product_code' => "Kode produk {$this->product_code} sudah digunakan pada perusahaan ini.",
                        ]);
                    }

                    $product = ProductModel::create([
                        'company_code' => $company,
                        'product_code' => $this->product_code,
                        'product_name' => $this->product_name,
                        'coa_code'     => $this->coa_code,
                    ]);
                }

                // Mapping disinkronkan: hapus lama, simpan ulang sesuai isi form
                MappedModel::query()
                    ->where('company_code', $company)
                    ->where('product_code', $product->product_code)
                    ->delete();

                if ($this->mappings !== []) {
                    MappedModel::query()->insert(array_map(fn($m) => [
                        'company_code'     => $company,
                        'product_code'     => $product->product_code,
                        'transaction_type' => $m['transaction_type'],
                        'operational_type' => $m['operational_type'],
                        'cost_center_code' => $m['cost_center_code'],
                        'coa_code'         => $m['coa_code'],
                        'kind'             => $m['kind'],
                        'do_type'          => $m['do_type'],
                    ], $this->mappings));
                }

                return $product;
            });

            $jumlah = count($this->mappings);
            $this->toast($existing
                ? "Produk '{$product->product_name}' berhasil diperbarui ({$jumlah} mapping)."
                : "Produk '{$product->product_name}' berhasil ditambahkan ({$jumlah} mapping).");
        } catch (ValidationException $e) {
            $this->toast('Kode produk bentrok, periksa kembali.', 'error');
            throw $e;
        } catch (UniqueConstraintViolationException $e) {
            report($e);
            $this->toast('Data kembar terdeteksi, silakan periksa mapping lalu coba lagi.', 'error');

            return;
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal menyimpan data. Periksa akun COA dan cost center lalu coba lagi.', 'error');

            return;
        }

        // Produk baru dengan mapping: langsung dibuka supaya terlihat di list
        if (! $existing && $this->mappings !== [] && ! in_array($product->id, $this->expanded, true)) {
            $this->expanded[] = $product->id;
        }

        $this->backToList();
    }

    /* HAPUS */
    public function delete(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'delete');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        try {
            $product = ProductModel::query()
                ->where('company_code', $company)
                ->findOrFail($id);
            $name = $product->product_name;

            $jumlah = MappedModel::query()
                ->where('company_code', $company)
                ->where('product_code', $product->product_code)
                ->count();

            // mapping ikut terhapus lewat FK cascade
            $product->delete();

            $this->expanded = array_values(array_diff($this->expanded, [$id]));

            $this->toast($jumlah > 0
                ? "Produk '{$name}' beserta {$jumlah} mapping berhasil dihapus."
                : "Produk '{$name}' berhasil dihapus.");
        } catch (QueryException $e) {
            report($e);
            $this->toast('Produk tidak bisa dihapus karena masih dipakai data lain.', 'error');
        }

        $this->dispatch('delete-close');
    }

    /* ATURAN VALIDASI */
    private function rules(string $company, bool $isEdit): array
    {
        $inCompany = fn(string $table, string $column) => Rule::exists($table, $column)
            ->where('company_code', $company);

        $rules = [
            'product_name' => ['required', 'string', 'max:255'],

            // hanya akun ledger / subledger (bukan header)
            'coa_code' => ['required', 'string', 'max:50', $inCompany('chart_of_accounts', 'account_number')->whereNotNull('header_code')],

            'mappings'   => ['array', 'max:' . self::MAX_MAPPING],
            'mappings.*.transaction_type' => ['required', Rule::in(array_keys(MappedModel::TRANSACTION_TYPES))],
            'mappings.*.operational_type' => ['required', Rule::in(array_keys(MappedModel::OPERATIONAL_TYPES))],
            'mappings.*.do_type'          => ['required', Rule::in(array_keys(MappedModel::DO_TYPES))],
            'mappings.*.cost_center_code' => ['required', 'string', 'max:30', $inCompany('cost_centers', 'cost_center')],
            'mappings.*.coa_code'         => ['required', 'string', 'max:50', $inCompany('chart_of_accounts', 'account_number')->whereNotNull('header_code')],
            'mappings.*.kind'             => ['required', Rule::in(array_keys(MappedModel::KINDS))],
        ];

        // Edit: kode produk tidak diubah, jadi tidak divalidasi ulang
        if (! $isEdit) {
            $rules['product_code'] = [
                'required',
                'string',
                'max:50',
                'regex:' . self::RE_CODE,
                Rule::unique('products', 'product_code')->where('company_code', $company),
            ];
        }

        return $rules;
    }

    private function resetForm(): void
    {
        $this->reset([
            'selectedId',
            'formCompany',
            'product_code',
            'product_name',
            'coa_code',
            'mappings',
        ]);
        $this->resetErrorBag();
    }

    /* OPSI DROPDOWN FORM (semua per company aktif) */
    private function formOptions(string $company): array
    {
        $coas = CoaModel::query()
            ->where('company_code', $company)
            ->whereNotNull('header_code')
            ->orderBy('account_number')
            ->get(['account_number', 'name'])
            ->map(fn($a) => ['v' => (string) $a->account_number, 'n' => (string) $a->name])
            ->all();

        $costCenters = DB::table('cost_centers')
            ->where('company_code', $company)
            ->orderBy('cost_center')
            ->get(['cost_center', 'name'])
            ->map(fn($c) => ['v' => (string) $c->cost_center, 'n' => (string) $c->name])
            ->all();

        return [
            'coas'        => $coas,
            'costCenters' => $costCenters,
            'kind'        => MappedModel::KINDS,
            'trx'         => MappedModel::TRANSACTION_TYPES,
            'ops'         => MappedModel::OPERATIONAL_TYPES,
            'do'          => MappedModel::DO_TYPES,
        ];
    }

    /* RENDER */
    public function render()
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        // Company di-switch: baris terbuka & halaman dikembalikan
        if ($this->listCompany !== $company) {
            $this->listCompany = $company;
            $this->expanded    = [];
            $this->resetPage();
        }

        // Company di-switch saat form terbuka: tutup form, data kembali ke company baru
        if ($this->mode === 'form' && ($this->formCompany === '' || $this->formCompany !== $company)) {
            $this->resetForm();
            $this->mode = 'list';
            $this->resetPage();
            $this->toast('Perusahaan diganti, form ditutup.', 'info');
        }

        if ($this->mode === 'form') {
            return view('livewire.master.product-form', [
                'activeCompany' => $company,
                'opsi'          => $this->formOptions($company),
            ]);
        }

        $products = ProductModel::query()
            ->where('company_code', $company)
            ->search($this->search)
            ->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('product_code')
            ->paginate($this->perPage);

        $items = $products->getCollection();
        $codes = $items->pluck('product_code')->all();

        // jumlah mapping per produk di halaman ini
        $counts = $codes
            ? MappedModel::query()
            ->where('company_code', $company)
            ->whereIn('product_code', $codes)
            ->selectRaw('product_code, COUNT(*) as total')
            ->groupBy('product_code')
            ->pluck('total', 'product_code')
            : collect();

        // detail mapping hanya untuk baris yang terbuka
        $openCodes = $items->whereIn('id', $this->expanded)->pluck('product_code')->all();

        $mappings = $openCodes
            ? MappedModel::query()
            ->where('company_code', $company)
            ->whereIn('product_code', $openCodes)
            ->orderBy('id')
            ->get()
            ->groupBy('product_code')
            : collect();

        // nama akun COA untuk produk & mapping yang tampil
        $coaCodes = $items->pluck('coa_code')
            ->merge($mappings->flatten()->pluck('coa_code'))
            ->unique()
            ->values()
            ->all();

        $coaNames = $coaCodes
            ? CoaModel::query()
            ->where('company_code', $company)
            ->whereIn('account_number', $coaCodes)
            ->pluck('name', 'account_number')
            : collect();

        // nama cost center untuk mapping yang tampil
        $ccCodes = $mappings->flatten()->pluck('cost_center_code')->unique()->values()->all();

        $ccNames = $ccCodes
            ? DB::table('cost_centers')
            ->where('company_code', $company)
            ->whereIn('cost_center', $ccCodes)
            ->pluck('name', 'cost_center')
            : collect();

        return view('livewire.master.product', [
            'products'      => $products,
            'counts'        => $counts,
            'detailMapping' => $mappings,
            'coaNames'      => $coaNames,
            'ccNames'       => $ccNames,
            'activeCompany' => $company,
        ]);
    }
}
