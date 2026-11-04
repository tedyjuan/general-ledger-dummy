<div x-data="{
    modalOpen: false,
    closeForm() {
        this.modalOpen = false;
        this.$wire.closeModal();
    }
}" @modal-close.window="modalOpen = false" @keydown.escape.window="modalOpen && closeForm()">
    @php
        $me = auth()->user();
        $bisaTambah = (bool) $me?->izin('user', 'create');
        $bisaEdit = (bool) $me?->izin('user', 'update');
        $adaFilter = $search !== '';
        $colspan = $bisaEdit ? 6 : 5;
    @endphp

    {{-- HEADER HALAMAN --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                Manajemen User
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Kelola akun pengguna, role, dan akses company
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
                <button type="button" @click="$wire.create().then(() => modalOpen = true)"
                    class="btn btn-success flex items-center gap-1.5 shadow-xs">
                    <span class="icon-[tabler--user-plus] size-4"></span>
                    Tambah User
                </button>
            @endif
        </div>
    </div>

    {{-- KARTU TABEL DATA USER --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">Daftar User</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total {{ $users->total() }} user terdaftar
                </p>
            </div>

            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative w-full lg:max-w-xs lg:flex-1">
                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Cari nama atau username..."
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

                        @foreach (['name' => 'Nama', 'username' => 'Username', 'role_code' => 'Role'] as $field => $label)
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
                        @endforeach

                        <th>Akses Company</th>
                        @if ($bisaEdit)
                            <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($users as $item)
                        @php
                            $daftarCompany = $akses->get($item->username, collect());
                        @endphp
                        <tr wire:key="user-{{ $item->id }}"
                            class="transition-colors hover:bg-emerald-50/40 dark:hover:bg-slate-800/60">
                            <td class="text-center text-slate-500 dark:text-slate-400">
                                {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                        {{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800 dark:text-white">{{ $item->name }}
                                        </div>
                                        @if ($item->is_password_default)
                                            <span class="badge badge-soft badge-warning badge-xs">Password
                                                default</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap font-semibold text-emerald-700 dark:text-emerald-400">
                                {{ $item->username }}
                            </td>

                            <td>
                                <span class="badge badge-soft badge-primary badge-sm">{{ $item->role_code }}</span>
                            </td>

                            <td>
                                @if ($daftarCompany->isEmpty())
                                    <span class="badge badge-soft badge-error badge-sm">Belum ada company</span>
                                @else
                                    <div class="flex flex-wrap items-center gap-1"
                                        title="{{ $daftarCompany->pluck('company_code')->implode(', ') }}">
                                        @foreach ($daftarCompany->take(3) as $row)
                                            <span
                                                class="badge badge-outline badge-success badge-sm">{{ $row->company_code }}</span>
                                        @endforeach
                                        @if ($daftarCompany->count() > 3)
                                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                                +{{ $daftarCompany->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            @if ($bisaEdit)
                                <td>
                                    <div class="flex items-center justify-center">
                                        <button type="button"
                                            @click="$wire.edit({{ $item->id }}).then(() => modalOpen = true)"
                                            wire:loading.attr="disabled" wire:target="edit({{ $item->id }})"
                                            class="btn btn-sm btn-outline btn-primary" title="Edit User"
                                            aria-label="Edit User">
                                            <span wire:loading.remove wire:target="edit({{ $item->id }})"
                                                class="icon-[tabler--pencil] size-4"></span>
                                            <span wire:loading wire:target="edit({{ $item->id }})"
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
                                            class="icon-[tabler--users-group] size-7 text-slate-400 dark:text-slate-500"></span>
                                    </div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-slate-300">
                                        Tidak ada user ditemukan
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @if ($adaFilter)
                                            Coba sesuaikan kata kunci pencarian atau reset filter.
                                        @elseif ($bisaTambah)
                                            Belum ada user terdaftar. Klik tombol Tambah User untuk membuat.
                                        @else
                                            Belum ada user terdaftar.
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
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $users->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $users->lastItem() ?? 0 }}</span>
                dari
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $users->total() }}</span>
                user
            </p>
            <div>{{ $users->links() }}</div>
        </div>
    </div>

    {{-- MODAL FORM TAMBAH / EDIT --}}
    <div x-show="modalOpen" x-cloak x-transition.opacity.duration.150ms
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm transition-all"
        @click.self="closeForm()">
        <div
            class="relative my-8 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span
                        class="{{ $selectedId ? 'icon-[tabler--user-edit]' : 'icon-[tabler--user-plus]' }} size-6 text-emerald-600 dark:text-emerald-400"></span>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                        {{ $selectedId ? 'Edit Data User' : 'Tambah User Baru' }}
                    </h3>
                </div>
                <button type="button" @click="closeForm()" aria-label="Tutup"
                    class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <span class="icon-[tabler--x] size-5"></span>
                </button>
            </div>

            <form wire:submit.prevent="save">
                <div class="grid max-h-[70vh] grid-cols-1 gap-4 overflow-y-auto p-6 sm:grid-cols-2">
                    {{-- Nama --}}
                    <div class="col-span-1 sm:col-span-2">
                        <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" placeholder="Contoh: Budi Santoso"
                            autocomplete="off"
                            class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('name') border-red-500 @enderror">
                        @error('name')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Username --}}
                    <div class="col-span-1">
                        <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Username <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="username" placeholder="Contoh: budi.santoso"
                            autocomplete="off" @readonly($selectedId)
                            class="input input-sm w-full bg-white read-only:cursor-not-allowed read-only:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:read-only:bg-slate-800 @error('username') border-red-500 @enderror">
                        @if ($selectedId)
                            <span class="text-xs text-slate-400">Username tidak dapat diubah.</span>
                        @endif
                        @error('username')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Role --}}
                    <div class="col-span-1">
                        <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="role_code" @disabled($selectedId && $selectedId === auth()->id())
                            class="select select-sm w-full ...">
                            <option value="">-- Pilih role --</option>
                            @foreach ($roles as $r)
                                <option value="{{ $r['code'] }}">{{ $r['code'] }} - {{ $r['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @if ($selectedId && $selectedId === auth()->id())
                            <span class="text-xs text-slate-400">Role akun sendiri tidak dapat diubah.</span>
                        @endif
                        @error('role_code')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="col-span-1 sm:col-span-2">
                        <label class="label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Password
                            @if ($selectedId)
                                <span class="font-normal text-slate-400">(kosongkan jika tidak diubah)</span>
                            @else
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <div x-data="{ show: false }" class="relative">
                            <input :type="show ? 'text' : 'password'" wire:model="password"
                                autocomplete="new-password" placeholder="Minimal 8 karakter"
                                class="input input-sm w-full bg-white pr-10 dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('password') border-red-500 @enderror">
                            <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                aria-label="Tampilkan atau sembunyikan password">
                                <span :class="show ? 'icon-[tabler--eye-off]' : 'icon-[tabler--eye]'"
                                    class="size-4"></span>
                            </button>
                        </div>
                        @error('password')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror

                        <label
                            class="mt-3 flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" wire:model="is_password_default"
                                class="toggle toggle-success toggle-sm">
                            Tandai sebagai password default (user diminta menggantinya)
                        </label>
                    </div>

                    {{-- Akses Company --}}
                    <div class="col-span-1 sm:col-span-2" x-data="{ q: '' }">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                    Akses Company <span class="text-red-500">*</span>
                                </label>
                                <span class="badge badge-soft badge-success badge-sm"
                                    x-text="$wire.companyCodes.length + ' dipilih'"></span>
                            </div>
                            <div class="space-x-3 text-xs">
                                <button type="button" wire:click="pilihSemuaCompany"
                                    class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">Pilih
                                    semua</button>
                                <button type="button" wire:click="kosongkanCompany"
                                    class="text-slate-500 hover:underline dark:text-slate-400">Kosongkan</button>
                            </div>
                        </div>

                        <input type="search" x-model="q" placeholder="Cari company..."
                            class="input input-sm mb-2 w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">

                        <div
                            class="grid max-h-52 gap-2 overflow-y-auto rounded-lg border border-slate-200 p-3 sm:grid-cols-2 dark:border-slate-700 @error('companyCodes') border-red-500 @enderror">
                            @forelse ($companies as $c)
                                <label wire:key="co-{{ $c['code'] }}"
                                    x-show="!q || @js(mb_strtolower($c['code'] . ' ' . $c['name'])).includes(q.toLowerCase())"
                                    class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 p-2.5 text-sm transition-colors hover:border-emerald-300 hover:bg-emerald-50/50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:border-slate-700 dark:hover:bg-slate-800 dark:has-[:checked]:bg-emerald-950/30">
                                    <input type="checkbox" wire:model="companyCodes" value="{{ $c['code'] }}"
                                        class="checkbox checkbox-success checkbox-sm">
                                    <span class="min-w-0">
                                        <span
                                            class="block font-semibold text-emerald-700 dark:text-emerald-400">{{ $c['code'] }}</span>
                                        <span
                                            class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $c['name'] }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-sm text-slate-500 sm:col-span-2">Belum ada company.</p>
                            @endforelse
                        </div>
                        <p class="mt-1 text-xs text-slate-400">User hanya dapat login dan berpindah ke company yang
                            dipilih.</p>
                        @error('companyCodes')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                        @error('companyCodes.*')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <button type="button" @click="closeForm()"
                        class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                            <span class="icon-[tabler--device-floppy] size-4"></span>
                            {{ $selectedId ? 'Perbarui User' : 'Simpan User' }}
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
</div>
