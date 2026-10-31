<div>
    @php
        $user = auth()->user();
        $bisaTambah = (bool) $user?->izin('depo', 'create');
        $bisaEdit = (bool) $user?->izin('depo', 'update');
        $bisaHapus = (bool) $user?->izin('depo', 'delete');
        $adaAksi = $bisaEdit || $bisaHapus;
        $adaFilter = $search !== '' || $filterStatus !== '';
        $colspan = $adaAksi ? 8 : 7;
    @endphp

    {{-- HEADER HALAMAN --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                Master Depo
                <span class="badge badge-soft badge-success ml-2 align-middle text-xs">{{ $activeCompany }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Kelola data lokasi dan informasi depo operasional perusahaan
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
                <button type="button" wire:click="create" class="btn btn-success flex items-center gap-1.5 shadow-xs">
                    <span class="icon-[tabler--plus] size-4"></span>
                    Tambah Depo
                </button>
            @endif
        </div>
    </div>

    @if (session('toast'))
        <div x-data x-init="$dispatch('toast', { type: '{{ session('toast.type') }}', message: @js(session('toast.message')) })"></div>
    @endif

    {{-- KARTU TABEL DATA DEPO --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">Daftar Lokasi Depo</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total {{ $depos->total() }} lokasi terdaftar pada {{ $activeCompany }}
                </p>
            </div>

            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative w-full lg:max-w-xs lg:flex-1">
                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Cari kode, nama, kota, npwp..."
                        class="input input-sm w-full bg-white pl-3 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
                    <select wire:model.live="filterStatus"
                        class="select select-sm w-full bg-white sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="">ALL</option>
                        <option value="pusat">Pusat</option>
                        <option value="depo">Depo</option>
                    </select>

                    <select wire:model.live="perPage"
                        class="select select-sm w-auto bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="5">5 / hal</option>
                        <option value="10">10 / hal</option>
                        <option value="25">25 / hal</option>
                        <option value="50">50 / hal</option>
                    </select>
                </div>
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
                                Kode Depo
                                @if ($sortField === 'code')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer select-none hover:text-emerald-700" wire:click="sortBy('name')">
                            <div class="flex items-center gap-1">
                                Nama Depo
                                @if ($sortField === 'name')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @endif
                            </div>
                        </th>
                        <th>Alias / Area</th>
                        <th>Status</th>
                        <th>Kota & Telepon</th>
                        <th>Tahun Fiskal</th>
                        @if ($adaAksi)
                            <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($depos as $depo)
                        <tr wire:key="depo-{{ $depo->id }}"
                            class="transition-colors hover:bg-emerald-50/40 dark:hover:bg-slate-800/60">
                            <td class="text-center text-slate-500 dark:text-slate-400">
                                {{ ($depos->currentPage() - 1) * $depos->perPage() + $loop->iteration }}
                            </td>

                            <td class="whitespace-nowrap font-semibold text-emerald-700 dark:text-emerald-400">
                                {{ $depo->code }}
                            </td>

                            <td>
                                <div class="font-medium text-slate-800 dark:text-white">{{ $depo->name }}</div>
                                @if ($depo->npwp)
                                    <span class="text-xs text-slate-400">NPWP: {{ $depo->npwp }}</span>
                                @endif
                            </td>

                            <td class="text-slate-600 dark:text-slate-300">
                                <span class="font-medium text-slate-800 dark:text-white">{{ $depo->alias }}</span>
                                <span class="text-xs text-slate-400">({{ $depo->area_code }})</span>
                            </td>

                            <td>
                                @if ($depo->status_depo === 'pusat')
                                    <span
                                        class="badge badge-soft badge-primary text-xs font-medium uppercase">Pusat</span>
                                @else
                                    <span
                                        class="badge badge-soft badge-success text-xs font-medium uppercase">Depo</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                <div>{{ $depo->city ?? '-' }}</div>
                                <div class="text-slate-400">{{ $depo->phone_no ?? '' }}</div>
                            </td>

                            <td class="text-center text-xs text-slate-600 dark:text-slate-300">
                                {{ $depo->fiscal_year ?? '-' }}
                            </td>

                            @if ($adaAksi)
                                <td>
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if ($bisaEdit)
                                            <button type="button" wire:click="edit({{ $depo->id }})"
                                                wire:loading.attr="disabled" wire:target="edit({{ $depo->id }})"
                                                class="btn btn-sm btn-outline btn-primary" title="Edit Depo"
                                                aria-label="Edit Depo">
                                                <span class="icon-[tabler--pencil] size-4"></span>
                                            </button>
                                        @endif

                                        @if ($bisaHapus)
                                            <button type="button" wire:click="confirmDelete({{ $depo->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="confirmDelete({{ $depo->id }})"
                                                class="btn btn-sm btn-outline btn-error" title="Hapus Depo"
                                                aria-label="Hapus Depo">
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
                                        Tidak ada data depo ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @if ($adaFilter)
                                            Coba sesuaikan kata kunci pencarian atau reset filter.
                                        @elseif ($bisaTambah)
                                            Belum ada data depo terdaftar. Klik tombol Tambah Depo untuk membuat.
                                        @else
                                            Belum ada data depo terdaftar.
                                        @endif
                                    </p>
                                    <div class="mt-2 flex items-center gap-2">
                                        @if ($adaFilter)
                                            <button type="button" wire:click="refreshData"
                                                class="btn btn-sm btn-outline btn-success">
                                                Reset Filter
                                            </button>
                                        @endif
                                        @if ($bisaTambah)
                                            <button type="button" wire:click="create" class="btn btn-sm btn-success">
                                                Tambah Depo Baru
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
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $depos->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $depos->lastItem() ?? 0 }}</span>
                dari
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $depos->total() }}</span>
                depo
            </p>
            <div>{{ $depos->links() }}</div>
        </div>
    </div>

    {{-- MODAL FORM TAMBAH / EDIT DEPO --}}
    @if ($isModalOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
            wire:click.self="closeModal">
            <div
                class="relative my-8 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="icon-[tabler--building-warehouse] size-6 text-emerald-600 dark:text-emerald-400"></span>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            {{ $selectedId ? 'Edit Data Depo' : 'Tambah Depo Baru' }}
                        </h3>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Tutup"
                        class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <span class="icon-[tabler--x] size-5"></span>
                    </button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="grid max-h-[70vh] grid-cols-1 gap-4 overflow-y-auto p-6 sm:grid-cols-2">
                        {{-- Perusahaan (readonly, dari company aktif) --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Perusahaan
                            </label>
                            <input type="text" value="{{ $activeCompany }}" readonly
                                class="input input-sm w-full cursor-not-allowed bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        </div>

                        {{-- Status Depo --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Status / Tipe Depo <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="status_depo"
                                class="select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('status_depo') border-red-500 @enderror">
                                <option value="depo">Depo</option>
                                <option value="pusat">Pusat</option>
                            </select>
                            @error('status_depo')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Kode Depo --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Kode Depo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="code" placeholder="Contoh: 336" maxlength="3"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('code') border-red-500 @enderror">
                            @error('code')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Nama Depo --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Nama Depo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" placeholder="Contoh: Serang"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('name') border-red-500 @enderror">
                            @error('name')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Alias --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Alias / Singkatan <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="alias" placeholder="Contoh: SRG"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('alias') border-red-500 @enderror">
                            @error('alias')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Kode Area --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Kode Area <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="area_code" placeholder="Contoh: 01" maxlength="2"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('area_code') border-red-500 @enderror">
                            @error('area_code')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- NPWP --}}
                        <div class="col-span-1">
                            <label
                                class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">NPWP</label>
                            <input type="text" wire:model="npwp" placeholder="00.000.000.0-000.000"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('npwp') border-red-500 @enderror">
                            @error('npwp')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Telepon --}}
                        <div class="col-span-1">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">No.
                                Telepon</label>
                            <input type="text" wire:model="phone_no" placeholder="Contoh: 0254-123456"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('phone_no') border-red-500 @enderror">
                            @error('phone_no')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Kota --}}
                        <div class="col-span-1">
                            <label
                                class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Kota</label>
                            <input type="text" wire:model="city" placeholder="Contoh: Serang"
                                class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('city') border-red-500 @enderror">
                            @error('city')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Kode Pos & Tahun Fiskal --}}
                        <div class="col-span-1 grid grid-cols-2 gap-2">
                            <div>
                                <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Kode
                                    Pos</label>
                                <input type="text" wire:model="postal_code" placeholder="42111"
                                    class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('postal_code') border-red-500 @enderror">
                                @error('postal_code')
                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Tahun
                                    Fiskal</label>
                                <input type="number" wire:model="fiscal_year" placeholder="2026"
                                    class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('fiscal_year') border-red-500 @enderror">
                                @error('fiscal_year')
                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Alamat --}}
                        <div class="col-span-1 sm:col-span-2">
                            <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Alamat
                                Lengkap</label>
                            <textarea wire:model="address" rows="3" placeholder="Masukkan alamat lengkap depo..."
                                class="textarea textarea-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('address') border-red-500 @enderror"></textarea>
                            @error('address')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <button type="button" wire:click="closeModal"
                            class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">Batal</button>
                        <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                                <span class="icon-[tabler--device-floppy] size-4"></span>
                                {{ $selectedId ? 'Perbarui Depo' : 'Simpan Depo' }}
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
    @if ($bisaHapus && $isDeleteModalOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
            wire:click.self="closeDeleteModal">
            <div
                class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400">
                        <span class="icon-[tabler--alert-triangle] size-6"></span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Hapus Data Depo</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Apakah Anda yakin ingin menghapus depo
                            <span class="font-semibold text-slate-800 dark:text-white">"{{ $deleteName }}"</span>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" wire:click="closeDeleteModal"
                        class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">Batal</button>
                    <button type="button" wire:click="delete"
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
