<div @keydown.escape.window="$wire.backToList()">
    @php
        $isEdit = (bool) $selectedId;
        $inputClass = 'input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white';
        $selectClass = 'select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white';
        $labelClass = 'label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200';
        $readonlyClass = 'cursor-not-allowed bg-slate-100 dark:bg-slate-800 dark:text-slate-300';

        // dropdown struktur: [property, key referensi, label]
        $orgFields = [
            ['area_code', 'area', 'Area (Depo)'],
            ['department_code', 'department', 'Department'],
            ['division_code', 'division', 'Division'],
            ['segment_code', 'segment', 'Segment'],
        ];
    @endphp

    {{-- HEADER HALAMAN --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                {{ $isEdit ? 'Edit Cost Center' : 'Tambah Cost Center Baru' }}
                <span class="badge badge-soft badge-success ml-2 align-middle text-xs">{{ $activeCompany }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Cost Center · data tersimpan untuk perusahaan {{ $activeCompany }}
            </p>
        </div>

        <button type="button" wire:click="backToList"
            class="btn btn-outline flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
            <span class="icon-[tabler--arrow-left] size-4"></span>
            Kembali ke daftar
        </button>
    </div>

    @if ($isEdit)
        <div role="alert" class="alert alert-soft alert-warning mb-4 flex items-start gap-2 text-sm">
            <span class="icon-[tabler--lock] mt-0.5 size-5 shrink-0"></span>
            <span>
                Area, department, division, segment, kode, dan nama tidak bisa diubah saat edit karena kode cost center
                disusun dari pilihan tersebut. Manager dan deskripsi tetap bisa diubah.
            </span>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-5">

        {{-- 1. STRUKTUR --}}
        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-3 dark:border-slate-800">
                <h2 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-white">
                    <span class="icon-[tabler--hierarchy-2] size-5 text-emerald-600 dark:text-emerald-400"></span>
                    Struktur Cost Center
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($orgFields as [$prop, $key, $label])
                    <div wire:key="org-{{ $prop }}">
                        <label class="{{ $labelClass }}">{{ $label }} <span
                                class="text-red-500">*</span></label>
                        <select wire:model.live="{{ $prop }}" @disabled($isEdit)
                            class="{{ $selectClass }} @error($prop) border-red-500 @enderror {{ $isEdit ? $readonlyClass : '' }}">
                            <option value="">-- Pilih --</option>
                            @foreach ($ref[$key] as $code => $alias)
                                <option value="{{ $code }}">
                                    {{ $code }} — {{ $alias }}@if ($key === 'area' && !empty($ref['area_name'][$code]))
                                        ({{ $ref['area_name'][$code] }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error($prop)
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                @endforeach
            </div>

            @if (empty($ref['area']))
                <div class="px-6 pb-4 text-xs text-amber-600 dark:text-amber-400">
                    Belum ada data depo untuk perusahaan {{ $activeCompany }}. Tambahkan depo terlebih dahulu.
                </div>
            @endif
        </div>

        {{-- 2. HASIL (otomatis) --}}
        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-3 dark:border-slate-800">
                <h2 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-white">
                    <span class="icon-[tabler--wand] size-5 text-emerald-600 dark:text-emerald-400"></span>
                    Kode &amp; Nama
                    <span class="text-xs font-normal text-slate-400">(otomatis, tidak perlu diisi)</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">Perusahaan</label>
                    <input type="text" value="{{ $activeCompany }}" readonly
                        class="{{ $inputClass }} {{ $readonlyClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">Kode Cost Center</label>
                    <input type="text" value="{{ $preview['code'] ?? '' }}" readonly
                        placeholder="Pilih area, department, division, segment"
                        class="{{ $inputClass }} font-mono font-semibold {{ $readonlyClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">Team (Nama)</label>
                    <input type="text" value="{{ $preview['name'] ?? '' }}" readonly
                        placeholder="AREA/DEPT/DIVISI/SEGMENT" class="{{ $inputClass }} {{ $readonlyClass }}">
                </div>

                <p class="text-[11px] text-slate-400 md:col-span-3">
                    Kode = kode area + kode department + kode division + kode segment (contoh 02 + 03 + 01 + 07 =
                    02030107). Nama = alias masing-masing dipisah "/" (contoh ALS/SOPR/OPR/W-GLN).
                </p>
            </div>
        </div>

        {{-- 3. DETAIL (input user) --}}
        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-3 dark:border-slate-800">
                <h2 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-white">
                    <span class="icon-[tabler--user-circle] size-5 text-emerald-600 dark:text-emerald-400"></span>
                    Detail
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">Manager</label>
                    <input type="text" wire:model="manager" placeholder="Nama manager (opsional)" maxlength="100"
                        autocomplete="off"
                        class="{{ $inputClass }} uppercase @error('manager') border-red-500 @enderror">
                    @error('manager')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">Deskripsi</label>
                    <textarea wire:model="description" rows="3" placeholder="Keterangan singkat (opsional)"
                        class="textarea w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('description') border-red-500 @enderror"></textarea>
                    @error('description')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- TOMBOL AKSI --}}
        <div
            class="flex items-center justify-end gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
            <button type="button" wire:click="backToList"
                class="btn btn-sm btn-outline text-slate-600 dark:text-slate-300">Batal</button>

            <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5" wire:loading.attr="disabled"
                wire:target="save">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                    <span class="icon-[tabler--device-floppy] size-4"></span>
                    {{ $isEdit ? 'Perbarui Cost Center' : 'Simpan Cost Center' }}
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                    <span class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>
