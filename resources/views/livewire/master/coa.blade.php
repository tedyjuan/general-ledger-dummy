<div x-data="{
    deleteOpen: false,
    deleteId: null,
    deleteName: '',
    downloadOpen: false,
}" @delete-close.window="deleteOpen = false" @download-open.window="downloadOpen = true"
    @download-close.window="downloadOpen = false" @keydown.escape.window="deleteOpen = false; downloadOpen = false">
    @php
        $user = auth()->user();
        $bisaTambah = (bool) $user?->izin('coa', 'create');
        $bisaEdit = (bool) $user?->izin('coa', 'update');
        $bisaHapus = (bool) $user?->izin('coa', 'delete');
        $bisaDownload = (bool) $user?->izin('coa', 'read'); // ganti 'read' kalau punya aksi khusus 'download'
        $adaAksi = $bisaEdit || $bisaHapus;
        $adaFilter = $search !== '' || $levelFilter !== '' || $typeFilter !== '';
        $colspan = $adaAksi ? 8 : 7;

        // indentasi per level (hanya dipakai di mode tree)
        $indent = ['header' => 'ps-0', 'ledger' => 'ps-6', 'subledger' => 'ps-12'];
        $levelBadge = [
            'header' => ['badge-primary', 'Header'],
            'ledger' => ['badge-info', 'Ledger'],
            'subledger' => ['badge-warning', 'Subledger'],
        ];

        // kolom tabel: [label, field sortable|null, class tambahan]
        $kolom = [
            ['No.', null, 'w-12 text-center'],
            ['No. Akun', 'account_number', ''],
            ['Nama Akun', 'name', ''],
            ['Level', null, ''],
            ['Tipe', 'account_type', ''],
            ['Metode', null, ''],
            ['Trial Balance', null, ''],
        ];

        // gaya chip pilihan di modal download
        $chip =
            'inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-emerald-400 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 dark:border-slate-700 dark:text-slate-300';
    @endphp

    {{-- ============ HEADER HALAMAN ============ --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Chart of Accounts</h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="icon-[tabler--building] size-3.5"></span>
                    {{ $activeCompany }}
                </span>
                <span>Kelola akun Header, Ledger, dan Subledger</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="refreshData" wire:loading.attr="disabled" wire:target="refreshData"
                class="btn btn-outline btn-success flex items-center gap-1.5 shadow-xs" title="Muat ulang data"
                aria-label="Muat ulang data">
                <span wire:loading.remove wire:target="refreshData" class="icon-[tabler--refresh] size-5"></span>
                <span wire:loading wire:target="refreshData" class="icon-[tabler--loader-2] size-5 animate-spin"></span>
                Refresh
            </button>
            @if ($bisaTambah)
                <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create"
                    class="btn btn-success gap-2 shadow-xs">
                    <span wire:loading.remove wire:target="create" class="icon-[tabler--plus] size-4"></span>
                    <span wire:loading wire:target="create" class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                    Tambah Akun
                </button>
            @endif
        </div>
    </div>

    @if (session('toast'))
        <div x-data x-init="$dispatch('toast', { type: '{{ session('toast.type') }}', message: @js(session('toast.message')) })"></div>
    @endif

    {{-- ============ KARTU TABEL ============ --}}
    <div
        class="card card-border overflow-hidden border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

        {{-- Judul kartu + kontrol tree --}}
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="icon-[tabler--list-tree] size-5"></span>
                </div>
                <div>
                    <h2 class="font-semibold leading-tight text-slate-800 dark:text-white">Daftar Akun</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        <span
                            class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format($accounts->total()) }}</span>
                        {{ $isTree ? 'header' : 'akun ditemukan' }}
                    </p>
                </div>
            </div>

            @if ($isTree)
                <div class="join">
                    <button type="button" wire:click="expandAll" wire:loading.attr="disabled" wire:target="expandAll"
                        class="btn btn-sm btn-outline btn-success join-item gap-1.5">
                        <span class="icon-[tabler--arrows-diagonal] size-4"></span>
                        Buka Semua
                    </button>
                    <button type="button" wire:click="collapseAll"
                        class="btn btn-sm btn-outline btn-success join-item gap-1.5">
                        <span class="icon-[tabler--arrows-diagonal-minimize-2] size-4"></span>
                        Tutup Semua
                    </button>
                    @if ($bisaDownload)
                        <button type="button" wire:click="openDownload" wire:loading.attr="disabled"
                            wire:target="openDownload" class="btn btn-sm btn-outline btn-success join-item gap-1.5">
                            <span wire:loading.remove wire:target="openDownload"
                                class="icon-[tabler--download] size-4"></span>
                            <span wire:loading wire:target="openDownload"
                                class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                            Download
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Toolbar pencarian & filter --}}
        <div
            class="flex flex-col gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/30 lg:flex-row lg:items-center">
            <div class="relative w-full lg:max-w-sm">
                <span
                    class="icon-[tabler--search] pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
                <input type="search" wire:model.live.debounce.400ms="search"
                    placeholder="Cari nomor, nama, deskripsi..."
                    class="input input-sm w-full bg-white ps-9 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
            </div>

            <div class="flex flex-wrap items-center gap-2 lg:ms-auto">
                <select wire:model.live="levelFilter"
                    class="select select-sm w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Semua level</option>
                    <option value="header">Header</option>
                    <option value="ledger">Ledger</option>
                    <option value="subledger">Subledger</option>
                </select>

                <select wire:model.live="typeFilter"
                    class="select select-sm w-auto bg-white capitalize dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Semua tipe</option>
                    @foreach (\App\Models\Coa::ACCOUNT_TYPES as $t)
                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                    @endforeach
                </select>

                <select wire:model.live="perPage"
                    class="select select-sm w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}">{{ $n }} / hal</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Petunjuk mode tampilan --}}
        <div
            class="flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            @if ($isTree)
                <span class="icon-[tabler--info-circle] size-4 shrink-0 text-slate-400"></span>
                <span>Klik baris yang memiliki panah untuk membuka atau menutup akun turunannya.</span>
            @else
                <span class="icon-[tabler--filter] size-4 shrink-0 text-slate-400"></span>
                <span>Hasil pencarian / filter ditampilkan sebagai daftar datar.</span>
                <button type="button" wire:click="refreshData"
                    class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">Reset filter</button>
            @endif
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto border-t border-slate-100 dark:border-slate-800" wire:loading.class="opacity-60"
            wire:target="search, levelFilter, typeFilter, perPage, sortBy, gotoPage, nextPage, previousPage, toggle, expandAll, collapseAll">
            <table class="table table-sm">
                <thead
                    class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        @foreach ($kolom as [$label, $field, $kelas])
                            <th @if ($field) wire:click="sortBy('{{ $field }}')" @endif
                                class="{{ $kelas }} {{ $field ? 'cursor-pointer select-none hover:text-emerald-700 dark:hover:text-emerald-400' : '' }}">
                                <div
                                    class="flex items-center gap-1 {{ str_contains($kelas, 'text-center') ? 'justify-center' : '' }}">
                                    {{ $label }}
                                    @if ($field)
                                        @if ($sortField !== $field)
                                            <span class="icon-[tabler--arrows-sort] size-3.5 opacity-40"></span>
                                        @elseif ($sortDirection === 'asc')
                                            <span
                                                class="icon-[tabler--sort-ascending] size-3.5 text-emerald-600"></span>
                                        @else
                                            <span
                                                class="icon-[tabler--sort-descending] size-3.5 text-emerald-600"></span>
                                        @endif
                                    @endif
                                </div>
                            </th>
                        @endforeach

                        @if ($adaAksi)
                            <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($rows as $row)
                        @php
                            $a = $row['model'];
                            $lv = $a->level;
                            [$badgeClass, $badgeText] = $levelBadge[$lv];
                            $klik = $isTree && $row['hasChildren'];
                        @endphp

                        <tr wire:key="coa-{{ $a->id }}"
                            @if ($klik) wire:click="toggle({{ (int) $a->account_number }})" @endif
                            class="group transition-colors hover:bg-emerald-50/50 dark:hover:bg-slate-800/60 {{ $klik ? 'cursor-pointer' : '' }} {{ $isTree && $lv === 'header' ? 'bg-slate-50/60 dark:bg-slate-800/30' : '' }}">

                            {{-- No. (tree: hanya header yang bernomor) --}}
                            <td class="text-center text-xs text-slate-400 dark:text-slate-500">
                                {{ $row['no'] }}
                            </td>

                            {{-- No. Akun + panah --}}
                            <td class="whitespace-nowrap">
                                <div class="{{ $isTree ? $indent[$lv] : '' }}">
                                    <div class="flex items-center gap-1.5">
                                        @if ($isTree)
                                            @if ($row['hasChildren'])
                                                <span
                                                    class="flex size-5 shrink-0 items-center justify-center rounded text-slate-500 transition-colors group-hover:bg-emerald-100 group-hover:text-emerald-700 dark:text-slate-400 dark:group-hover:bg-emerald-950/60">
                                                    <span
                                                        class="icon-[tabler--chevron-right] size-4 transition-transform duration-150 {{ $row['open'] ? 'rotate-90' : '' }}"></span>
                                                </span>
                                            @else
                                                <span class="flex size-5 shrink-0 items-center justify-center">
                                                    <span
                                                        class="size-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                </span>
                                            @endif
                                        @endif
                                        <span
                                            class="font-mono text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{{ $a->account_number }}</span>
                                    </div>

                                    @if ($a->header_code)
                                        <div
                                            class="{{ $isTree ? 'ps-[1.625rem]' : '' }} mt-0.5 text-[11px] text-slate-400">
                                            H: {{ $a->header_code }}@if ($a->reference_code)
                                                · L: {{ $a->reference_code }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div
                                    class="{{ $lv === 'header' ? 'font-semibold' : 'font-medium' }} text-slate-800 dark:text-white">
                                    {{ $a->name }}</div>
                                <div class="line-clamp-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $a->description }}</div>
                            </td>

                            <td>
                                <span
                                    class="badge badge-soft {{ $badgeClass }} badge-sm">{{ $badgeText }}</span>
                            </td>

                            <td class="text-sm capitalize text-slate-600 dark:text-slate-300">
                                {{ $a->account_type ?? '-' }}
                            </td>

                            <td>
                                @if ($a->account_method)
                                    <span
                                        class="badge badge-soft badge-sm {{ $a->account_method === 'debit' ? 'badge-success' : 'badge-error' }}">
                                        {{ ucfirst($a->account_method) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                @if ($a->tbag_1)
                                    {{ collect([$a->tbag_1, $a->tbag_2, $a->tbag_3])->filter()->implode(' › ') }}
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            @if ($adaAksi)
                                {{-- @click.stop: klik Edit/Hapus tidak ikut membuka/menutup baris --}}
                                <td @click.stop>
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($bisaEdit)
                                            <button type="button" wire:click="edit({{ $a->id }})"
                                                wire:loading.attr="disabled" wire:target="edit({{ $a->id }})"
                                                class="btn btn-sm btn-square btn-soft btn-primary" title="Edit akun"
                                                aria-label="Edit akun">
                                                <span wire:loading.remove wire:target="edit({{ $a->id }})"
                                                    class="icon-[tabler--pencil] size-4"></span>
                                                <span wire:loading wire:target="edit({{ $a->id }})"
                                                    class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                            </button>
                                        @endif
                                        @if ($bisaHapus)
                                            <button type="button"
                                                @click="deleteId = {{ $a->id }}; deleteName = @js($a->account_number . ' - ' . $a->name); deleteOpen = true"
                                                class="btn btn-sm btn-square btn-soft btn-error" title="Hapus akun"
                                                aria-label="Hapus akun">
                                                <span class="icon-[tabler--trash] size-4"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}"
                                    class="p-12 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2.5">
                                        <div
                                            class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                            <span
                                                class="icon-[tabler--folder-x] size-7 text-slate-400 dark:text-slate-500"></span>
                                        </div>
                                        <p class="text-base font-semibold text-slate-700 dark:text-slate-300">
                                            Tidak ada akun ditemukan
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            @if ($adaFilter)
                                                Coba sesuaikan kata kunci atau reset filter.
                                            @elseif ($bisaTambah)
                                                Belum ada akun. Klik Tambah Akun untuk membuat.
                                            @else
                                                Belum ada akun terdaftar.
                                            @endif
                                        </p>
                                        @if ($adaFilter)
                                            <button type="button" wire:click="refreshData"
                                                class="btn btn-sm btn-outline btn-success mt-2">Reset Filter</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer & Pagination --}}
            <div
                class="flex flex-col gap-3 border-t border-slate-100 p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Menampilkan
                    <span class="font-medium text-slate-700 dark:text-slate-200">{{ $accounts->firstItem() ?? 0 }}</span>
                    sampai
                    <span class="font-medium text-slate-700 dark:text-slate-200">{{ $accounts->lastItem() ?? 0 }}</span>
                    dari
                    <span
                        class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($accounts->total()) }}</span>
                    {{ $isTree ? 'header' : 'akun' }}
                </p>
                <div>{{ $accounts->links() }}</div>
            </div>
        </div>

        {{-- ============ MODAL DOWNLOAD COA ============ --}}
        @if ($bisaDownload)
            <div x-show="downloadOpen" x-cloak x-transition.opacity.duration.150ms
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
                @click.self="downloadOpen = false">
                <div
                    class="relative flex max-h-[90vh] w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">

                    {{-- Header modal --}}
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 p-5 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                                <span class="icon-[tabler--download] size-5"></span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold leading-tight text-slate-800 dark:text-white">Download COA
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Format CSV, bisa dibuka di Excel</p>
                            </div>
                        </div>
                        <button type="button" @click="downloadOpen = false" class="btn btn-sm btn-circle btn-text"
                            aria-label="Tutup">
                            <span class="icon-[tabler--x] size-4"></span>
                        </button>
                    </div>

                    {{-- Isi modal --}}
                    <div class="space-y-6 overflow-y-auto p-5">

                        {{-- 1. Company --}}
                        <section>
                            <div class="mb-2.5 flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Company</h4>
                                <div class="flex items-center gap-3 text-xs">
                                    <button type="button" wire:click="selectAllDownloadCompanies"
                                        class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">Pilih
                                        semua</button>
                                    <button type="button" wire:click="clearDownloadCompanies"
                                        class="text-slate-500 hover:underline dark:text-slate-400">Kosongkan</button>
                                </div>
                            </div>
                            <div class="flex max-h-32 flex-wrap gap-2 overflow-y-auto">
                                @foreach ($dlCompanyOptions as $c)
                                    <label wire:key="dl-company-{{ $c }}" class="cursor-pointer">
                                        <input type="checkbox" value="{{ $c }}" wire:model="dl_companies"
                                            class="peer sr-only">
                                        <span class="{{ $chip }}">{{ $c }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('dl_companies')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('dl_companies.*')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </section>

                        {{-- 2. Cakupan data --}}
                        <section
                            class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 dark:border-slate-700 dark:bg-slate-800/40">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Download semua data
                                </h4>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    Abaikan filter nomor akun dan tipe, ambil seluruh akun pada company terpilih.
                                </p>
                            </div>
                            <input type="checkbox" wire:model.live="dl_all" class="toggle toggle-success shrink-0"
                                aria-label="Download semua data">
                        </section>

                        {{-- 3. Filter (nonaktif kalau download semua) --}}
                        <div @class([
                            'space-y-6 transition-opacity',
                            'pointer-events-none select-none opacity-40' => $dl_all,
                        ])>
                            <section>
                                <h4 class="mb-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200">Nomor Akun</h4>
                                <div class="flex items-center gap-2">
                                    <input type="text" inputmode="numeric" maxlength="4" wire:model="dl_from"
                                        placeholder="Dari, mis. 1001" @disabled($dl_all)
                                        class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                                    <span class="icon-[tabler--arrow-right] size-4 shrink-0 text-slate-400"></span>
                                    <input type="text" inputmode="numeric" maxlength="4" wire:model="dl_to"
                                        placeholder="Sampai, mis. 1999" @disabled($dl_all)
                                        class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                                </div>
                                <p class="mt-1.5 text-xs text-slate-400">
                                    Isi nomor header (4 angka); ledger dan subledger di bawahnya ikut terunduh.
                                    Kosongkan untuk semua nomor.
                                </p>
                                @error('dl_from')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error('dl_to')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </section>

                            <section>
                                <h4 class="mb-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200">Tipe Akun</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (\App\Models\Coa::ACCOUNT_TYPES as $t)
                                        <label wire:key="dl-type-{{ $t }}" class="cursor-pointer">
                                            <input type="checkbox" value="{{ $t }}" wire:model="dl_types"
                                                @disabled($dl_all) class="peer sr-only">
                                            <span class="{{ $chip }} capitalize">{{ $t }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1.5 text-xs text-slate-400">Tidak ada yang dipilih = semua tipe.</p>
                                @error('dl_types.*')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </section>
                        </div>
                    </div>

                    {{-- Footer modal --}}
                    <div
                        class="flex items-center justify-between gap-3 border-t border-slate-100 p-4 dark:border-slate-800">
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ count($dl_companies) }} company dipilih
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="downloadOpen = false"
                                class="btn btn-sm btn-outline btn-secondary">Batal</button>
                            <button type="button" wire:click="download" wire:loading.attr="disabled"
                                wire:target="download" class="btn btn-sm btn-success gap-1.5">
                                <span wire:loading.remove wire:target="download" class="flex items-center gap-1.5">
                                    <span class="icon-[tabler--download] size-4"></span>
                                    Download
                                </span>
                                <span wire:loading wire:target="download" class="flex items-center gap-1.5">
                                    <span class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                    Menyiapkan...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============ MODAL KONFIRMASI HAPUS ============ --}}
        @if ($bisaHapus)
            <div x-show="deleteOpen" x-cloak x-transition.opacity.duration.150ms
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
                @click.self="deleteOpen = false">
                <div
                    class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400">
                            <span class="icon-[tabler--alert-triangle] size-6"></span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Hapus Akun</h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                Apakah Anda yakin ingin menghapus akun
                                <span class="font-semibold text-slate-800 dark:text-white"
                                    x-text="'&quot;' + deleteName + '&quot;'"></span>?
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button type="button" @click="deleteOpen = false"
                            class="btn btn-sm btn-outline btn-secondary">Batal</button>
                        <button type="button" @click="$wire.delete(deleteId)"
                            class="btn btn-sm flex items-center gap-1.5 bg-red-600 text-white hover:bg-red-700"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="delete" class="flex items-center gap-1.5">
                                <span class="icon-[tabler--trash] size-4"></span>
                                Ya, Hapus
                            </span>
                            <span wire:loading wire:target="delete" class="flex items-center gap-1.5">
                                <span class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                Menghapus...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
