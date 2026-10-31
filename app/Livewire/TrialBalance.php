<?php

namespace App\Livewire;

use App\Models\TrialBalanceAccountGroup1;
use App\Models\TrialBalanceAccountGroup2;
use App\Models\TrialBalanceAccountGroup3;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TrialBalance  extends Component
{
    use WithPagination;

    public const ACCOUNT_TYPES = [
        'asset',
        'liability',
        'equity',
        'retained earning account',
        'income',
        'expense',
    ];

    private const T1 = 'trial_balance_account_group_1';
    private const T2 = 'trial_balance_account_group_2';
    private const T3 = 'trial_balance_account_group_3';

    #[Url(as: 'tab')]
    public string $tab = 'group1';

    #[Url(except: '')]
    public string $search = '';

    public string $filterCompany = '';
    public string $filterType = '';
    public int $perPage = 10;
    public string $sortField = 'code';
    public string $sortDirection = 'asc';

    /* ---------------------------------------------------------------
     |  Navigasi, filter, sort
     * ------------------------------------------------------------- */

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['group1', 'group2', 'group3'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->sortField = 'code';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['company_code', 'code', 'description', 'account_type', 'updated_at'], true)) {
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

    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'filterCompany', 'filterType', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function refreshData(): void
    {
        $this->reset(['search', 'filterCompany', 'filterType']);
        $this->resetPage();
        $this->flushCache();

        // Kirim opsi Group 2 terbaru ke modal Alpine
        $this->dispatch('g2-options', options: $this->group2Options());
    }

    /* ---------------------------------------------------------------
     |  Aksi Group 3 (dipanggil Alpine lewat $wire, sekali per aksi)
     * ------------------------------------------------------------- */

    public function saveGroup3(array $data): array
    {
        $this->guard();

        $input = [
            'id'           => $data['id'] ?? null,
            'company_code' => trim((string) ($data['company_code'] ?? '')),
            'g2_id'        => $data['g2_id'] ?? null,
            'code'         => strtoupper(trim((string) ($data['code'] ?? ''))),
            'description'  => trim((string) ($data['description'] ?? '')),
        ];

        $validator = Validator::make($input, [
            'company_code' => ['required', 'exists:companies,code'],
            'g2_id'        => ['required', 'integer'],
            'code'         => ['required', 'string', 'max:20'],
            'description'  => ['nullable', 'string', 'max:150'],
        ], [
            'company_code.required' => 'Perusahaan wajib dipilih.',
            'company_code.exists'   => 'Perusahaan tidak valid.',
            'g2_id.required'        => 'Group 2 (induk) wajib dipilih.',
            'code.required'         => 'Kode Group 3 wajib diisi.',
            'code.max'              => 'Kode maksimal 20 karakter.',
            'description.max'       => 'Deskripsi maksimal 150 karakter.',
        ]);

        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()->toArray()];
        }

        // Group 2 terpilih WAJIB milik perusahaan yang sama
        $g2 = TrialBalanceAccountGroup2::query()
            ->where('id', $input['g2_id'])
            ->where('company_code', $input['company_code'])
            ->first();

        if (! $g2) {
            return ['ok' => false, 'errors' => ['g2_id' => ['Group 2 tidak valid untuk perusahaan ini.']]];
        }

        // Group 3 tidak menyimpan id induk, tapi salinan kolom composite dari Group 2
        $parent = [
            'company_code'        => $g2->company_code,
            'tbag_2_code'         => $g2->code,
            'tbag_1_code'         => $g2->tbag_1_code,
            'tbag_1_account_type' => $g2->tbag_1_account_type,
        ];

        // Sama dengan unique_tbag_3, abaikan dirinya sendiri saat edit
        $duplicate = TrialBalanceAccountGroup3::query()
            ->where($parent)
            ->where('code', $input['code'])
            ->when($input['id'], fn(Builder $q) => $q->where('id', '!=', $input['id']))
            ->exists();

        if ($duplicate) {
            return ['ok' => false, 'errors' => ['code' => ['Kode ini sudah ada pada Group 2 tersebut.']]];
        }

        $payload = $parent + [
            'code'        => $input['code'],
            'description' => $input['description'] !== '' ? $input['description'] : null,
        ];

        try {
            if ($input['id']) {
                $row = TrialBalanceAccountGroup3::find($input['id']);

                if (! $row) {
                    return ['ok' => false, 'message' => 'Data tidak ditemukan, mungkin sudah dihapus.'];
                }

                $row->update($payload);
                $message = 'Group 3 berhasil diperbarui.';
            } else {
                TrialBalanceAccountGroup3::create($payload);
                $message = 'Group 3 berhasil ditambahkan.';
            }
        } catch (QueryException $e) {
            report($e);

            return ['ok' => false, 'message' => 'Gagal menyimpan data. Coba lagi.'];
        }

        $this->flushCache();

        return ['ok' => true, 'message' => $message];
    }

    public function deleteGroup3(int $id): array
    {
        $this->guard();

        $row = TrialBalanceAccountGroup3::find($id);

        if (! $row) {
            return ['ok' => true, 'message' => 'Data sudah tidak ada.'];
        }

        try {
            $row->delete();
        } catch (QueryException $e) {
            // FK restrictOnDelete: masih dipakai tabel lain
            return ['ok' => false, 'message' => 'Tidak bisa dihapus karena masih dipakai data lain.'];
        }

        $this->flushCache();

        return ['ok' => true, 'message' => 'Group 3 berhasil dihapus.'];
    }

    /** Casbin: method Livewire bisa dipanggil langsung dari browser, jadi cek izin di sini. */
    protected function guard(): void
    {
        // contoh:
        // abort_unless(Enforcer::enforce(auth()->user()->role, 'trial-balance', 'write'), 403);
    }

    /* ---------------------------------------------------------------
     |  Query data
     * ------------------------------------------------------------- */

    protected function rowsQuery(): Builder
    {
        $tab = $this->tab;

        $query = match ($tab) {
            'group3' => TrialBalanceAccountGroup3::query()->from(self::T3 . ' as t'),
            'group2' => TrialBalanceAccountGroup2::query()->from(self::T2 . ' as t'),
            default  => TrialBalanceAccountGroup1::query()->from(self::T1 . ' as t'),
        };

        $typeCol = $tab === 'group1' ? 't.account_type' : 't.tbag_1_account_type';

        $query->select('t.*');

        if ($tab === 'group1') {
            $query->selectSub(
                DB::table(self::T2 . ' as c')
                    ->selectRaw('count(*)')
                    ->whereColumn('c.company_code', 't.company_code')
                    ->whereColumn('c.tbag_1_code', 't.code')
                    ->whereColumn('c.tbag_1_account_type', 't.account_type'),
                'children_count'
            );
        }

        if ($tab === 'group2') {
            $query->selectSub(
                DB::table(self::T3 . ' as c')
                    ->selectRaw('count(*)')
                    ->whereColumn('c.company_code', 't.company_code')
                    ->whereColumn('c.tbag_2_code', 't.code')
                    ->whereColumn('c.tbag_1_code', 't.tbag_1_code')
                    ->whereColumn('c.tbag_1_account_type', 't.tbag_1_account_type'),
                'children_count'
            );
        }

        if ($tab !== 'group1') {
            $query->addSelect(['t.tbag_1_account_type as account_type', 'g1.code as g1_code', 'g1.description as g1_description'])
                ->leftJoin(self::T1 . ' as g1', function ($join) {
                    $join->on('g1.company_code', '=', 't.company_code')
                        ->on('g1.code', '=', 't.tbag_1_code')
                        ->on('g1.account_type', '=', 't.tbag_1_account_type');
                });
        }

        if ($tab === 'group3') {
            $query->addSelect(['g2.id as g2_id', 'g2.code as g2_code', 'g2.description as g2_description'])
                ->leftJoin(self::T2 . ' as g2', function ($join) {
                    $join->on('g2.company_code', '=', 't.company_code')
                        ->on('g2.code', '=', 't.tbag_2_code')
                        ->on('g2.tbag_1_code', '=', 't.tbag_1_code')
                        ->on('g2.tbag_1_account_type', '=', 't.tbag_1_account_type');
                });
        }

        // Filter
        $term = mb_strtolower(trim($this->search));
        if ($term !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term) . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw('LOWER(t.code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(t.description) LIKE ?', [$like]);
            });
        }

        if ($this->filterCompany !== '') {
            $query->where('t.company_code', $this->filterCompany);
        }

        if ($this->filterType !== '') {
            $query->where($typeCol, $this->filterType);
        }

        // Urutan: default urut dari induk Group 1 untuk Group 2 dan 3
        if ($this->sortField === 'code' && $tab !== 'group1') {
            $query->orderBy('t.company_code')->orderBy('t.tbag_1_code');

            if ($tab === 'group3') {
                $query->orderBy('t.tbag_2_code');
            }
        }

        $column = match ($this->sortField) {
            'account_type' => $typeCol,
            default        => 't.' . $this->sortField,
        };

        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction)->orderBy('t.id');
    }

    protected function perPageValue(): int
    {
        return in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;
    }

    /* ---------------------------------------------------------------
     |  Data pendukung (di-cache supaya render tidak query berulang)
     * ------------------------------------------------------------- */

    protected function counts(): array
    {
        return Cache::remember('tb.counts', 300, fn() => [
            'group1' => DB::table(self::T1)->count(),
            'group2' => DB::table(self::T2)->count(),
            'group3' => DB::table(self::T3)->count(),
        ]);
    }

    protected function companies(): array
    {
        return Cache::remember('tb.companies', 300, fn() => DB::table('companies')->orderBy('code')->pluck('code')->all());
    }

    protected function group2Options(): array
    {
        return Cache::remember('tb.g2options', 300, fn() => DB::table(self::T2 . ' as g2')
            ->leftJoin(self::T1 . ' as g1', function ($join) {
                $join->on('g1.company_code', '=', 'g2.company_code')
                    ->on('g1.code', '=', 'g2.tbag_1_code')
                    ->on('g1.account_type', '=', 'g2.tbag_1_account_type');
            })
            ->orderBy('g2.company_code')
            ->orderBy('g2.tbag_1_code')
            ->orderBy('g2.code')
            ->get([
                'g2.id',
                'g2.company_code',
                'g2.code',
                'g2.description',
                'g2.tbag_1_code as g1_code',
                'g1.description as g1_description',
            ])
            ->map(fn($r) => [
                'id'             => (int) $r->id,
                'company_code'   => $r->company_code,
                'code'           => $r->code,
                'description'    => $r->description,
                'g1_code'        => $r->g1_code,
                'g1_description' => $r->g1_description,
            ])
            ->all());
    }

    protected function flushCache(): void
    {
        Cache::forget('tb.counts');
        Cache::forget('tb.companies');
        Cache::forget('tb.g2options');
    }

    public function render()
    {
        $rows = $this->rowsQuery()->paginate($this->perPageValue());

        // Halaman terakhir kosong (misalnya setelah hapus): mundur satu halaman
        if ($rows->isEmpty() && $rows->currentPage() > 1) {
            $this->setPage($rows->lastPage());
            $rows = $this->rowsQuery()->paginate($this->perPageValue());
        }

        return view('livewire.master.trial-balance', [
            'rows'          => $rows,
            'counts'        => $this->counts(),
            'companies'     => collect($this->companies()),
            'accountTypes'  => self::ACCOUNT_TYPES,
            'group2Options' => $this->group2Options(),
        ]);
    }
}
