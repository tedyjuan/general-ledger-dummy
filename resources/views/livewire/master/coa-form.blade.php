<div @keydown.escape.window="$wire.backToList()">
    @php
        $isEdit = (bool) $selectedId;
        $inputClass = 'input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white';
        $selectClass = 'select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white';
        $labelClass = 'label mb-1 text-xs font-semibold text-slate-700 dark:text-slate-200';
        $readonlyClass = 'cursor-not-allowed bg-slate-100 dark:bg-slate-800 dark:text-slate-300';
        $levelHint =
            [
                'header' => 'Wajib 4 angka, tidak boleh diawali 0 (contoh 1001).',
                'ledger' => 'Nomor otomatis: nomor header + 2 digit urut (1001 + 01 = 100101).',
                'subledger' => 'Nomor otomatis: nomor ledger + 2 digit urut (100101 + 01 = 10010101).',
            ][$level] ?? '';
        $prefix = $level === 'ledger' ? $header_code : ($level === 'subledger' ? $reference_code : '');
    @endphp

    {{-- HEADER HALAMAN --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                {{ $isEdit ? 'Edit Akun' : 'Tambah Akun Baru' }}
                <span class="badge badge-soft badge-success ml-2 align-middle text-xs">{{ $activeCompany }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Chart of Accounts · data tersimpan untuk perusahaan {{ $activeCompany }}
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
                Level, nomor akun, header, dan ledger tidak bisa diubah saat edit supaya hierarki dan
                nomor akun tetap konsisten. Nama, deskripsi, dan klasifikasi tetap bisa diubah.
            </span>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-5">

        {{-- 1. HIERARKI --}}
        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-3 dark:border-slate-800">
                <h2 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-white">
                    <span class="icon-[tabler--hierarchy-2] size-5 text-emerald-600 dark:text-emerald-400"></span>
                    Hierarki Akun
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                {{-- Level --}}
                <div>
                    <label class="{{ $labelClass }}">Level Akun <span class="text-red-500">*</span></label>
                    <select wire:model.live="level" @disabled($isEdit) class="{{ $selectClass }}">
                        <option value="header">Header</option>
                        <option value="ledger">Ledger</option>
                        <option value="subledger">Subledger</option>
                    </select>
                    @error('level')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                    <p class="mt-1 text-[11px] text-slate-400">{{ $levelHint }}</p>
                </div>

                {{-- Header --}}
                @if ($level !== 'header')
                    <div>
                        <label class="{{ $labelClass }}">Header <span class="text-red-500">*</span></label>
                        <select wire:model.live="header_code" @disabled($isEdit)
                            class="{{ $selectClass }} @error('header_code') border-red-500 @enderror">
                            <option value="">-- Pilih header --</option>
                            @foreach ($opsi['headers'] as $h)
                                <option value="{{ $h->account_number }}">
                                    {{ $h->account_number }} — {{ $h->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('header_code')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                {{-- Ledger (khusus subledger) --}}
                @if ($level === 'subledger')
                    <div>
                        <label class="{{ $labelClass }}">Ledger <span class="text-red-500">*</span></label>
                        <select wire:model.live="reference_code" @disabled($isEdit || $header_code === '')
                            class="{{ $selectClass }} @error('reference_code') border-red-500 @enderror">
                            <option value="">
                                {{ $header_code === '' ? 'Pilih header dulu' : '-- Pilih ledger --' }}
                            </option>
                            @foreach ($opsi['ledgers'] as $l)
                                <option value="{{ $l->account_number }}">
                                    {{ $l->account_number }} — {{ $l->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('reference_code')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        {{-- 2. IDENTITAS --}}
        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-3 dark:border-slate-800">
                <h2 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-white">
                    <span class="icon-[tabler--id] size-5 text-emerald-600 dark:text-emerald-400"></span>
                    Identitas Akun
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">Perusahaan</label>
                    <input type="text" value="{{ $activeCompany }}" readonly
                        class="{{ $inputClass }} {{ $readonlyClass }}">
                </div>

                {{-- Nomor Akun --}}
                <div>
                    <label class="{{ $labelClass }}">Nomor Akun <span class="text-red-500">*</span></label>

                    @if ($isEdit)
                        {{-- EDIT: nomor dikunci --}}
                        <input type="text" value="{{ $account_number }}" readonly
                            class="{{ $inputClass }} {{ $readonlyClass }}">
                    @elseif ($level === 'header')
                        {{-- HEADER: 4 angka, tidak boleh diawali 0 --}}
                        <input type="text" wire:model="account_number" placeholder="Contoh: 1001" inputmode="numeric"
                            pattern="[1-9][0-9]{3}" autocomplete="off" maxlength="4" x-data
                            x-on:beforeinput="
                                if ($event.data && (/\D/.test($event.data) || ($el.selectionStart === 0 && $event.data.startsWith('0')))) {
                                    $event.preventDefault()
                                }"
                            class="{{ $inputClass }} font-mono @error('account_number') border-red-500 @enderror">
                    @else
                        {{-- LEDGER / SUBLEDGER: prefix dari induk + 2 angka urut --}}
                        <div class="flex items-center gap-2">
                            <input type="text" value="{{ $prefix }}" readonly
                                placeholder="{{ $level === 'ledger' ? 'Header' : 'Ledger' }}"
                                class="input input-sm w-28 shrink-0 font-mono {{ $readonlyClass }}">

                            <input type="text" wire:model.live.debounce.400ms="code_suffix" placeholder="01"
                                wire:key="suffix-{{ $level }}-{{ $prefix }}" inputmode="numeric"
                                pattern="[0-9]{2}" autocomplete="off" maxlength="2" x-data
                                x-on:beforeinput="if ($event.data && /\D/.test($event.data)) $event.preventDefault()"
                                x-on:blur="if ($el.value.length === 1) { $el.value = '0' + $el.value; $el.dispatchEvent(new Event('input', { bubbles: true })) }"
                                class="input input-sm w-20 shrink-0 bg-white text-center font-mono dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('code_suffix') border-red-500 @enderror">
                            <button type="button" wire:click="suggestSuffix" wire:loading.attr="disabled"
                                wire:target="suggestSuffix" class="btn btn-sm btn-outline btn-success"
                                title="Isi nomor urut berikutnya">
                                <span class="icon-[tabler--wand] size-4"></span>
                                Auto
                            </button>
                        </div>
                    @endif

                    @error('account_number')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                    @error('code_suffix')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror

                    @if (!$isEdit && $preview !== '')
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                            Nomor akun lengkap:
                            <span
                                class="font-mono font-semibold text-emerald-700 dark:text-emerald-400">{{ $preview }}</span>
                        </p>
                    @endif
                </div>

                {{-- Nama (tampil uppercase; server juga menyimpan uppercase) --}}
                <div>
                    <label class="{{ $labelClass }}">Nama Akun <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" placeholder="CONTOH: KAS BESAR" autocomplete="off"
                        class="{{ $inputClass }} uppercase @error('name') border-red-500 @enderror">
                    @error('name')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Deskripsi <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="description" placeholder="Keterangan singkat akun"
                        class="{{ $inputClass }} @error('description') border-red-500 @enderror">
                    @error('description')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 3. KLASIFIKASI --}}
        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-3 dark:border-slate-800">
                <h2 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-white">
                    <span class="icon-[tabler--category] size-5 text-emerald-600 dark:text-emerald-400"></span>
                    Klasifikasi
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="{{ $labelClass }}">Tipe Akun</label>
                    <select wire:model.live="account_type"
                        class="{{ $selectClass }} capitalize @error('account_type') border-red-500 @enderror">
                        <option value="">-- Pilih tipe --</option>
                        @foreach (\App\Models\Coa::ACCOUNT_TYPES as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    @error('account_type')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Metode Akun</label>
                    <select wire:model="account_method" class="{{ $selectClass }}">
                        <option value="">-- Pilih metode --</option>
                        @foreach (\App\Models\Coa::ACCOUNT_METHODS as $m)
                            <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                    @error('account_method')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Grup Akun</label>
                    <select wire:model="account_group" class="{{ $selectClass }}">
                        <option value="">-- Tidak ada --</option>
                        @foreach (\App\Models\Coa::ACCOUNT_GROUPS as $g)
                            <option value="{{ $g }}">{{ ucfirst($g) }}</option>
                        @endforeach
                    </select>
                    @error('account_group')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Tipe Cost Center</label>
                    <select wire:model="cost_center_type" class="{{ $selectClass }}">
                        <option value="">-- Tidak ada --</option>
                        @foreach (\App\Models\Coa::COST_CENTER_TYPES as $c)
                            <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                    @error('cost_center_type')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 4. TRIAL BALANCE GROUP --}}
        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-3 dark:border-slate-800">
                <h2 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-white">
                    <span class="icon-[tabler--scale] size-5 text-emerald-600 dark:text-emerald-400"></span>
                    Trial Balance Group
                    <span class="text-xs font-normal text-slate-400">(opsional, berurutan: Group 1 › 2 › 3)</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                {{-- Group 1 --}}
                <div>
                    <label class="{{ $labelClass }}">Group 1</label>
                    <select wire:model.live="tbag_1" @disabled($account_type === '')
                        class="{{ $selectClass }} @error('tbag_1') border-red-500 @enderror">
                        <option value="">
                            {{ $account_type === '' ? 'Pilih tipe akun dulu' : '-- Pilih group 1 --' }}
                        </option>
                        @foreach ($opsi['group1'] as $g)
                            <option value="{{ $g->code }}">
                                {{ $g->code }}@if ($g->description)
                                    — {{ $g->description }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('tbag_1')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Group 2 --}}
                <div>
                    <label class="{{ $labelClass }}">Group 2</label>
                    <select wire:model.live="tbag_2" @disabled($tbag_1 === '')
                        class="{{ $selectClass }} @error('tbag_2') border-red-500 @enderror">
                        <option value="">
                            {{ $tbag_1 === '' ? 'Pilih group 1 dulu' : '-- Pilih group 2 --' }}
                        </option>
                        @foreach ($opsi['group2'] as $g)
                            <option value="{{ $g->code }}">
                                {{ $g->code }}@if ($g->description)
                                    — {{ $g->description }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('tbag_2')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Group 3 --}}
                <div>
                    <label class="{{ $labelClass }}">Group 3</label>
                    <select wire:model="tbag_3" @disabled($tbag_2 === '')
                        class="{{ $selectClass }} @error('tbag_3') border-red-500 @enderror">
                        <option value="">
                            {{ $tbag_2 === '' ? 'Pilih group 2 dulu' : '-- Pilih group 3 --' }}
                        </option>
                        @foreach ($opsi['group3'] as $g)
                            <option value="{{ $g->code }}">
                                {{ $g->code }}@if ($g->description)
                                    — {{ $g->description }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('tbag_3')
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

            <button type="submit" class="btn btn-sm btn-success flex items-center gap-1.5"
                wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                    <span class="icon-[tabler--device-floppy] size-4"></span>
                    {{ $isEdit ? 'Perbarui Akun' : 'Simpan Akun' }}
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                    <span class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>
