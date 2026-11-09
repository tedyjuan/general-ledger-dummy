{{-- Alpine hanya dipakai untuk modal hapus --}}
<div x-data="{
    deleteOpen: false,
    deleteId: null,
    deleteName: '',
}" @delete-close.window="deleteOpen = false" @keydown.escape.window="deleteOpen = false">
    @php
        $user = auth()->user();
        $bisaTambah = (bool) $user?->izin('journal-sources', 'create');
        $bisaEdit = (bool) $user?->izin('journal-sources', 'update');
        $bisaHapus = (bool) $user?->izin('journal-sources', 'delete');
        $adaAksi = $bisaEdit || $bisaHapus;
        // sesudah
        $adaFilter = $search !== '' || $filterCode !== '' || $filterDepo !== '' || $filterDescription !== '';
        $colspan = $adaAksi ? 5 : 4;
    @endphp

    {{-- HEADER HALAMAN --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                Master Journal Source
                <span class="badge badge-soft badge-success ml-2 align-middle text-xs">{{ $activeCompany }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Kelola data sumber jurnal (Journal Source) perusahaan
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="refreshData"
                class="btn btn-outline btn-success flex items-center gap-1.5 shadow-xs">
                <span wire:loading.remove wire:target="refreshData" class="icon-[tabler--refresh] size-4"></span>
                <span wire:loading wire:target="refreshData" class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                Refresh
            </button>
            @if ($bisaTambah)
                <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create"
                    class="btn btn-success flex items-center gap-1.5 shadow-xs">
                    <span wire:loading.remove wire:target="create" class="icon-[tabler--plus] size-4"></span>
                    <span wire:loading wire:target="create" class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                    Tambah Journal Source
                </button>
            @endif
        </div>
    </div>

    @if (session('toast'))
        <div x-data x-init="$dispatch('toast', { type: '{{ session('toast.type') }}', message: @js(session('toast.message')) })"></div>
    @endif

    {{-- KARTU TABEL --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">Daftar Journal Source</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total {{ $sources->total() }} journal source terdaftar pada {{ $activeCompany }}
                </p>
            </div>

            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative w-full lg:max-w-xs lg:flex-1">
                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Cari kode, depo, deskripsi..."
                        class="input input-sm w-full bg-white pl-3 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>

                <select wire:model.live="perPage"
                    class="select select-sm w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="5">5 / hal</option>
                    <option value="10">10 / hal</option>
                    <option value="25">25 / hal</option>
                    <option value="50">50 / hal</option>
                </select>

            </div>
        </div>

        {{-- Tabel Data --}}
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th class="w-12 text-center">No.</th>
                        <th class="cursor-pointer select-none hover:text-emerald-700" wire:click="sortBy('code')">
                            <div class="flex items-center gap-1">
                                Kode
                                @if ($sortField === 'code')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer select-none hover:text-emerald-700" wire:click="sortBy('depo_code')">
                            <div class="flex items-center gap-1">
                                Kode Depo
                                @if ($sortField === 'depo_code')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer select-none hover:text-emerald-700"
                            wire:click="sortBy('description')">
                            <div class="flex items-center gap-1">
                                Deskripsi
                                @if ($sortField === 'description')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @endif
                            </div>
                        </th>
                        @if ($adaAksi)
                            <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($sources as $source)
                        <tr wire:key="journal-source-{{ $source->id }}"
                            class="transition-colors hover:bg-emerald-50/40 dark:hover:bg-slate-800/60">
                            <td class="text-center text-slate-500 dark:text-slate-400">
                                {{ ($sources->currentPage() - 1) * $sources->perPage() + $loop->iteration }}
                            </td>

                            <td class="whitespace-nowrap font-semibold text-emerald-700 dark:text-emerald-400">
                                {{ $source->code }}
                            </td>

                            <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ $source->depo_code }}
                            </td>

                            <td>
                                <div class="font-medium text-slate-800 dark:text-white">{{ $source->description }}
                                </div>
                            </td>

                            @if ($adaAksi)
                                <td>
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if ($bisaEdit)
                                            <button type="button" wire:click="edit({{ $source->id }})"
                                                wire:loading.attr="disabled" wire:target="edit({{ $source->id }})"
                                                class="btn btn-sm btn-outline btn-primary" title="Edit Journal Source"
                                                aria-label="Edit Journal Source">
                                                <span wire:loading.remove wire:target="edit({{ $source->id }})"
                                                    class="icon-[tabler--pencil] size-4"></span>
                                                <span wire:loading wire:target="edit({{ $source->id }})"
                                                    class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                            </button>
                                        @endif
                                        @if ($bisaHapus)
                                            <button type="button"
                                                @click="deleteId = {{ $source->id }}; deleteName = @js($source->code); deleteOpen = true"
                                                class="btn btn-sm btn-outline btn-error" title="Hapus Journal Source"
                                                aria-label="Hapus Journal Source">
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
                                class="p-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2.5">
                                    <div
                                        class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                        <span
                                            class="icon-[tabler--folder-x] size-7 text-slate-400 dark:text-slate-500"></span>
                                    </div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-slate-300">
                                        Tidak ada data Journal Source ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @if ($adaFilter)
                                            Coba sesuaikan kata kunci pencarian atau reset filter.
                                        @elseif ($bisaTambah)
                                            Belum ada data terdaftar. Klik tombol Tambah Journal Source untuk membuat.
                                        @else
                                            Belum ada data Journal Source terdaftar.
                                        @endif
                                    </p>
                                    @if ($adaFilter)
                                        <button type="button" wire:click="refreshData"
                                            class="btn btn-sm btn-outline btn-success mt-2">
                                            Reset Filter
                                        </button>
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
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $sources->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $sources->lastItem() ?? 0 }}</span>
                dari
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $sources->total() }}</span>
                Journal Source
            </p>

            @if ($sources->hasPages())
                @php
                    $hlmNow = $sources->currentPage();
                    $hlmLast = $sources->lastPage();
                    $hlmName = $sources->getPageName();

                    // halaman kunci: 2 awal, halaman aktif, 2 akhir
                    $hlmKunci = collect([1, 2, $hlmNow, $hlmLast - 1, $hlmLast])
                        ->filter(fn($n) => $n >= 1 && $n <= $hlmLast)
                        ->unique()
                        ->sort()
                        ->values();

                    // sisipkan "..." untuk celah; celah 1 halaman langsung ditampilkan angkanya
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

                    $btnBase =
                        'inline-flex size-9 select-none items-center justify-center rounded-xl border text-sm font-semibold transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 active:scale-95 disabled:pointer-events-none disabled:opacity-35';

                    $btnNormal =
                        'border-transparent text-slate-600 hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 hover:shadow-md hover:shadow-emerald-500/10 dark:text-slate-300 dark:hover:border-emerald-500/30 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-300';

                    $btnAktif =
                        '-translate-y-0.5 border-transparent bg-linear-to-br from-emerald-500 via-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/40';

                    $btnPanah =
                        'border-slate-200 bg-white text-slate-500 shadow-xs hover:border-emerald-200 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-emerald-300';
                @endphp

                <nav class="flex items-center gap-1.5" aria-label="Pagination">
                    <button type="button" wire:click="previousPage('{{ $hlmName }}')"
                        @disabled($sources->onFirstPage()) class="{{ $btnBase }} {{ $btnPanah }}"
                        aria-label="Sebelumnya">
                        <span class="icon-[tabler--chevron-left] size-4"></span>
                    </button>

                    @foreach ($hlmItem as $k => $n)
                        @if ($n === '...')
                            <span wire:key="dots-{{ $k }}"
                                class="inline-flex size-9 select-none items-center justify-center font-bold tracking-widest text-slate-300 dark:text-slate-600">…</span>
                        @else
                            <button type="button" wire:key="page-{{ $n }}"
                                wire:click="gotoPage({{ $n }}, '{{ $hlmName }}')"
                                @if ($n === $hlmNow) aria-current="page" @endif
                                class="{{ $btnBase }} {{ $n === $hlmNow ? $btnAktif : $btnNormal }}">
                                {{ $n }}
                            </button>
                        @endif
                    @endforeach

                    <button type="button" wire:click="nextPage('{{ $hlmName }}')" @disabled(!$sources->hasMorePages())
                        class="{{ $btnBase }} {{ $btnPanah }}" aria-label="Berikutnya">
                        <span class="icon-[tabler--chevron-right] size-4"></span>
                    </button>
                </nav>
            @endif
        </div>
    </div>

    {{-- MODAL FORM TAMBAH / EDIT (dikendalikan Livewire) --}}
    @if ($showForm)
        <div wire:key="journal-source-form-{{ $selectedId ?? 'new' }}"
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm"
            wire:click.self="closeModal" @keydown.escape.window="$wire.closeModal()">
            <div
                class="relative my-8 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="icon-[tabler--book-2] size-6 text-emerald-600 dark:text-emerald-400"></span>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            {{ $selectedId ? 'Edit Journal Source' : 'Tambah Journal Source Baru' }}
                            <span
                                class="badge badge-soft badge-success ml-2 align-middle text-xs">{{ $activeCompany }}</span>
                        </h3>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Tutup"
                        class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <span class="icon-[tabler--x] size-5"></span>
                    </button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="flex max-h-[70vh] flex-col gap-5 overflow-y-auto p-6">

                        {{-- Depo: opsi dari Livewire, DOM dibuat baru setiap modal dibuka --}}
                        <div class="form-control w-full">
                            <label class="label" for="depo-select">
                                <span class="label-text font-semibold text-slate-700 dark:text-slate-200">
                                    Depo <span class="text-error">*</span>
                                </span>
                            </label>

                            {{-- wire:ignore: DOM select dikelola FlyonUI, bukan Livewire.
                                 destroy() membersihkan instance + menu di <body> saat modal ditutup. --}}
                            <div wire:ignore x-data="{
                                destroy() {
                                    try { window.HSSelect?.getInstance('#depo-select')?.destroy(); } catch (e) {}
                                }
                            }" x-init="$nextTick(() => window.HSStaticMethods?.autoInit(['select']))">
                                <select id="depo-select" wire:model="depo_code" class="hidden"
                                    data-select='{
                                        "dropdownScope": "window",
                                        "dropdownAutoPlacement": true,
                                        "dropdownSpace": 4,
                                        "dropdownClasses": "advance-select-menu w-full z-[200] max-h-52 pt-0 overflow-y-auto shadow-lg",
                                        "hasSearch": true,
                                        "searchPlaceholder": "Cari kode / nama depo...",
                                        "searchNoResultText": "Depo tidak ditemukan",
                                        "placeholder": "-- Pilih Depo --",
                                        "toggleTag": "<button type=\"button\" aria-expanded=\"false\"></button>",
                                        "toggleClasses": "advance-select-toggle select-disabled:pointer-events-none select-disabled:opacity-40 bg-white dark:bg-slate-900 dark:border-slate-700",
                                        "optionClasses": "advance-select-option selected:select-active",
                                        "optionTemplate": "<div class=\"flex justify-between items-center w-full\"><span data-title></span><span class=\"icon-[tabler--check] shrink-0 size-4 text-primary hidden selected:block\"></span></div>",
                                        "extraMarkup": "<span class=\"icon-[tabler--caret-up-down] shrink-0 size-4 text-base-content absolute top-1/2 end-3 -translate-y-1/2\"></span>"
                                    }'>
                                    <option value="">-- Pilih Depo --</option>
                                    @foreach ($depos as $depo)
                                        <option value="{{ $depo->code }}" @selected($depo_code === $depo->code)>
                                            {{ $depo->code }} - {{ $depo->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @error('depo_code')
                                <label class="label pb-0">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        {{-- Grid untuk Kode & Deskripsi --}}
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">

                            {{-- Kode Journal Source --}}
                            <div class="form-control w-full sm:col-span-1">
                                <label class="label" for="code">
                                    <span class="label-text font-semibold text-slate-700 dark:text-slate-200">
                                        Kode <span class="text-error">*</span>
                                    </span>
                                </label>
                                <input type="text" id="code" wire:model="code" placeholder="Contoh: JU"
                                    maxlength="2" minlength="2" autocomplete="off"
                                    class="input input-sm w-full bg-white uppercase dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('code') border-error focus:border-error @enderror">

                                @error('code')
                                    <label class="label pb-0">
                                        <span class="label-text-alt text-error">{{ $message }}</span>
                                    </label>
                                @enderror
                            </div>

                            {{-- Deskripsi --}}
                            <div class="form-control w-full sm:col-span-2">
                                <label class="label" for="description">
                                    <span class="label-text font-semibold text-slate-700 dark:text-slate-200">
                                        Deskripsi <span class="text-error">*</span>
                                    </span>
                                </label>
                                <input type="text" id="description" wire:model="description"
                                    placeholder="Contoh: Jurnal Umum" maxlength="255"
                                    class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('description') border-error focus:border-error @enderror">

                                @error('description')
                                    <label class="label pb-0">
                                        <span class="label-text-alt text-error">{{ $message }}</span>
                                    </label>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div
                        class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/80 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <button type="button" wire:click="closeModal" class="btn btn-sm btn-soft btn-secondary">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5 shadow-sm"
                            wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                                <span class="icon-[tabler--device-floppy] size-4"></span>
                                {{ $selectedId ? 'Perbarui' : 'Simpan' }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                                <span class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL KONFIRMASI HAPUS (tetap Alpine) --}}
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
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Hapus Journal Source</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Apakah Anda yakin ingin menghapus Journal Source
                            <span class="font-semibold text-slate-800 dark:text-white"
                                x-text="'&quot;' + deleteName + '&quot;'"></span>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" @click="deleteOpen = false"
                        class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">Batal</button>
                    <button type="button" @click="$wire.delete(deleteId)"
                        class="btn btn-sm flex items-center gap-1.5 bg-red-600 text-white hover:bg-red-700"
                        wire:loading.attr="disabled" wire:target="delete">
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
