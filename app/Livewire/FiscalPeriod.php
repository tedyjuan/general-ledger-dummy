<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Depo;
use App\Models\FiscalPeriod as FiscalPeriodModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FiscalPeriod extends Component
{
    use WithPagination, WithToast, Otorisasi, ActiveCompany;

    // Harus sama dengan key menu di Casbin / config/navigation.php
    private const RESOURCE = 'fiscal-period';

    private const SORTABLE = ['depo_code', 'period', 'year', 'status'];
    private const FILTERS  = ['year', 'depo', 'statusFilter', 'perPage'];

    /* STATE TABEL (filter & urutan tersimpan di URL) */
    #[Url(as: 'tahun')]
    public string $year = '';
    #[Url(as: 'depo', except: '')]
    public string $depo = '';
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';
    #[Url(as: 'urut')]
    public string $sortField = 'depo_code';
    #[Url(as: 'arah')]
    public string $sortDirection = 'asc';
    #[Url(as: 'per')]
    public int $perPage = 25;

    /* PILIHAN MASSAL */
    /** @var array<int,string> */
    public array $selected = [];

    /* STATE MODAL UBAH SATU PERIODE */
    public bool $showEdit = false;
    #[Locked]
    public ?int $editId = null;
    public string $status = '';
    public array $info = [];

    /* STATE MODAL KONFIRMASI MASSAL */
    public bool $showBulk = false;
    #[Locked]
    public string $bulkStatus = 'open';

    protected array $messages = [
        'status.required' => 'Pilih status periode.',
        'status.in'       => 'Status tidak valid.',
    ];

    public function mount(): void
    {
        // Pastikan company sudah terisi sebelum query apa pun. Urutan hook mount trait
        // tidak boleh diandalkan: kalau tabCompany masih '' saat opsiTahun dihitung,
        // hasilnya kosong dan ikut ter-cache sampai render pertama selesai.
        if ($this->tabCompany === '') {
            $this->mountActiveCompany();
        }

        $tahun = $this->opsiTahun;
        $ada = fn(string $y) => $tahun->contains(fn($t) => (string) $t === $y);

        if ($this->year !== '' && ! $ada($this->year)) {
            $this->year = '';
        }

        if ($this->year === '') {
            $sekarang = (string) now()->year;
            $this->year = $ada($sekarang) ? $sekarang : (string) ($tahun->first() ?? '');
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'depo_code';
        }
        if (! in_array($this->perPage, [25, 50, 100], true)) {
            $this->perPage = 25;
        }
    }

    /* DATA */

    #[Computed]
    public function bisaUbah(): bool
    {
        return (bool) auth()->user()?->izin(self::RESOURCE, 'update');
    }

    /** Semua query periode wajib lewat sini: dibatasi ke depo milik company aktif. */
    private function periodeQuery(): Builder
    {
        return FiscalPeriodModel::query()->forCompany($this->tabCompany);
    }

    #[Computed]
    public function opsiTahun(): Collection
    {
        return $this->periodeQuery()->select('year')->distinct()->orderByDesc('year')->pluck('year');
    }

    #[Computed]
    public function opsiDepo(): Collection
    {
        // asumsi: tabel depos punya kolom code, name, company_code
        return Depo::query()
            ->where('company_code', $this->tabCompany)
            ->orderBy('code')
            ->pluck('name', 'code');
    }

    /** Filter tahun + depo (tanpa status), dipakai juga untuk kartu ringkasan. */
    private function baseQuery(): Builder
    {
        return $this->periodeQuery()
            ->when($this->year !== '', fn(Builder $q) => $q->where('year', $this->year))
            ->when($this->depo !== '', fn(Builder $q) => $q->where('depo_code', $this->depo));
    }

    private function filteredQuery(): Builder
    {
        return $this->baseQuery()->when($this->statusFilter !== '', function (Builder $q) {
            // status NULL dianggap "undefined"
            $this->statusFilter === 'undefined'
                ? $q->where(fn(Builder $w) => $w->whereNull('status')->orWhere('status', 'undefined'))
                : $q->where('status', $this->statusFilter);
        });
    }

    #[Computed]
    public function periods()
    {
        $dir = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $query = $this->filteredQuery();

        // kolom period bertipe string: urutkan sebagai angka agar 2 < 10
        $this->sortField === 'period'
            ? $query->orderByRaw("CAST(period AS UNSIGNED) {$dir}")
            : $query->orderBy($this->sortField, $dir);

        return $query
            ->orderBy('depo_code')
            ->orderByRaw('CAST(period AS UNSIGNED)')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function ringkas(): array
    {
        $rows = $this->baseQuery()
            ->selectRaw("COALESCE(status, 'undefined') AS st, COUNT(*) AS total")
            ->groupBy('st')
            ->pluck('total', 'st');

        return [
            'open'      => (int) ($rows['open'] ?? 0),
            'closed'    => (int) ($rows['closed'] ?? 0),
            'undefined' => (int) ($rows['undefined'] ?? 0),
        ];
    }

    /* HOOK, FILTER, URUTAN, PILIHAN */

    public function updated(string $name): void
    {
        if (in_array($name, self::FILTERS, true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function filterStatus(string $status): void
    {
        if (! in_array($status, ['open', 'closed', 'undefined'], true)) {
            return;
        }

        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
        $this->selected = [];
    }

    public function refreshData(): void
    {
        $this->reset('depo', 'statusFilter', 'selected');
        $this->resetPage();
        unset($this->periods, $this->ringkas);

        $this->toast('Data Periode Fiskal berhasil dimuat ulang.', 'info');
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    /** Centang / lepas semua baris di halaman ini. */
    public function togglePage(): void
    {
        $ids = $this->periods->pluck('id')->map(fn($i) => (string) $i);

        $this->selected = $ids->diff($this->selected)->isEmpty()
            ? collect($this->selected)->diff($ids)->values()->all()
            : collect($this->selected)->merge($ids)->unique()->values()->all();
    }

    public function pilihSemuaHasil(): void
    {
        $this->selected = $this->filteredQuery()->pluck('id')->map(fn($i) => (string) $i)->all();
    }

    /* UBAH SATU PERIODE */

    public function edit(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        $this->resetErrorBag();

        // Dibatasi company aktif: ID milik company lain -> 404
        $p = $this->periodeQuery()->findOrFail($id);

        $this->editId = $p->id;
        $this->status = $p->status_normal === 'undefined' ? '' : $p->status_normal;
        $this->info = [
            'depo'    => $p->depo_code . (isset($this->opsiDepo[$p->depo_code]) ? ' - ' . $this->opsiDepo[$p->depo_code] : ''),
            'tahun'   => $p->year,
            'periode' => $p->periode_label . ($p->is_penyesuaian ? ' (Penyesuaian)' : ''),
            'mulai'   => $p->start_date?->translatedFormat('d F Y'),
            'selesai' => $p->end_date?->translatedFormat('d F Y'),
            'awal'    => $p->status_normal,
        ];
        $this->showEdit = true;
    }

    public function save(): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        $this->validate(['status' => ['required', 'in:open,closed']]);

        try {
            $p = $this->periodeQuery()->findOrFail($this->editId);
            $p->update(['status' => $this->status]);

            $this->toast("Periode {$p->periode_label} depo {$p->depo_code} tahun {$p->year} diubah menjadi " . ucfirst($this->status) . '.');
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal menyimpan data. Silakan coba lagi.', 'error');

            return;
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->resetErrorBag();
        $this->reset('showEdit', 'showBulk', 'editId', 'status', 'info');
    }

    /* UBAH MASSAL */

    public function confirmBulk(string $status): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        if (in_array($status, ['open', 'closed'], true) && $this->selected) {
            $this->bulkStatus = $status;
            $this->showBulk = true;
        }
    }

    public function bulkUpdate(): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        abort_unless(in_array($this->bulkStatus, ['open', 'closed'], true), 422);

        try {
            // Dibatasi company aktif: ID milik company lain tidak ikut terubah
            $jumlah = $this->periodeQuery()
                ->whereIn('id', $this->selected)
                ->update(['status' => $this->bulkStatus]);
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal mengubah status. Silakan coba lagi.', 'error');

            return;
        }

        $this->toast("{$jumlah} periode diubah menjadi " . ucfirst($this->bulkStatus) . '.');
        $this->selected = [];
        $this->closeModal();
    }

    /* RENDER */

    public function render()
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        return view('livewire.master.fiscal-period', [
            'activeCompany' => $this->tabCompany,
        ]);
    }
}
