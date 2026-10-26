<div>
    @php
        $tabs = [
            'group1' => [
                'label' => 'Group 1',
                'hint' => 'Kelompok akun utama dan tipe akun',
                'icon' => 'icon-[tabler--layers-subtract]',
            ],
            'group2' => [
                'label' => 'Group 2',
                'hint' => 'Sub-kelompok di bawah Group 1',
                'icon' => 'icon-[tabler--layers-intersect]',
            ],
            'group3' => [
                'label' => 'Group 3',
                'hint' => 'Detail akun lengkap dengan induk Group 2 dan Group 1',
                'icon' => 'icon-[tabler--layers-union]',
            ],
        ];

        $sortIcon = function (string $field) use ($sortField, $sortDirection) {
            if ($sortField !== $field) {
                return 'icon-[tabler--arrows-sort] opacity-30';
            }
            return $sortDirection === 'asc' ? 'icon-[tabler--chevron-up]' : 'icon-[tabler--chevron-down]';
        };

        $hasFilter = $search !== '' || $filterCompany !== '' || $filterType !== '';
        $colspan = match ($tab) {
            'group3' => 9,
            'group2' => 8,
            default => 7,
        };
    @endphp

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                Trial Balance
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Daftar kelompok akun trial balance dari Group 1 sampai Group 3
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="refreshData"
                class="btn btn-outline btn-success flex items-center gap-1.5 shadow-xs">
                <span wire:loading.remove wire:target="refreshData" class="icon-[tabler--refresh] size-4"></span>
                <span wire:loading wire:target="refreshData" class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                Refresh
            </button>

            @if ($tab === 'group3')
                <button type="button" wire:click="create" class="btn btn-success flex items-center gap-1.5 shadow-xs">
                    <span class="icon-[tabler--plus] size-4"></span>
                    Tambah Group 3
                </button>
            @endif
        </div>
    </div>

    {{-- RINGKASAN HIERARKI --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach ($tabs as $key => $meta)
            <button type="button" wire:click="setTab('{{ $key }}')" wire:key="stat-{{ $key }}"
                class="group relative overflow-hidden rounded-2xl border p-5 text-left transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500
                    {{ $tab === $key
                        ? 'border-emerald-300 bg-gradient-to-br from-emerald-50 to-white shadow-md shadow-emerald-100 dark:border-emerald-500/40 dark:from-emerald-500/10 dark:to-slate-900 dark:shadow-none'
                        : 'border-slate-200 bg-white hover:border-emerald-200 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-500/30' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $meta['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-800 dark:text-white">
                            {{ number_format($counts[$key]) }}
                        </p>
                        <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $meta['hint'] }}</p>
                    </div>
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl transition-colors
                        {{ $tab === $key
                            ? 'bg-emerald-600 text-white dark:bg-emerald-500'
                            : 'bg-emerald-100 text-emerald-700 group-hover:bg-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400' }}">
                        <span class="{{ $meta['icon'] }} size-6"></span>
                    </div>
                </div>
                @if ($tab === $key)
                    <span class="absolute inset-x-0 bottom-0 h-1 bg-emerald-500"></span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- KARTU GRID DATA --}}
    <div
        class="card card-border relative overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

        {{-- Indikator loading --}}
        <div wire:loading.delay.shorter class="absolute inset-x-0 top-0 z-10 h-0.5 animate-pulse bg-emerald-500"></div>

        {{-- Tabs --}}
        <div class="flex gap-1 overflow-x-auto border-b border-slate-100 px-4 pt-3 dark:border-slate-800"
            role="tablist">
            @foreach ($tabs as $key => $meta)
                <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    wire:click="setTab('{{ $key }}')" wire:key="tab-{{ $key }}"
                    class="relative -mb-px flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500
                        {{ $tab === $key
                            ? 'border-emerald-600 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-500/10 dark:text-emerald-400'
                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200' }}">
                    <span class="{{ $meta['icon'] }} size-4"></span>
                    Trial Balance {{ $meta['label'] }}
                    <span
                        class="rounded-full px-2 py-0.5 text-xs tabular-nums
                        {{ $tab === $key
                            ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-slate-900'
                            : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ number_format($counts[$key]) }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-col gap-4 p-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">
                    Trial Balance {{ $tabs[$tab]['label'] }}
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $rows->total() }} data ditemukan
                    @if ($tab !== 'group1')
                        &middot; urut dari induk Group 1
                    @endif
                </p>
            </div>

            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative w-full lg:max-w-xs lg:flex-1">
                    <span
                        class="icon-[tabler--search] pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Cari kode atau deskripsi..."
                        class="input input-sm w-full bg-white pl-9 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
                    @if ($companies->count() > 1)
                        <select wire:model.live="filterCompany"
                            class="select select-sm w-full bg-white sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            <option value="">Semua Perusahaan</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company }}">{{ $company }}</option>
                            @endforeach
                        </select>
                    @endif

                    <select wire:model.live="filterType"
                        class="select select-sm w-full bg-white sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="">Semua Tipe Akun</option>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="perPage"
                        class="select select-sm w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="10">10 / hal</option>
                        <option value="25">25 / hal</option>
                        <option value="50">50 / hal</option>
                        <option value="100">100 / hal</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto" wire:key="table-{{ $tab }}">
            <table class="table table-sm">
                <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th class="w-12 text-center">No.</th>

                        <th class="cursor-pointer select-none hover:text-emerald-700 dark:hover:text-emerald-400"
                            wire:click="sortBy('company_code')">
                            <div class="flex items-center gap-1">
                                Perusahaan
                                <span class="{{ $sortIcon('company_code') }} size-3.5"></span>
                            </div>
                        </th>

                        <th class="cursor-pointer select-none hover:text-emerald-700 dark:hover:text-emerald-400"
                            wire:click="sortBy('code')">
                            <div class="flex items-center gap-1">
                                Kode {{ $tabs[$tab]['label'] }}
                                <span class="{{ $sortIcon('code') }} size-3.5"></span>
                            </div>
                        </th>

                        <th class="cursor-pointer select-none hover:text-emerald-700 dark:hover:text-emerald-400"
                            wire:click="sortBy('description')">
                            <div class="flex items-center gap-1">
                                Deskripsi
                                <span class="{{ $sortIcon('description') }} size-3.5"></span>
                            </div>
                        </th>

                        @if ($tab === 'group3')
                            <th>Group 2 (Induk)</th>
                        @endif

                        @if ($tab !== 'group1')
                            <th>Group 1 (Induk)</th>
                        @endif

                        <th class="cursor-pointer select-none hover:text-emerald-700 dark:hover:text-emerald-400"
                            wire:click="sortBy('account_type')">
                            <div class="flex items-center gap-1">
                                Tipe Akun
                                <span class="{{ $sortIcon('account_type') }} size-3.5"></span>
                            </div>
                        </th>

                        @if ($tab !== 'group3')
                            <th class="text-center">{{ $tab === 'group1' ? 'Jml Group 2' : 'Jml Group 3' }}</th>
                        @endif

                        <th class="cursor-pointer select-none whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400"
                            wire:click="sortBy('updated_at')">
                            <div class="flex items-center gap-1">
                                Diperbarui
                                <span class="{{ $sortIcon('updated_at') }} size-3.5"></span>
                            </div>
                        </th>

                        @if ($tab === 'group3')
                            <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($rows as $row)
                        <tr wire:key="{{ $tab }}-{{ $row->id }}"
                            class="transition-colors hover:bg-emerald-50/40 dark:hover:bg-slate-800/60">
                            <td class="text-center text-slate-500 dark:text-slate-400">
                                {{ ($rows->currentPage() - 1) * $rows->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                <span
                                    class="badge badge-soft badge-neutral text-xs font-medium">{{ $row->company_code }}</span>
                            </td>

                            <td class="whitespace-nowrap font-semibold text-emerald-700 dark:text-emerald-400">
                                {{ $row->code }}
                            </td>

                            <td class="max-w-xs">
                                <div class="truncate font-medium text-slate-800 dark:text-white"
                                    title="{{ $row->description }}">
                                    {{ $row->description ?: '-' }}
                                </div>
                            </td>

                            @if ($tab === 'group3')
                                <td class="max-w-[14rem]">
                                    @if ($row->g2_code)
                                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                            {{ $row->g2_code }}
                                        </div>
                                        <div class="truncate text-xs text-slate-400"
                                            title="{{ $row->g2_description }}">
                                            {{ $row->g2_description ?: '-' }}
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Belum terhubung</span>
                                    @endif
                                </td>
                            @endif

                            @if ($tab !== 'group1')
                                <td class="max-w-[14rem]">
                                    @if ($row->g1_code)
                                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                            {{ $row->g1_code }}
                                        </div>
                                        <div class="truncate text-xs text-slate-400"
                                            title="{{ $row->g1_description }}">
                                            {{ $row->g1_description ?: '-' }}
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Belum terhubung</span>
                                    @endif
                                </td>
                            @endif

                            <td>
                                @if ($row->account_type)
                                    <span class="badge badge-soft badge-success whitespace-nowrap text-xs font-medium">
                                        {{ $row->account_type }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>

                            @if ($tab !== 'group3')
                                <td class="text-center">
                                    <span
                                        class="inline-flex min-w-8 items-center justify-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        {{ number_format($row->children_count) }}
                                    </span>
                                </td>
                            @endif

                            <td class="whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                {{ $row->updated_at?->format('d M Y, H:i') ?? '-' }}
                            </td>

                            @if ($tab === 'group3')
                                <td>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" wire:click="edit({{ $row->id }})"
                                            wire:loading.attr="disabled" wire:target="edit({{ $row->id }})"
                                            class="btn btn-sm btn-outline btn-primary" title="Edit Group 3"
                                            aria-label="Edit Group 3">
                                            <span class="icon-[tabler--pencil] size-4"></span>
                                        </button>

                                        <button type="button" wire:click="confirmDelete({{ $row->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmDelete({{ $row->id }})"
                                            class="btn btn-sm btn-outline btn-error" title="Hapus Group 3"
                                            aria-label="Hapus Group 3">
                                            <span class="icon-[tabler--trash] size-4"></span>
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
                                        class="flex size-14 items-center justify-center rounded-full bg-emerald-50 dark:bg-slate-800">
                                        <span
                                            class="icon-[tabler--folder-x] size-7 text-emerald-500 dark:text-emerald-400"></span>
                                    </div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-slate-300">
                                        Data tidak ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @if ($hasFilter)
                                            Ubah kata kunci atau reset filter untuk melihat data lain.
                                        @elseif ($tab === 'group3')
                                            Belum ada data Group 3. Klik Tambah Group 3 untuk membuat.
                                        @else
                                            Belum ada data pada Trial Balance {{ $tabs[$tab]['label'] }}.
                                        @endif
                                    </p>
                                    <div class="mt-2 flex items-center gap-2">
                                        @if ($hasFilter)
                                            <button type="button" wire:click="refreshData"
                                                class="btn btn-sm btn-outline btn-success">
                                                Reset filter
                                            </button>
                                        @endif
                                        @if ($tab === 'group3')
                                            <button type="button" wire:click="create"
                                                class="btn btn-sm btn-success">
                                                Tambah Group 3
                                            </button>
                                        @endif
                                    </div>
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
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $rows->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $rows->lastItem() ?? 0 }}</span>
                dari
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $rows->total() }}</span>
                data
            </p>

            <div>
                {{ $rows->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL FORM TAMBAH / EDIT GROUP 3 --}}
    @if ($isModalOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
            wire:click.self="closeModal" wire:keydown.escape.window="closeModal">
            <div
                class="relative my-8 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">

                {{-- Header --}}
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="icon-[tabler--layers-union] size-6 text-emerald-600 dark:text-emerald-400"></span>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            {{ $selectedId ? 'Edit Group 3' : 'Tambah Group 3' }}
                        </h3>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Tutup"
                        class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <span class="icon-[tabler--x] size-5"></span>
                    </button>
                </div>

                {{-- Form --}}
                <form wire:submit.prevent="save">
                    <div class="grid max-h-[70vh] grid-cols-1 gap-4 overflow-y-auto p-6 sm:grid-cols-2">

                        {{-- Perusahaan --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Perusahaan <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="company_code"
                                class="select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('company_code') border-red-500 @enderror">
                                <option value="">-- Pilih Perusahaan --</option>
                                @foreach ($formCompanies as $comp)
                                    <option value="{{ $comp }}">{{ $comp }}</option>
                                @endforeach
                            </select>
                            @error('company_code')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Group 2 induk --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Group 2 (Induk) <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="tbag_2" @disabled($company_code === '')
                                class="select select-sm w-full bg-white disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('tbag_2') border-red-500 @enderror">
                                <option value="">
                                    {{ $company_code === '' ? 'Pilih perusahaan dulu' : '-- Pilih Group 2 --' }}
                                </option>
                                @foreach ($group2Options as $g1Code => $items)
                                    <optgroup
                                        label="Group 1: {{ $g1Code ?: 'Tanpa induk' }}{{ $items->first()->g1_description ? ' - ' . $items->first()->g1_description : '' }}">
                                        @foreach ($items as $opt)
                                            <option value="{{ $opt->id }}">
                                                {{ $opt->code }}{{ $opt->description ? ' - ' . $opt->description : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('tbag_2')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Kode --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Kode Group 3 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="code" placeholder="Contoh: 1101001" maxlength="100"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('code') border-red-500 @enderror">
                            @error('code')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Deskripsi --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Deskripsi
                            </label>
                            <input type="text" wire:model="description" placeholder="Contoh: Kas Kecil"
                                maxlength="150"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('description') border-red-500 @enderror">
                            @error('description')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div
                        class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <button type="button" wire:click="closeModal"
                            class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5"
                            wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                                <span class="icon-[tabler--device-floppy] size-4"></span>
                                {{ $selectedId ? 'Simpan perubahan' : 'Simpan Group 3' }}
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

    {{-- MODAL KONFIRMASI HAPUS --}}
    @if ($isDeleteModalOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
            wire:click.self="closeDeleteModal" wire:keydown.escape.window="closeDeleteModal">
            <div
                class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400">
                        <span class="icon-[tabler--alert-triangle] size-6"></span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            Hapus Group 3
                        </h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Yakin ingin menghapus
                            <span class="font-semibold text-slate-800 dark:text-white">"{{ $deleteName }}"</span>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" wire:click="closeDeleteModal"
                        class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">
                        Batal
                    </button>
                    <button type="button" wire:click="delete"
                        class="btn btn-sm flex items-center gap-1.5 bg-red-600 text-white hover:bg-red-700"
                        wire:loading.attr="disabled" wire:target="delete">
                        <span wire:loading.remove wire:target="delete" class="flex items-center gap-1.5">
                            <span class="icon-[tabler--trash] size-4"></span>
                            Ya, hapus
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
