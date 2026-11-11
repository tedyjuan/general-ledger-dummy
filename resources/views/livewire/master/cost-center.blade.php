<div x-data="{
    deleteOpen: false,
    deleteId: null,
    deleteName: '',
    downloadOpen: false,
}" @delete-close.window="deleteOpen = false" @download-open.window="downloadOpen = true"
    @download-close.window="downloadOpen = false" @keydown.escape.window="deleteOpen = false; downloadOpen = false">
    @php
        $user = auth()->user();
        $bisaTambah = (bool) $user?->izin('cost-center', 'create');
        $bisaEdit = (bool) $user?->izin('cost-center', 'update');
        $bisaHapus = (bool) $user?->izin('cost-center', 'delete');
        $bisaDownload = (bool) $user?->izin('cost-center', 'read'); // ganti 'read' kalau punya aksi khusus 'download'
        $adaAksi = $bisaEdit || $bisaHapus;
        $adaFilter =
            $search !== '' ||
            $areaFilter !== '' ||
            $departmentFilter !== '' ||
            $divisionFilter !== '' ||
            $segmentFilter !== '';
        $colspan = $adaAksi ? 6 : 5;

        // kolom tabel: [label, field sortable|null, class tambahan]
        $kolom = [
            ['No.', null, 'w-12 text-center'],
            ['Cost Center', 'cost_center', ''],
            ['Team', 'name', ''],
            ['Manager', 'manager', ''],
            ['Deskripsi', null, ''],
        ];

        // filter toolbar: [property livewire, key referensi, label, placeholder]
        $filters = [
            ['areaFilter', 'area', 'Area', 'Semua area'],
            ['departmentFilter', 'department', 'Department', 'Semua department'],
            ['divisionFilter', 'division', 'Division', 'Semua division'],
            ['segmentFilter', 'segment', 'Segment', 'Semua segment'],
        ];

        // filter di modal download: [property livewire, key referensi, label]
        $dlFilters = [
            ['dl_area', 'area', 'Area'],
            ['dl_department', 'department', 'Department'],
            ['dl_division', 'division', 'Division'],
            ['dl_segment', 'segment', 'Segment'],
        ];

        // gaya chip pilihan di modal download
        $chip =
            'inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-emerald-400 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 dark:border-slate-700 dark:text-slate-300';

        $selectSm = 'select select-sm w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white';
        $labelCls = 'mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400';
    @endphp

    {{-- ============ HEADER HALAMAN ============ --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-2">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Cost Center</h1>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="icon-[tabler--building] size-3.5"></span>
                    {{ $activeCompany }}
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Kode dan nama disusun otomatis dari Area, Department, Division, dan Segment.
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
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
                    Tambah Cost Center
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

        {{-- Judul kartu + tombol download --}}
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="icon-[tabler--topology-star-3] size-5"></span>
                </div>
                <div>
                    <h2 class="font-semibold leading-tight text-slate-800 dark:text-white">Daftar Cost Center</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        <span
                            class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format($costCenters->total()) }}</span>
                        cost center ditemukan
                    </p>
                </div>
            </div>

            @if ($bisaDownload)
                <button type="button" wire:click="openDownload" wire:loading.attr="disabled" wire:target="openDownload"
                    class="btn btn-sm btn-outline btn-success gap-1.5 self-start sm:self-auto">
                    <span wire:loading.remove wire:target="openDownload" class="icon-[tabler--download] size-4"></span>
                    <span wire:loading wire:target="openDownload"
                        class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                    Download
                </button>
            @endif
        </div>

        {{-- Toolbar: pencarian (baris 1) + filter (baris 2) --}}
        <div
            class="space-y-3 border-t border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/30">

            {{-- Baris 1: pencarian + status filter --}}
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:max-w-md">
                    <span
                        class="icon-[tabler--search] pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Cari kode, nama, manager, deskripsi..."
                        class="input input-sm w-full bg-white ps-9 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>

                @if ($adaFilter)
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 sm:ms-auto">
                        <span class="icon-[tabler--filter] size-4 shrink-0 text-slate-400"></span>
                        <span>Filter aktif</span>
                        <button type="button" wire:click="refreshData"
                            class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">Reset</button>
                    </div>
                @endif
            </div>

            {{-- Baris 2: filter dengan live search (grid rapi, tidak saling menimpa) --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($filters as [$prop, $key, $label, $placeholder])
                    <div class="min-w-0" wire:key="filter-{{ $prop }}">
                        <label class="{{ $labelCls }}">{{ $label }}</label>
                        <x-searchable-select :model="$prop" :options="$ref[$key]" :placeholder="$placeholder" :live="true" />
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto border-t border-slate-100 dark:border-slate-800" wire:loading.class="opacity-60"
            wire:target="search, areaFilter, departmentFilter, divisionFilter, segmentFilter, perPage, sortBy, gotoPage, nextPage, previousPage">
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
                    @forelse ($costCenters as $a)
                        <tr wire:key="cc-{{ $a->id }}"
                            class="group transition-colors hover:bg-emerald-50/50 dark:hover:bg-slate-800/60">

                            <td class="text-center text-xs text-slate-400 dark:text-slate-500">
                                {{ $costCenters->firstItem() + $loop->index }}
                            </td>

                            <td class="whitespace-nowrap">
                                <span
                                    class="font-mono text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{{ $a->cost_center }}</span>
                            </td>

                            <td class="whitespace-nowrap text-sm font-medium text-slate-800 dark:text-white">
                                {{ $a->name }}
                            </td>

                            <td class="whitespace-nowrap text-sm text-slate-700 dark:text-slate-200">
                                {{ $a->manager ?: '-' }}
                            </td>

                            <td class="max-w-xs text-xs text-slate-500 dark:text-slate-400">
                                <div class="line-clamp-1">{{ $a->description ?: '-' }}</div>
                            </td>

                            @if ($adaAksi)
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($bisaEdit)
                                            <button type="button" wire:click="edit({{ $a->id }})"
                                                wire:loading.attr="disabled" wire:target="edit({{ $a->id }})"
                                                class="btn btn-sm btn-square btn-soft btn-primary"
                                                title="Edit cost center" aria-label="Edit cost center">
                                                <span wire:loading.remove wire:target="edit({{ $a->id }})"
                                                    class="icon-[tabler--pencil] size-4"></span>
                                                <span wire:loading wire:target="edit({{ $a->id }})"
                                                    class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                            </button>
                                        @endif
                                        @if ($bisaHapus)
                                            <button type="button"
                                                @click="deleteId = {{ $a->id }}; deleteName = @js($a->cost_center . ' - ' . $a->name); deleteOpen = true"
                                                class="btn btn-sm btn-square btn-soft btn-error"
                                                title="Hapus cost center" aria-label="Hapus cost center">
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
                                        Tidak ada cost center ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @if ($adaFilter)
                                            Coba sesuaikan kata kunci atau reset filter.
                                        @elseif ($bisaTambah)
                                            Belum ada data. Klik Tambah Cost Center untuk membuat.
                                        @else
                                            Belum ada cost center terdaftar.
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

        {{-- Footer: per halaman (kiri), info (tengah), pagination (kanan) --}}
        <div
            class="flex flex-col gap-3 border-t border-slate-100 p-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <label class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    Tampilkan
                    <select wire:model.live="perPage" class="{{ $selectSm }}">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}">{{ $n }}</option>
                        @endforeach
                    </select>
                    per halaman
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <span
                        class="font-medium text-slate-700 dark:text-slate-200">{{ $costCenters->firstItem() ?? 0 }}</span>
                    –
                    <span
                        class="font-medium text-slate-700 dark:text-slate-200">{{ $costCenters->lastItem() ?? 0 }}</span>
                    dari
                    <span
                        class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($costCenters->total()) }}</span>
                </p>
            </div>
            <div class="overflow-x-auto">{{ $costCenters->links() }}</div>
        </div>
    </div>

    {{-- ============ MODAL DOWNLOAD COST CENTER ============ --}}
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
                            <h3 class="text-lg font-bold leading-tight text-slate-800 dark:text-white">Download Cost
                                Center</h3>
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
                        <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
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
                                Abaikan filter di bawah, ambil seluruh cost center pada company terpilih.
                            </p>
                        </div>
                        <input type="checkbox" wire:model.live="dl_all" class="toggle toggle-success shrink-0"
                            aria-label="Download semua data">
                    </section>

                    {{-- 3. Filter (nonaktif kalau download semua) --}}
                    <div @class([
                        'grid grid-cols-1 gap-4 transition-opacity sm:grid-cols-2',
                        'pointer-events-none select-none opacity-40' => $dl_all,
                    ])>
                        @foreach ($dlFilters as [$prop, $key, $label])
                            <section class="min-w-0" wire:key="dl-filter-{{ $prop }}">
                                <h4 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $label }}</h4>
                                <x-searchable-select :model="$prop" :options="$ref[$key]" :placeholder="'Semua ' . strtolower($label)"
                                    :disabled="$dl_all" />
                                @error($prop)
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </section>
                        @endforeach
                    </div>
                </div>

                {{-- Footer modal --}}
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-4 dark:border-slate-800">
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
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Hapus Cost Center</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Apakah Anda yakin ingin menghapus cost center
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
