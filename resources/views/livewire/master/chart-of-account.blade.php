<div>
    {{-- ===== HEADER HALAMAN ===== --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                Master Chart of Accounts (COA)
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Kelola bagan akun keuangan perusahaan (Chart of Accounts)
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="refreshData"
                class="btn btn-outline btn-success flex items-center gap-1.5 shadow-xs">
                <span wire:loading.remove wire:target="refreshData" class="icon-[tabler--refresh] size-4"></span>
                <span wire:loading wire:target="refreshData" class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                Refresh
            </button>
            <button type="button" wire:click="create" class="btn btn-success flex items-center gap-1.5 shadow-xs">
                <span class="icon-[tabler--plus] size-4"></span>
                Tambah Akun
            </button>
        </div>
    </div>

    {{-- ===== FLASH NOTIFIKASI ===== --}}
    @if (session()->has('success'))
        <div
            class="mb-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-xs dark:border-emerald-800/40 dark:bg-emerald-950/40 dark:text-emerald-300">
            <div class="flex items-center gap-2.5">
                <span class="icon-[tabler--circle-check] size-5 text-emerald-600 dark:text-emerald-400"></span>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()"
                class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">
                <span class="icon-[tabler--x] size-4"></span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div
            class="mb-4 flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-xs dark:border-red-800/40 dark:bg-red-950/40 dark:text-red-300">
            <div class="flex items-center gap-2.5">
                <span class="icon-[tabler--alert-circle] size-5 text-red-600 dark:text-red-400"></span>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()"
                class="text-red-600 hover:text-red-800 dark:text-red-400">
                <span class="icon-[tabler--x] size-4"></span>
            </button>
        </div>
    @endif

    {{-- ===== KARTU TABEL ===== --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

        {{-- Toolbar Filter --}}
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">Daftar Akun COA</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total {{ $accounts->total() }} akun terdaftar dalam sistem
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                {{-- Pencarian --}}
                <div class="relative w-full sm:w-56">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <span class="icon-[tabler--search] size-4"></span>
                    </span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor, nama akun..."
                        class="input input-sm w-full pl-9 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>

                {{-- Filter Tipe Akun --}}
                <select wire:model.live="filterType"
                    class="select select-sm w-full sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Semua Tipe</option>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                    <option value="retained earning account">Retained Earning</option>
                </select>

                {{-- Filter Metode --}}
                <select wire:model.live="filterMethod"
                    class="select select-sm w-full sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Semua Metode</option>
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>

                {{-- Filter Group --}}
                <select wire:model.live="filterGroup"
                    class="select select-sm w-full sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Semua Group</option>
                    <option value="kas">Kas</option>
                    <option value="bank">Bank</option>
                    <option value="inventory">Inventory</option>
                    <option value="sales">Sales</option>
                </select>

                {{-- Filter Perusahaan --}}
                <select wire:model.live="filterCompany"
                    class="select select-sm w-full sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Semua Perusahaan</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>

                {{-- Per Page --}}
                <select wire:model.live="perPage"
                    class="select select-sm w-full sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="table table-sm w-full">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                    <tr>
                        <th class="w-10 px-4 py-3 text-center">#</th>
                        <th class="cursor-pointer px-4 py-3 text-left" wire:click="sortBy('account_number')">
                            <div class="flex items-center gap-1">
                                No. Akun
                                @if ($sortField === 'account_number')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @else
                                    <span class="icon-[tabler--arrows-sort] size-3.5 opacity-40"></span>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer px-4 py-3 text-left" wire:click="sortBy('name')">
                            <div class="flex items-center gap-1">
                                Nama Akun
                                @if ($sortField === 'name')
                                    <span
                                        class="icon-[tabler--chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}] size-3.5"></span>
                                @else
                                    <span class="icon-[tabler--arrows-sort] size-3.5 opacity-40"></span>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">Tipe</th>
                        <th class="px-4 py-3 text-left">Metode</th>
                        <th class="px-4 py-3 text-left">Group</th>
                        <th class="px-4 py-3 text-left">Cost Center</th>
                        <th class="px-4 py-3 text-left">Header</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($accounts as $account)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors duration-150">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">
                                {{ $accounts->firstItem() + $loop->index }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ $account->account_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800 dark:text-white text-sm">
                                    {{ $account->name }}
                                </div>
                                @if ($account->description)
                                    <div class="text-xs text-slate-400 mt-0.5 truncate max-w-xs">
                                        {{ $account->description }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($account->account_type)
                                    @php
                                        $typeColors = [
                                            'asset' => 'badge-info',
                                            'liability' => 'badge-warning',
                                            'equity' => 'badge-secondary',
                                            'income' => 'badge-success',
                                            'expense' => 'badge-error',
                                            'retained earning account' => 'badge-neutral',
                                        ];
                                        $color = $typeColors[$account->account_type] ?? 'badge-ghost';
                                    @endphp
                                    <span class="badge {{ $color }} badge-soft text-xs capitalize">
                                        {{ $account->account_type }}
                                    </span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($account->account_method)
                                    <span
                                        class="badge {{ $account->account_method === 'debit' ? 'badge-info' : 'badge-warning' }} badge-soft text-xs">
                                        {{ ucfirst($account->account_method) }}
                                    </span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300 capitalize">
                                {{ $account->account_group ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($account->cost_center_type)
                                    @php
                                        $ccColors = [
                                            'all' => 'badge-ghost',
                                            'depo' => 'badge-primary',
                                            'unit' => 'badge-accent',
                                        ];
                                        $ccColor = $ccColors[$account->cost_center_type] ?? 'badge-ghost';
                                    @endphp
                                    <span class="badge {{ $ccColor }} badge-soft text-xs">
                                        {{ ucfirst($account->cost_center_type) }}
                                    </span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                @if ($account->header)
                                    <span class="font-mono">{{ $account->header->account_number }}</span>
                                    <span class="mx-1 text-slate-300">·</span>
                                    <span>{{ Str::limit($account->header->name, 20) }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">Root</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $account->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $account->id }})"
                                        class="btn btn-sm btn-outline btn-primary" title="Edit" aria-label="Edit">
                                        <span class="icon-[tabler--pencil] size-4"></span>
                                    </button>

                                    <button type="button" wire:click="confirmDelete({{ $account->id }})"
                                        wire:loading.attr="disabled" wire:target="confirmDelete({{ $account->id }})"
                                        class="btn btn-square btn-sm btn-outline  btn-error" title="Hapus"
                                        aria-label="Hapus">
                                        <span class="icon-[tabler--trash] size-4"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center text-slate-400">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="icon-[tabler--database-off] size-12 opacity-30"></span>
                                    <p class="text-sm">Tidak ada data akun COA yang ditemukan.</p>
                                    @if ($search || $filterType || $filterMethod || $filterGroup)
                                        <button wire:click="refreshData"
                                            class="btn btn-sm btn-ghost text-emerald-600">
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

        {{-- Pagination --}}
        @if ($accounts->hasPages())
            <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>

    {{-- ===== MODAL TAMBAH / EDIT ===== --}}
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            wire:click.self="closeModal">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900 max-h-[90vh] flex flex-col">

                {{-- Header Modal --}}
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                            <span class="icon-[tabler--book-2] size-5 text-emerald-600 dark:text-emerald-400"></span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 dark:text-white">
                                {{ $selectedId ? 'Edit Akun COA' : 'Tambah Akun COA' }}
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ $selectedId ? 'Perbarui informasi akun yang ada' : 'Buat akun baru dalam chart of accounts' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal"
                        class="btn btn-square btn-sm btn-ghost text-slate-400 hover:text-slate-700">
                        <span class="icon-[tabler--x] size-5"></span>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="overflow-y-auto px-6 py-4 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Nomor Akun --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Nomor Akun <span class="text-red-500">*</span>
                            </label>
                            <input type="number" wire:model="account_number"
                                class="input input-sm w-full font-mono @error('account_number') input-error @enderror"
                                placeholder="Contoh: 1111">
                            @error('account_number')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Perusahaan --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Perusahaan <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="company_id"
                                class="select select-sm w-full @error('company_id') select-error @enderror">
                                <option value="">-- Pilih Perusahaan --</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nama Akun --}}
                        <div class="sm:col-span-2">
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Nama Akun <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name"
                                class="input input-sm w-full @error('name') input-error @enderror"
                                placeholder="Nama akun dalam bagan akun">
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Deskripsi --}}
                        <div class="sm:col-span-2">
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Deskripsi
                            </label>
                            <textarea wire:model="description" rows="2"
                                class="textarea textarea-sm w-full @error('description') textarea-error @enderror"
                                placeholder="Deskripsi singkat akun ini..."></textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Header Akun --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Header Akun (Parent)
                            </label>
                            <select wire:model="header_id"
                                class="select select-sm w-full @error('header_id') select-error @enderror">
                                <option value="">-- Tidak Ada (Root) --</option>
                                @foreach ($headers as $h)
                                    @if ($h->id !== $selectedId)
                                        <option value="{{ $h->id }}">{{ $h->account_number }} -
                                            {{ $h->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('header_id')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Referensi Akun --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Referensi Akun
                            </label>
                            <select wire:model="reference_id"
                                class="select select-sm w-full @error('reference_id') select-error @enderror">
                                <option value="">-- Tidak Ada --</option>
                                @foreach ($headers as $h)
                                    @if ($h->id !== $selectedId)
                                        <option value="{{ $h->id }}">{{ $h->account_number }} -
                                            {{ $h->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('reference_id')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tipe Akun --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Tipe Akun
                            </label>
                            <select wire:model="account_type"
                                class="select select-sm w-full @error('account_type') select-error @enderror">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="retained earning account">Retained Earning Account</option>
                            </select>
                            @error('account_type')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Metode Akun --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Metode Normal
                            </label>
                            <select wire:model="account_method"
                                class="select select-sm w-full @error('account_method') select-error @enderror">
                                <option value="">-- Pilih Metode --</option>
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                            @error('account_method')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Kelompok Akun --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Kelompok Akun
                            </label>
                            <select wire:model="account_group"
                                class="select select-sm w-full @error('account_group') select-error @enderror">
                                <option value="">-- Tidak Ada --</option>
                                <option value="kas">Kas</option>
                                <option value="bank">Bank</option>
                                <option value="inventory">Inventory</option>
                                <option value="sales">Sales</option>
                            </select>
                            @error('account_group')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cost Center Type --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Tipe Cost Center
                            </label>
                            <select wire:model="cost_center_type"
                                class="select select-sm w-full @error('cost_center_type') select-error @enderror">
                                <option value="">-- Pilih --</option>
                                <option value="all">All</option>
                                <option value="depo">Depo</option>
                                <option value="unit">Unit</option>
                            </select>
                            @error('cost_center_type')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- TBAG 1 --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Kelompok Trial Balance 1
                            </label>
                            <select wire:model="tbag_1"
                                class="select select-sm w-full @error('tbag_1') select-error @enderror">
                                <option value="">-- Tidak Ada --</option>
                                @foreach ($tbag1List as $t)
                                    <option value="{{ $t->id }}">{{ $t->code }} - {{ $t->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tbag_1')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- TBAG 2 --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Kelompok Trial Balance 2
                            </label>
                            <select wire:model="tbag_2"
                                class="select select-sm w-full @error('tbag_2') select-error @enderror">
                                <option value="">-- Tidak Ada --</option>
                                @foreach ($tbag2List as $t)
                                    <option value="{{ $t->id }}">{{ $t->code }} - {{ $t->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tbag_2')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- TBAG 3 --}}
                        <div>
                            <label
                                class="label label-text text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Kelompok Trial Balance 3
                            </label>
                            <select wire:model="tbag_3"
                                class="select select-sm w-full @error('tbag_3') select-error @enderror">
                                <option value="">-- Tidak Ada --</option>
                                @foreach ($tbag3List as $t)
                                    <option value="{{ $t->id }}">{{ $t->code }} - {{ $t->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tbag_3')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Footer Modal --}}
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                    <button type="button" wire:click="closeModal"
                        class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">
                        Batal
                    </button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="btn btn-sm btn-success flex items-center gap-2">
                        <span wire:loading.remove wire:target="save"
                            class="icon-[tabler--device-floppy] size-4"></span>
                        <span wire:loading wire:target="save"
                            class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                        {{ $selectedId ? 'Perbarui Akun' : 'Simpan Akun' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== MODAL KONFIRMASI HAPUS ===== --}}
    @if ($isDeleteModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            wire:click.self="closeDeleteModal">
            <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                <div class="flex flex-col items-center gap-4 p-6 text-center">
                    <div class="flex size-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                        <span class="icon-[tabler--trash] size-7 text-red-600 dark:text-red-400"></span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Konfirmasi Hapus</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Apakah Anda yakin ingin menghapus akun
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                            "{{ $deleteName }}"?
                        </p>
                        <p class="mt-2 text-xs text-red-500">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                    <div class="flex w-full gap-2">
                        <button type="button" wire:click="closeDeleteModal"
                            class="btn btn-sm btn-outline flex-1 text-slate-600 dark:text-slate-300">
                            Batal
                        </button>
                        <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                            class="btn btn-sm btn-error flex-1 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="delete" class="icon-[tabler--trash] size-4"></span>
                            <span wire:loading wire:target="delete"
                                class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
