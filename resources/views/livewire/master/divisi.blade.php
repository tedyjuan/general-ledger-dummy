<div x-data="{
    modalOpen: false,
    deleteOpen: false,
    deleteId: null,
    deleteName: '',
    closeForm() {
        this.modalOpen = false;
        this.$wire.closeModal();
    }
}" @modal-close.window="modalOpen = false" @delete-close.window="deleteOpen = false"
    @keydown.escape.window="modalOpen && closeForm(); deleteOpen = false">
    {{-- HEADER HALAMAN (Langsung di dalam Livewire root agar tombol wire:click berfungsi) --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                Master Divisi
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Kelola data lokasi dan informasi Divisi operasional perusahaan
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="refreshData"
                class="btn btn-outline btn-success flex items-center gap-1.5 shadow-xs">
                <span wire:loading.remove wire:target="refreshData" class="icon-[tabler--refresh] size-4"></span>
                <span wire:loading wire:target="refreshData" class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                Refresh
            </button>

            <button type="button" @click="modalOpen=true" class="btn btn-success flex items-center gap-1.5 shadow-xs">
                <span class="icon-[tabler--plus] size-4"></span>
                Tambah Divisi
            </button>
        </div>
    </div>

    @if (session('toast'))
        <div x-data x-init="$dispatch('toast', { type: '{{ session('toast.type') }}', message: @js(session('toast.message')) })"></div>
    @endif

    {{-- KARTU TABEL DATA Divisi --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        {{-- Card Header & Filter Toolbar --}}
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">
                    Daftar List Divisi
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total {{ $divisions->total() }} lokasi terdaftar dalam sistem
                </p>
            </div>

            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">

                {{-- Kotak Pencarian --}}
                <div class="relative w-full lg:max-w-xs lg:flex-1">

                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Cari kode, nama, kota, npwp..."
                        class="input input-sm w-full bg-white pl-3 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>

                {{-- Kelompok Filter --}}
                <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">


                    {{-- Filter Perusahaan (jika ada lebih dari 1) --}}
                    @if ($companies->count() > 1)
                        <select wire:model.live="filterCompany"
                            class="select select-sm w-full bg-white sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            <option value="">Semua Perusahaan</option>
                            @foreach ($companies as $comp)
                                <option value="{{ $comp->code }}">{{ $comp->code }} - {{ $comp->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Jumlah Per Halaman --}}
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
                        <th class="cursor-pointer hover:text-emerald-700 select-none" wire:click="sortBy('code')">
                            <div class="flex items-center gap-1">
                                Kode Divisi
                                @if ($sortField === 'code')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer hover:text-emerald-700 select-none" wire:click="sortBy('name')">
                            <div class="flex items-center gap-1">
                                Nama Divisi
                                @if ($sortField === 'name')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @endif
                            </div>
                        </th>
                        <th>Alias</th>
                        <th>Perusahaan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($divisions as $divisi)
                        <tr wire:key="divisi-{{ $divisi->id }}"
                            class="hover:bg-emerald-50/40 dark:hover:bg-slate-800/60 transition-colors">
                            <td class="text-center text-slate-500 dark:text-slate-400">
                                {{ ($divisions->currentPage() - 1) * $divisions->perPage() + $loop->iteration }}
                            </td>

                            <td class="font-semibold text-emerald-700 dark:text-emerald-400 whitespace-nowrap">
                                {{ $divisi->code }}
                            </td>

                            <td>
                                <div class="font-medium text-slate-800 dark:text-white">
                                    {{ $divisi->name }}
                                </div>

                            </td>

                            <td class="text-slate-600 dark:text-slate-300">
                                <span class="font-medium text-slate-800 dark:text-white">{{ $divisi->alias }}</span>
                            </td>

                            <td class="text-slate-600 dark:text-slate-300 text-xs">
                                {{ $divisi->company?->name ?? '-' }}
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-1.5">

                                    <button type="button"
                                        @click="$wire.edit({{ $divisi->id }}).then(() => modalOpen = true)"
                                        wire:loading.attr="disabled" wire:target="edit({{ $divisi->id }})"
                                        class="btn btn-sm btn-outline btn-primary" title="Edit Divisi"
                                        aria-label="Edit Divisi">
                                        <span wire:loading.remove wire:target="edit({{ $divisi->id }})"
                                            class="icon-[tabler--pencil] size-4"></span>
                                        <span wire:loading wire:target="edit({{ $divisi->id }})"
                                            class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                                    </button>

                                    <button type="button"
                                        @click="deleteId = {{ $divisi->id }}; deleteName = @js($divisi->name); deleteOpen = true"
                                        class="btn btn-sm btn-outline btn-error" title="Hapus Divisi"
                                        aria-label="Hapus Divisi">
                                        <span class="icon-[tabler--trash] size-4"></span>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2.5">
                                    <div
                                        class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                        <span
                                            class="icon-[tabler--folder-x] size-7 text-slate-400 dark:text-slate-500"></span>
                                    </div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-slate-300">
                                        Tidak ada data Divisi ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @if ($search !== '' || $filterStatus !== '' || $filterCompany !== '')
                                            Coba sesuaikan kata kunci pencarian atau reset filter.
                                        @else
                                            Belum ada data Divisi terdaftar. Klik tombol Tambah Divisi untuk membuat.
                                        @endif
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        @if ($search !== '' || $filterStatus !== '' || $filterCompany !== '')
                                            <button type="button" wire:click="refreshData"
                                                class="btn btn-sm btn-outline btn-success">
                                                Reset Filter
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

        {{-- Card Footer & Pagination --}}
        <div
            class="flex flex-col gap-3 border-t border-slate-100 p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Menampilkan
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $divisions->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $divisions->lastItem() ?? 0 }}</span>
                dari
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $divisions->total() }}</span>
                Divisi
            </p>

            <div>
                {{ $divisions->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL FORM TAMBAH / EDIT Divisi --}}
    <div x-show="modalOpen" x-cloak x-transition.opacity.duration.150ms
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
        wire:click.self="closeModal">
        <div
            class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 my-8">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span
                        class="icon-[tabler--building-warehouse] size-6 text-emerald-600 dark:text-emerald-400"></span>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                        {{ $selectedId ? 'Edit Data Divisi' : 'Tambah Divisi Baru' }}
                    </h3>
                </div>
                <button type="button" wire:click="closeModal"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition-colors">
                    <span class="icon-[tabler--x] size-5"></span>
                </button>
            </div>

            {{-- Modal Body Form --}}
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 max-h-[70vh] overflow-y-auto">
                    {{-- Perusahaan --}}
                    <div class="col-span-1">
                        <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Perusahaan <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="company_code"
                            class="select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('company_code') border-red-500 @enderror">
                            <option value="">-- Pilih Perusahaan --</option>
                            @foreach ($companies as $comp)
                                <option value="{{ $comp->code }}">{{ $comp->name }}</option>
                            @endforeach
                        </select>
                        @error('company_code')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>


                    {{-- Kode Divisi --}}
                    <div class="col-span-1">
                        <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Kode Divisi <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="code" placeholder="Contoh: 02" maxlength="2"
                            inputmode="numeric" pattern="[0-9]*" autocomplete="off" x-data
                            x-on:beforeinput="if ($event.data && /\D/.test($event.data)) $event.preventDefault()"
                            class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('code') border-red-500 @enderror">
                        @error('code')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                    {{-- Nama Divisi --}}
                    <div class="col-span-1">
                        <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Nama Divisi <span class="text-red-500">*</span>
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
                            class="input input-sm w-full uppercase bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('alias') border-red-500 @enderror">
                        @error('alias')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>


                </div>

                {{-- Modal Footer --}}
                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <button type="button" @click="closeForm()"
                        class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                            <span class="icon-[tabler--device-floppy] size-4"></span>
                            {{ $selectedId ? 'Perbarui Divisi' : 'Simpan Divisi' }}
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
    {{-- end modal tambah / edit --}}

    {{-- MODAL KONFIRMASI HAPUS --}}
    <div x-show="deleteOpen" x-cloak x-transition.opacity.duration.150ms
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
        wire:click.self="closeDeleteModal">
        <div
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400">
                    <span class="icon-[tabler--alert-triangle] size-6"></span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                        Hapus Data Divisi
                    </h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        Apakah Anda yakin ingin menghapus Divisi
                        <span class="font-semibold text-slate-800 dark:text-white"
                            x-text="'&quot;' + deleteName + '&quot;'"></span>?
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" @click="deleteOpen = false"
                    class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">
                    Batal
                </button>
                <button type="button" type="button" @click="$wire.delete(deleteId)"
                    class="btn btn-sm bg-red-600 text-white hover:bg-red-700 flex items-center gap-1.5"
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
    {{-- end hapus  --}}
</div>
