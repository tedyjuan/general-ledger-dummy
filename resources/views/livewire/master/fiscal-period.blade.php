{{-- resources/views/livewire/master/fiscal-period.blade.php --}}
<div wire:keydown.escape.window="closeModal">
    @php
        $periods = $this->periods;
        $ringkas = $this->ringkas;
        $bisaUbah = $this->bisaUbah;
        $total = array_sum($ringkas);
        $adaFilter = $depo !== '' || $statusFilter !== '';
        $colspan = $bisaUbah ? 8 : 6;

        $idHalaman = $periods->pluck('id')->map(fn($i) => (string) $i);
        $semuaDipilih = $idHalaman->isNotEmpty() && $idHalaman->diff($selected)->isEmpty();

        // Kelas ditulis utuh agar terdeteksi Tailwind
        $kartu = [
            'open' => [
                'label' => 'Open',
                'icon' => 'icon-[tabler--lock-open]',
                'ikon' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
                'bar' => 'bg-emerald-500',
                'aktif' => 'ring-2 ring-emerald-400',
            ],
            'closed' => [
                'label' => 'Closed',
                'icon' => 'icon-[tabler--lock]',
                'ikon' => 'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400',
                'bar' => 'bg-red-500',
                'aktif' => 'ring-2 ring-red-400',
            ],
            'undefined' => [
                'label' => 'Belum ditentukan',
                'icon' => 'icon-[tabler--help-circle]',
                'ikon' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                'bar' => 'bg-slate-400',
                'aktif' => 'ring-2 ring-slate-400',
            ],
        ];

        $kolom = [
            'depo_code' => ['Depo', true],
            'period' => ['Periode', true],
            'year' => ['Tahun', true],
            'tanggal' => ['Tanggal', false],
            'status' => ['Status', true],
        ];

        $selectCls =
            'select select-sm w-full bg-white font-normal dark:border-slate-700 dark:bg-slate-900 dark:text-white';
    @endphp

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                Periode Fiskal
                <span class="badge badge-soft badge-success ml-2 align-middle text-xs">{{ $activeCompany }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Buka atau tutup periode akuntansi per depo</p>
        </div>

        <button type="button" wire:click="refreshData"
            class="btn btn-outline btn-success flex items-center gap-1.5 shadow-xs">
            <span wire:loading.remove wire:target="refreshData" class="icon-[tabler--refresh] size-4"></span>
            <span wire:loading wire:target="refreshData" class="icon-[tabler--loader-2] size-4 animate-spin"></span>
            Refresh
        </button>
    </div>

    {{-- INFO --}}
    <div
        class="mb-6 flex items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-300">
        <span class="icon-[tabler--info-circle] mt-0.5 size-4 shrink-0"></span>
        <p>
            Periode dibuat otomatis setiap akhir tahun, jadi halaman ini hanya untuk mengubah status
            <strong>Open</strong> atau <strong>Closed</strong>. Periode <strong>13</strong> dan <strong>14</strong>
            adalah periode penyesuaian. Centang beberapa baris untuk mengubah sekaligus.
        </p>
    </div>

    {{-- RINGKASAN (klik untuk filter) --}}
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($kartu as $kunci => $k)
            @php $persen = $total > 0 ? round($ringkas[$kunci] / $total * 100) : 0; @endphp
            <button type="button" wire:click="filterStatus('{{ $kunci }}')" wire:key="kartu-{{ $kunci }}"
                aria-pressed="{{ $statusFilter === $kunci ? 'true' : 'false' }}"
                class="card card-border gap-3 border-slate-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900 {{ $statusFilter === $kunci ? $k['aktif'] : '' }}">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $k['ikon'] }}">
                        <span class="{{ $k['icon'] }} size-5"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-2xl font-bold leading-none text-slate-800 dark:text-white">
                            {{ number_format($ringkas[$kunci]) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $k['label'] }}</p>
                    </div>
                    <span class="text-xs font-medium text-slate-400">{{ $persen }}%</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full {{ $k['bar'] }}" style="width: {{ $persen }}%"></div>
                </div>
            </button>
        @endforeach
    </div>

    {{-- BAR PILIHAN --}}
    @if ($bisaUbah && count($selected) > 0)
        <div
            class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900 dark:bg-emerald-950/30">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="font-semibold text-emerald-800 dark:text-emerald-300">{{ count($selected) }} periode
                    dipilih</span>
                @if ($periods->total() > count($selected))
                    <button type="button" wire:click="pilihSemuaHasil"
                        class="text-emerald-700 underline hover:no-underline dark:text-emerald-400">
                        Pilih semua {{ number_format($periods->total()) }} hasil filter
                    </button>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="confirmBulk('open')"
                    class="btn btn-sm btn-success flex items-center gap-1.5">
                    <span class="icon-[tabler--lock-open] size-4"></span> Set Open
                </button>
                <button type="button" wire:click="confirmBulk('closed')"
                    class="btn btn-sm btn-outline btn-error flex items-center gap-1.5">
                    <span class="icon-[tabler--lock] size-4"></span> Set Closed
                </button>
                <button type="button" wire:click="$set('selected', [])" class="btn btn-sm btn-ghost">Batal</button>
            </div>
        </div>
    @endif

    {{-- KARTU TABEL --}}
    <div class="card card-border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

        {{-- Judul + filter --}}
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-4 dark:border-slate-800 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">Daftar Periode</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ number_format($periods->total()) }} periode
                    @if ($year !== '')
                        · tahun {{ $year }}
                    @endif
                    @if ($adaFilter)
                        · terfilter
                    @endif
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:flex xl:items-end">
                {{-- Depo: dropdown dengan pencarian (Alpine + Tailwind/FlyonUI, tanpa library tambahan) --}}
                <div class="block xl:w-64" wire:ignore>
                    <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Depo</span>

                    <div class="relative" x-data="{
                        open: false,
                        q: '',
                        aktif: 0,
                        items: @js($this->opsiDepo->map(fn($nama, $kode) => ['kode' => (string) $kode, 'nama' => (string) $nama])->values()),
                        get terpilih() { return this.items.find(i => i.kode === this.$wire.depo) },
                        get daftar() {
                            const q = this.q.toLowerCase().trim();
                            if (q === '') return [{ kode: '', nama: 'Semua depo' }, ...this.items];
                            return this.items.filter(i => (i.kode + ' ' + i.nama).toLowerCase().includes(q));
                        },
                        buka() {
                            this.open = true;
                            this.q = '';
                            this.aktif = Math.max(0, this.daftar.findIndex(i => i.kode === this.$wire.depo));
                            this.$nextTick(() => {
                                this.$refs.cari.focus();
                                this.gulir();
                            });
                        },
                        tutup() { this.open = false; },
                        pilih(kode) {
                            this.$wire.set('depo', kode);
                            this.tutup();
                        },
                        pilihAktif() { const it = this.daftar[this.aktif]; if (it) this.pilih(it.kode); },
                        geser(n) {
                            const max = this.daftar.length - 1;
                            if (max < 0) return;
                            this.aktif = Math.min(max, Math.max(0, this.aktif + n));
                            this.gulir();
                        },
                        gulir() {
                            this.$nextTick(() => this.$refs.list.querySelector('[data-aktif=true]')?.scrollIntoView({ block: 'nearest' }));
                        },
                    }" @click.outside="tutup()"
                        @keydown.escape.window="tutup()">

                        <button type="button" @click="open ? tutup() : buka()"
                            class="input input-sm flex w-full items-center justify-between gap-2 bg-white text-left dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                            aria-haspopup="listbox" :aria-expanded="open" aria-label="Filter depo">
                            <span class="truncate" :class="terpilih ? '' : 'text-slate-500 dark:text-slate-400'"
                                x-text="terpilih ? terpilih.kode + ' - ' + terpilih.nama : 'Semua depo'"></span>
                            <span class="icon-[tabler--selector] size-4 shrink-0 text-slate-400"></span>
                        </button>

                        <div x-show="open" x-cloak x-transition.opacity.duration.100ms
                            class="absolute left-0 z-30 mt-1 w-full min-w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            <div class="relative border-b border-slate-100 p-2 dark:border-slate-800">
                                <span
                                    class="icon-[tabler--search] pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
                                <input type="text" x-ref="cari" x-model="q" @input="aktif = 0"
                                    @keydown.arrow-down.prevent="geser(1)" @keydown.arrow-up.prevent="geser(-1)"
                                    @keydown.enter.prevent="pilihAktif()" placeholder="Cari kode atau nama depo..."
                                    autocomplete="off"
                                    class="input input-sm w-full bg-white pl-8 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            </div>

                            <ul x-ref="list" role="listbox" class="max-h-60 overflow-y-auto p-1">
                                <template x-for="(it, i) in daftar" :key="it.kode">
                                    <li>
                                        <button type="button" role="option" @click="pilih(it.kode)"
                                            @mouseenter="aktif = i" :data-aktif="aktif === i"
                                            :aria-selected="it.kode === $wire.depo"
                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-1.5 text-left text-sm text-slate-700 dark:text-slate-200"
                                            :class="aktif === i ?
                                                'bg-emerald-50 text-emerald-800 dark:bg-slate-800 dark:text-emerald-300' :
                                                ''">
                                            <span class="truncate">
                                                <template x-if="it.kode !== ''">
                                                    <span><span class="font-semibold" x-text="it.kode"></span> - <span
                                                            x-text="it.nama"></span></span>
                                                </template>
                                                <template x-if="it.kode === ''">
                                                    <span class="text-slate-500 dark:text-slate-400"
                                                        x-text="it.nama"></span>
                                                </template>
                                            </span>
                                            <span x-show="it.kode === $wire.depo"
                                                class="icon-[tabler--check] size-4 shrink-0 text-emerald-600"></span>
                                        </button>
                                    </li>
                                </template>

                                <li x-show="daftar.length === 0" class="px-3 py-4 text-center text-sm text-slate-400">
                                    Depo tidak ditemukan
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <label class="block xl:w-36">
                    <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Tahun</span>
                    <select wire:model.live="year" class="{{ $selectCls }}" aria-label="Filter tahun">
                        <option value="">Semua tahun</option>
                        @foreach ($this->opsiTahun as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block xl:w-44">
                    <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Status</span>
                    <select wire:model.live="statusFilter" class="{{ $selectCls }}" aria-label="Filter status">
                        <option value="">Semua status</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                        <option value="undefined">Belum ditentukan</option>
                    </select>
                </label>

                @if ($adaFilter)
                    <button type="button" wire:click="refreshData"
                        class="btn btn-sm btn-ghost flex items-center gap-1.5 sm:col-span-3 xl:col-span-1">
                        <span class="icon-[tabler--filter-off] size-4"></span>
                        Hapus filter
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto" wire:loading.class="opacity-60"
            wire:target="year, depo, statusFilter, perPage, sortBy, gotoPage, nextPage, previousPage, filterStatus">
            <table class="table table-sm">
                <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    {{-- Judul kolom --}}
                    <tr>
                        @if ($bisaUbah)
                            <th class="w-10">
                                <input type="checkbox" class="checkbox checkbox-success checkbox-sm"
                                    aria-label="Pilih semua di halaman ini" wire:click="togglePage"
                                    @checked($semuaDipilih)>
                            </th>
                        @endif
                        <th class="w-12 text-center">No.</th>

                        @foreach ($kolom as $field => [$label, $bisaUrut])
                            @if ($bisaUrut)
                                <th class="cursor-pointer select-none hover:text-emerald-700"
                                    wire:click="sortBy('{{ $field }}')">
                                    <div class="flex items-center gap-1">
                                        {{ $label }}
                                        @if ($sortField === $field)
                                            <span
                                                class="{{ $sortDirection === 'asc' ? 'icon-[tabler--chevron-up]' : 'icon-[tabler--chevron-down]' }} size-3.5"></span>
                                        @endif
                                    </div>
                                </th>
                            @else
                                <th>{{ $label }}</th>
                            @endif
                        @endforeach

                        @if ($bisaUbah)
                            <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($periods as $p)
                        <tr wire:key="periode-{{ $p->id }}"
                            class="transition-colors hover:bg-emerald-50/40 has-[:checked]:bg-emerald-50/60 dark:hover:bg-slate-800/60 dark:has-[:checked]:bg-emerald-950/20">
                            @if ($bisaUbah)
                                <td>
                                    <input type="checkbox" wire:model.live="selected" value="{{ $p->id }}"
                                        class="checkbox checkbox-success checkbox-sm"
                                        aria-label="Pilih periode {{ $p->periode_label }} depo {{ $p->depo_code }}">
                                </td>
                            @endif

                            <td class="text-center text-slate-500 dark:text-slate-400">
                                {{ $periods->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <div class="whitespace-nowrap font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ $p->depo_code }}</div>
                                @if (isset($this->opsiDepo[$p->depo_code]))
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $this->opsiDepo[$p->depo_code] }}</div>
                                @endif
                            </td>

                            <td>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-medium text-slate-800 dark:text-white">{{ $p->periode_label }}</span>
                                    @if ($p->is_berjalan)
                                        <span class="badge badge-soft badge-info badge-xs">Berjalan</span>
                                    @endif
                                    @if ($p->is_penyesuaian)
                                        <span class="badge badge-soft badge-warning badge-xs">Adj.</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $p->nama_periode }}</div>
                            </td>

                            <td class="text-slate-600 dark:text-slate-300">{{ $p->year }}</td>

                            <td class="whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                {{ $p->start_date?->translatedFormat('d M Y') }}
                                <span class="text-slate-400">s/d</span>
                                {{ $p->end_date?->translatedFormat('d M Y') }}
                            </td>

                            <td>
                                @switch($p->status_normal)
                                    @case('open')
                                        <span class="badge badge-soft badge-success badge-sm gap-1"><span
                                                class="icon-[tabler--lock-open] size-3.5"></span> Open</span>
                                    @break

                                    @case('closed')
                                        <span class="badge badge-soft badge-error badge-sm gap-1"><span
                                                class="icon-[tabler--lock] size-3.5"></span> Closed</span>
                                    @break

                                    @default
                                        <span class="badge badge-soft badge-neutral badge-sm gap-1"><span
                                                class="icon-[tabler--help-circle] size-3.5"></span> Belum ditentukan</span>
                                @endswitch
                            </td>

                            @if ($bisaUbah)
                                <td>
                                    <div class="flex items-center justify-center">
                                        <button type="button" wire:click="edit({{ $p->id }})"
                                            wire:loading.attr="disabled" wire:target="edit({{ $p->id }})"
                                            class="btn btn-sm btn-outline btn-primary" title="Ubah status"
                                            aria-label="Ubah status periode">
                                            <span wire:loading.remove wire:target="edit({{ $p->id }})"
                                                class="icon-[tabler--pencil] size-4"></span>
                                            <span wire:loading wire:target="edit({{ $p->id }})"
                                                class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}"
                                    class="p-10 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2.5">
                                        <div
                                            class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                            <span
                                                class="icon-[tabler--calendar-off] size-7 text-slate-400 dark:text-slate-500"></span>
                                        </div>
                                        <p class="text-base font-semibold text-slate-700 dark:text-slate-300">Tidak ada
                                            periode fiskal ditemukan</p>
                                        <p class="text-xs text-slate-400">
                                            {{ $adaFilter ? 'Coba ubah filter tahun, depo, atau status.' : 'Periode dibuat otomatis setiap akhir tahun.' }}
                                        </p>
                                        @if ($adaFilter)
                                            <button type="button" wire:click="refreshData"
                                                class="btn btn-sm btn-outline btn-success mt-2">Hapus filter</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div
                class="flex flex-col gap-3 border-t border-slate-100 p-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                    <p>
                        Menampilkan
                        <span
                            class="font-medium text-slate-700 dark:text-slate-200">{{ $periods->firstItem() ?? 0 }}</span>–<span
                            class="font-medium text-slate-700 dark:text-slate-200">{{ $periods->lastItem() ?? 0 }}</span>
                        dari
                        <span
                            class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($periods->total()) }}</span>
                    </p>
                    <label class="flex items-center gap-2">
                        <span class="text-xs">Per halaman</span>
                        <select wire:model.live="perPage"
                            class="select select-xs w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            @foreach ([25, 50, 100] as $n)
                                <option value="{{ $n }}">{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                {{-- Pagination ringkas: 1 2 … 5 6 7 … 24 25 --}}
                @if ($periods->hasPages())
                    @php
                        $hlmNow = $periods->currentPage();
                        $hlmLast = $periods->lastPage();
                        $hlmName = $periods->getPageName();

                        // halaman kunci: 2 awal, sekitar aktif (±1), 2 akhir
                        $hlmKunci = collect([1, 2, $hlmNow - 1, $hlmNow, $hlmNow + 1, $hlmLast - 1, $hlmLast])
                            ->filter(fn($n) => $n >= 1 && $n <= $hlmLast)
                            ->unique()
                            ->sort()
                            ->values();

                        // sisipkan "…" untuk celah; celah 1 halaman langsung ditampilkan angkanya
                        $hlmItem = [];
                        foreach ($hlmKunci as $i => $n) {
                            if ($i > 0) {
                                $selisih = $n - $hlmKunci[$i - 1];
                                if ($selisih === 2) {
                                    $hlmItem[] = $n - 1;
                                } elseif ($selisih > 2) {
                                    $hlmItem[] = '...';
                                }
                            }
                            $hlmItem[] = $n;
                        }
                    @endphp

                    <nav class="flex items-center gap-1" aria-label="Pagination">
                        <button type="button" wire:click="previousPage('{{ $hlmName }}')"
                            @disabled($periods->onFirstPage()) class="btn btn-sm btn-square btn-ghost" aria-label="Sebelumnya">
                            <span class="icon-[tabler--chevron-left] size-4"></span>
                        </button>

                        @foreach ($hlmItem as $k => $n)
                            @if ($n === '...')
                                <span class="select-none px-1 text-slate-400"
                                    wire:key="dots-{{ $k }}">…</span>
                            @else
                                <button type="button" wire:key="page-{{ $n }}"
                                    wire:click="gotoPage({{ $n }}, '{{ $hlmName }}')"
                                    @if ($n === $hlmNow) aria-current="page" @endif
                                    class="btn btn-sm btn-square {{ $n === $hlmNow ? 'btn-success' : 'btn-ghost' }}">
                                    {{ $n }}
                                </button>
                            @endif
                        @endforeach

                        <button type="button" wire:click="nextPage('{{ $hlmName }}')" @disabled(!$periods->hasMorePages())
                            class="btn btn-sm btn-square btn-ghost" aria-label="Berikutnya">
                            <span class="icon-[tabler--chevron-right] size-4"></span>
                        </button>
                    </nav>
                @endif
            </div>
        </div>

        {{-- MODAL UBAH STATUS (SATU PERIODE) --}}
        @if ($bisaUbah && $showEdit)
            <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm"
                wire:click.self="closeModal" role="dialog" aria-modal="true" aria-labelledby="judul-edit">
                <div
                    class="relative my-8 w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="icon-[tabler--calendar-event] size-6 text-emerald-600 dark:text-emerald-400"></span>
                            <h3 id="judul-edit" class="text-lg font-bold text-slate-800 dark:text-white">Ubah Status
                                Periode</h3>
                        </div>
                        <button type="button" wire:click="closeModal" aria-label="Tutup"
                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                            <span class="icon-[tabler--x] size-5"></span>
                        </button>
                    </div>

                    <form wire:submit="save">
                        <div class="space-y-5 p-6">
                            <dl
                                class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-800/50">
                                <div class="col-span-2">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">Depo</dt>
                                    <dd class="font-semibold text-slate-800 dark:text-white">{{ $info['depo'] ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">Tahun</dt>
                                    <dd class="font-semibold text-slate-800 dark:text-white">{{ $info['tahun'] ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">Periode</dt>
                                    <dd class="font-semibold text-slate-800 dark:text-white">{{ $info['periode'] ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">Tanggal mulai</dt>
                                    <dd class="text-slate-700 dark:text-slate-200">{{ $info['mulai'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">Tanggal selesai</dt>
                                    <dd class="text-slate-700 dark:text-slate-200">{{ $info['selesai'] ?? '-' }}</dd>
                                </div>
                            </dl>

                            <fieldset>
                                <legend class="mb-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                    Status periode <span class="text-red-500">*</span>
                                    @if (($info['awal'] ?? '') === 'undefined')
                                        <span class="font-normal text-slate-400">(saat ini belum ditentukan)</span>
                                    @endif
                                </legend>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition-colors hover:border-emerald-300 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:border-slate-700 dark:has-[:checked]:bg-emerald-950/30">
                                        <input type="radio" wire:model="status" value="open"
                                            class="radio radio-success radio-sm mt-0.5">
                                        <span>
                                            <span
                                                class="flex items-center gap-1 text-sm font-semibold text-slate-800 dark:text-white">
                                                <span class="icon-[tabler--lock-open] size-4 text-emerald-600"></span> Open
                                            </span>
                                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Transaksi
                                                pada periode ini dapat diposting.</span>
                                        </span>
                                    </label>

                                    <label
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition-colors hover:border-red-300 has-[:checked]:border-red-400 has-[:checked]:bg-red-50 dark:border-slate-700 dark:has-[:checked]:bg-red-950/20">
                                        <input type="radio" wire:model="status" value="closed"
                                            class="radio radio-error radio-sm mt-0.5">
                                        <span>
                                            <span
                                                class="flex items-center gap-1 text-sm font-semibold text-slate-800 dark:text-white">
                                                <span class="icon-[tabler--lock] size-4 text-red-600"></span> Closed
                                            </span>
                                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Periode
                                                dikunci, tidak ada transaksi baru.</span>
                                        </span>
                                    </label>
                                </div>

                                @error('status')
                                    <span class="mt-2 block text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </fieldset>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <button type="button" wire:click="closeModal"
                                class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">Batal</button>
                            <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5"
                                wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                                    <span class="icon-[tabler--device-floppy] size-4"></span> Simpan status
                                </span>
                                <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                                    <span class="icon-[tabler--loader-2] size-4 animate-spin"></span> Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- MODAL KONFIRMASI MASSAL --}}
        @if ($bisaUbah && $showBulk)
            @php $buka = $bulkStatus === 'open'; @endphp
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                wire:click.self="closeModal" role="alertdialog" aria-modal="true" aria-labelledby="judul-bulk">
                <div
                    class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-full {{ $buka ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400' }}">
                            <span class="{{ $buka ? 'icon-[tabler--lock-open]' : 'icon-[tabler--lock]' }} size-6"></span>
                        </div>
                        <div>
                            <h3 id="judul-bulk" class="text-lg font-bold text-slate-800 dark:text-white">Ubah status
                                massal</h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                Ubah status
                                <span class="font-semibold text-slate-800 dark:text-white">{{ count($selected) }}
                                    periode</span>
                                menjadi
                                <span
                                    class="font-semibold text-slate-800 dark:text-white">{{ $buka ? 'Open' : 'Closed' }}</span>?
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeModal"
                            class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">Batal</button>
                        <button type="button" wire:click="bulkUpdate" wire:loading.attr="disabled"
                            wire:target="bulkUpdate"
                            class="btn btn-sm flex items-center gap-1.5 {{ $buka ? 'btn-success' : 'btn-error' }}">
                            <span wire:loading.remove wire:target="bulkUpdate">Ya, ubah</span>
                            <span wire:loading wire:target="bulkUpdate" class="flex items-center gap-1.5">
                                <span class="icon-[tabler--loader-2] size-4 animate-spin"></span> Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
