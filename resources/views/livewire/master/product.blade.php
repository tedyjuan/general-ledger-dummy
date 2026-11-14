<div x-data="{
    deleteOpen: false,
    deleteId: null,
    deleteName: '',
    deleteCount: 0,
}" @delete-close.window="deleteOpen = false" @keydown.escape.window="deleteOpen = false">
    @php
        $user = auth()->user();
        $bisaTambah = (bool) $user?->izin('product', 'create');
        $bisaEdit = (bool) $user?->izin('product', 'update');
        $bisaHapus = (bool) $user?->izin('product', 'delete');
        $adaAksi = $bisaEdit || $bisaHapus;
        $adaFilter = $search !== '';
        $colspan = $adaAksi ? 6 : 5;

        // kolom tabel: [label, field sortable|null, class tambahan]
        $kolom = [
            ['No.', null, 'w-12 text-center'],
            ['Kode Produk', 'product_code', ''],
            ['Nama Produk', 'product_name', ''],
            ['Akun COA', 'coa_code', ''],
            ['Mapping', null, 'text-center'],
        ];
    @endphp

    {{-- ============ HEADER HALAMAN ============ --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Produk & Mapping</h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="icon-[tabler--building] size-3.5"></span>
                    {{ $activeCompany }}
                </span>
                <span>Kelola produk dan aturan akun yang dipakai tiap transaksi</span>
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
                    Tambah Produk
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

        {{-- Judul kartu + kontrol baris --}}
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="icon-[tabler--package] size-5"></span>
                </div>
                <div>
                    <h2 class="font-semibold leading-tight text-slate-800 dark:text-white">Daftar Produk</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        <span
                            class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format($products->total()) }}</span>
                        produk {{ $adaFilter ? 'ditemukan' : 'terdaftar' }}
                    </p>
                </div>
            </div>

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
            </div>
        </div>

        {{-- Toolbar pencarian --}}
        <div
            class="flex flex-col gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/30 lg:flex-row lg:items-center">
            <div class="relative w-full lg:max-w-sm">
                <span
                    class="icon-[tabler--search] pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
                <input type="search" wire:model.live.debounce.400ms="search"
                    placeholder="Cari kode, nama produk, atau akun COA..."
                    class="input input-sm w-full bg-white ps-9 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
            </div>

            <div class="flex flex-wrap items-center gap-2 lg:ms-auto">
                @if ($adaFilter)
                    <button type="button" wire:click="refreshData"
                        class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">Reset
                        pencarian</button>
                @endif
                <select wire:model.live="perPage"
                    class="select select-sm w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}">{{ $n }} / hal</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Petunjuk --}}
        <div
            class="flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            <span class="icon-[tabler--info-circle] size-4 shrink-0 text-slate-400"></span>
            <span>Klik baris yang memiliki panah untuk melihat mapping produknya.</span>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto border-t border-slate-100 dark:border-slate-800" wire:loading.class="opacity-60"
            wire:target="search, perPage, sortBy, gotoPage, nextPage, previousPage, toggle, expandAll, collapseAll">
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
                    @forelse ($products as $i => $p)
                        @php
                            $jml = (int) ($counts[$p->product_code] ?? 0);
                            $open = in_array($p->id, $expanded, true);
                            $klik = $jml > 0;
                            $coaNama = $coaNames->get((int) $p->coa_code);
                        @endphp

                        <tr wire:key="prd-{{ $p->id }}"
                            @if ($klik) wire:click="toggle({{ $p->id }})" @endif
                            class="group transition-colors hover:bg-emerald-50/50 dark:hover:bg-slate-800/60 {{ $klik ? 'cursor-pointer' : '' }}">

                            <td class="text-center text-xs text-slate-400 dark:text-slate-500">
                                {{ ($products->firstItem() ?? 1) + $i }}
                            </td>

                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    @if ($klik)
                                        <span
                                            class="flex size-5 shrink-0 items-center justify-center rounded text-slate-500 transition-colors group-hover:bg-emerald-100 group-hover:text-emerald-700 dark:text-slate-400 dark:group-hover:bg-emerald-950/60">
                                            <span
                                                class="icon-[tabler--chevron-right] size-4 transition-transform duration-150 {{ $open ? 'rotate-90' : '' }}"></span>
                                        </span>
                                    @else
                                        <span class="flex size-5 shrink-0 items-center justify-center">
                                            <span class="size-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                        </span>
                                    @endif
                                    <span
                                        class="font-mono text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{{ $p->product_code }}</span>
                                </div>
                            </td>

                            <td class="font-medium text-slate-800 dark:text-white">{{ $p->product_name }}</td>

                            <td>
                                <div class="font-mono text-sm tabular-nums text-slate-700 dark:text-slate-200">
                                    {{ $p->coa_code }}</div>
                                <div class="line-clamp-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $coaNama ?? '-' }}</div>
                            </td>

                            <td class="text-center">
                                @if ($jml > 0)
                                    <span class="badge badge-soft badge-success badge-sm">{{ $jml }}
                                        mapping</span>
                                @else
                                    <span class="badge badge-soft badge-warning badge-sm">Belum ada</span>
                                @endif
                            </td>

                            @if ($adaAksi)
                                {{-- @click.stop: klik Edit/Hapus tidak ikut membuka/menutup baris --}}
                                <td @click.stop>
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($bisaEdit)
                                            <button type="button" wire:click="edit({{ $p->id }})"
                                                wire:loading.attr="disabled" wire:target="edit({{ $p->id }})"
                                                class="btn btn-sm btn-square btn-soft btn-primary" title="Edit produk"
                                                aria-label="Edit produk">
                                                <span wire:loading.remove wire:target="edit({{ $p->id }})"
                                                    class="icon-[tabler--pencil] size-4"></span>
                                                <span wire:loading wire:target="edit({{ $p->id }})"
                                                    class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                            </button>
                                        @endif
                                        @if ($bisaHapus)
                                            <button type="button"
                                                @click="deleteId = {{ $p->id }}; deleteName = @js($p->product_code . ' - ' . $p->product_name); deleteCount = {{ $jml }}; deleteOpen = true"
                                                class="btn btn-sm btn-square btn-soft btn-error" title="Hapus produk"
                                                aria-label="Hapus produk">
                                                <span class="icon-[tabler--trash] size-4"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>

                        {{-- Detail mapping --}}
                        @if ($open)
                            @php $detail = $detailMapping->get($p->product_code, collect()); @endphp
                            <tr wire:key="prd-{{ $p->id }}-detail"
                                class="bg-slate-50/60 dark:bg-slate-800/30">
                                <td colspan="{{ $colspan }}" class="p-0">
                                    <div class="overflow-x-auto px-4 py-3 sm:ps-14">
                                        <table class="table table-xs">
                                            <thead
                                                class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                                <tr>
                                                    <th>Tipe Transaksi</th>
                                                    <th>Tipe Operasional</th>
                                                    <th>DO Type</th>
                                                    <th>Cost Center</th>
                                                    <th>Akun COA</th>
                                                    <th class="text-center">D / K</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                                @foreach ($detail as $m)
                                                    <tr wire:key="pm-{{ $m->id }}">
                                                        <td class="text-slate-700 dark:text-slate-200">
                                                            {{ \App\Models\ProductMapped::TRANSACTION_TYPES[$m->transaction_type] ?? $m->transaction_type }}
                                                        </td>
                                                        <td class="text-slate-700 dark:text-slate-200">
                                                            {{ \App\Models\ProductMapped::OPERATIONAL_TYPES[$m->operational_type] ?? $m->operational_type }}
                                                        </td>
                                                        <td class="text-slate-700 dark:text-slate-200">
                                                            {{ \App\Models\ProductMapped::DO_TYPES[$m->do_type] ?? $m->do_type }}
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="font-mono text-slate-700 dark:text-slate-200">{{ $m->cost_center_code }}</span>
                                                            <span
                                                                class="text-slate-500 dark:text-slate-400">{{ $ccNames->get($m->cost_center_code) }}</span>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="font-mono font-semibold text-emerald-700 dark:text-emerald-400">{{ $m->coa_code }}</span>
                                                            <span
                                                                class="text-slate-500 dark:text-slate-400">{{ $coaNames->get((int) $m->coa_code) }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span
                                                                class="badge badge-soft badge-xs {{ $m->kind === 'debit' ? 'badge-success' : 'badge-error' }}">{{ $m->kind === 'debit' ? 'Debit' : 'Kredit' }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ $colspan }}"
                                class="p-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2.5">
                                    <div
                                        class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                        <span
                                            class="icon-[tabler--package-off] size-7 text-slate-400 dark:text-slate-500"></span>
                                    </div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-slate-300">
                                        Tidak ada produk ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @if ($adaFilter)
                                            Coba ganti kata kunci atau reset pencarian.
                                        @elseif ($bisaTambah)
                                            Belum ada produk. Klik Tambah Produk untuk membuat.
                                        @else
                                            Belum ada produk terdaftar.
                                        @endif
                                    </p>
                                    @if ($adaFilter)
                                        <button type="button" wire:click="refreshData"
                                            class="btn btn-sm btn-outline btn-success mt-2">Reset Pencarian</button>
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
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $products->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $products->lastItem() ?? 0 }}</span>
                dari
                <span
                    class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($products->total()) }}</span>
                produk
            </p>
            <div>{{ $products->links() }}</div>
        </div>
    </div>

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
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Hapus Produk</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Apakah Anda yakin ingin menghapus produk
                            <span class="font-semibold text-slate-800 dark:text-white"
                                x-text="'&quot;' + deleteName + '&quot;'"></span>?
                        </p>
                        <p x-show="deleteCount > 0" class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">
                            <span x-text="deleteCount"></span> mapping milik produk ini juga akan ikut terhapus.
                        </p>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Tindakan ini tidak dapat dibatalkan.
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
