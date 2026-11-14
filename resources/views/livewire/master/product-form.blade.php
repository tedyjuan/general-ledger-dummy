<div>
    @php
        $isEdit = $selectedId !== null;

        // gaya chip Debit / Kredit
        $chip =
            'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-emerald-400 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 dark:border-slate-700 dark:text-slate-300';
        $label = 'mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200';
        $input = 'input w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white';
    @endphp

    {{-- ============ HEADER HALAMAN ============ --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <button type="button" wire:click="backToList" class="btn btn-sm btn-circle btn-soft btn-success mt-0.5"
                title="Kembali ke daftar" aria-label="Kembali ke daftar">
                <span class="icon-[tabler--arrow-left] size-4"></span>
            </button>
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                    {{ $isEdit ? 'Edit Produk' : 'Tambah Produk' }}</h1>
                <div
                    class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <span class="icon-[tabler--building] size-3.5"></span>
                        {{ $activeCompany }}
                    </span>
                    <span>Isi data produk, lalu atur mapping akunnya</span>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">

        @if ($errors->any())
            <div class="alert alert-soft alert-error flex items-start gap-2" role="alert">
                <span class="icon-[tabler--alert-circle] mt-0.5 size-5 shrink-0"></span>
                <span>Ada isian yang belum benar. Periksa kolom yang bertanda merah di bawah.</span>
            </div>
        @endif

        {{-- ============ 1. DATA PRODUK ============ --}}
        <div class="card card-border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="icon-[tabler--package] size-5"></span>
                </div>
                <div>
                    <h2 class="font-semibold leading-tight text-slate-800 dark:text-white">Data Produk</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Identitas produk dan akun
                        utamanya</p>
                </div>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2">
                <div>
                    <label for="product_code" class="{{ $label }}">Kode Produk <span
                            class="text-red-500">*</span></label>
                    <input id="product_code" type="text" maxlength="50" wire:model="product_code"
                        placeholder="mis. BBM-001" @disabled($isEdit) autocomplete="off"
                        class="{{ $input }} font-mono uppercase {{ $errors->has('product_code') ? 'border-red-400' : '' }} disabled:bg-slate-100 dark:disabled:bg-slate-800">
                    @if ($isEdit)
                        <p class="mt-1 text-xs text-slate-400">Kode produk tidak bisa diubah setelah disimpan.</p>
                    @else
                        <p class="mt-1 text-xs text-slate-400">Harus unik di perusahaan ini. Otomatis huruf besar.</p>
                    @endif
                    @error('product_code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_name" class="{{ $label }}">Nama Produk <span
                            class="text-red-500">*</span></label>
                    <input id="product_name" type="text" maxlength="255" wire:model="product_name"
                        placeholder="mis. Solar Industri" autocomplete="off"
                        class="{{ $input }} {{ $errors->has('product_name') ? 'border-red-400' : '' }}">
                    @error('product_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Akun COA Produk <span class="text-red-500">*</span></label>
                    @include('partials.coa-select', [
                        'path' => 'coa_code',
                        'options' => $opsi['coas'],
                        'invalid' => $errors->has('coa_code'),
                    ])
                    <p class="mt-1 text-xs text-slate-400">Pilih akun ledger atau subledger. Akun header tidak
                        ditampilkan.</p>
                    @error('coa_code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ============ 2. MAPPING PRODUK ============ --}}
        <div class="card card-border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div
                class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <span class="icon-[tabler--sitemap] size-5"></span>
                    </div>
                    <div>
                        <h2 class="font-semibold leading-tight text-slate-800 dark:text-white">Mapping Produk</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            <span
                                class="font-semibold text-slate-700 dark:text-slate-200">{{ count($mappings) }}</span>
                            baris. Tiap baris menentukan akun yang dipakai untuk satu jenis transaksi.
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="addMapping" wire:loading.attr="disabled" wire:target="addMapping"
                    class="btn btn-sm btn-outline btn-success gap-1.5">
                    <span wire:loading.remove wire:target="addMapping" class="icon-[tabler--plus] size-4"></span>
                    <span wire:loading wire:target="addMapping"
                        class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                    Tambah Mapping
                </button>
            </div>

            <div class="space-y-4 p-4">
                @error('mappings')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror

                @forelse ($mappings as $i => $m)
                    <div wire:key="map-{{ $m['key'] }}"
                        class="rounded-xl border border-slate-200 bg-slate-50/50 dark:border-slate-700 dark:bg-slate-800/30">

                        {{-- Kepala baris --}}
                        <div
                            class="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2 dark:border-slate-700">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                Mapping {{ $i + 1 }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="duplicateMapping({{ $i }})"
                                    class="btn btn-xs btn-soft btn-success gap-1" title="Salin baris ini">
                                    <span class="icon-[tabler--copy] size-3.5"></span>
                                    Salin
                                </button>
                                <button type="button" wire:click="removeMapping({{ $i }})"
                                    class="btn btn-xs btn-soft btn-error gap-1" title="Hapus baris ini">
                                    <span class="icon-[tabler--trash] size-3.5"></span>
                                    Hapus
                                </button>
                            </div>
                        </div>

                        {{-- Isian baris --}}
                        <div class="grid gap-3 p-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="{{ $label }}">Tipe Transaksi</label>
                                <select wire:model="mappings.{{ $i }}.transaction_type"
                                    class="select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white {{ $errors->has("mappings.$i.transaction_type") ? 'border-red-400' : '' }}">
                                    <option value="">Pilih tipe transaksi</option>
                                    @foreach ($opsi['trx'] as $val => $teks)
                                        <option value="{{ $val }}">{{ $teks }}</option>
                                    @endforeach
                                </select>
                                @error("mappings.$i.transaction_type")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="{{ $label }}">Tipe Operasional</label>
                                <select wire:model="mappings.{{ $i }}.operational_type"
                                    class="select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white {{ $errors->has("mappings.$i.operational_type") ? 'border-red-400' : '' }}">
                                    <option value="">Pilih tipe operasional</option>
                                    @foreach ($opsi['ops'] as $val => $teks)
                                        <option value="{{ $val }}">{{ $teks }}</option>
                                    @endforeach
                                </select>
                                @error("mappings.$i.operational_type")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="{{ $label }}">DO Type</label>
                                <select wire:model="mappings.{{ $i }}.do_type"
                                    class="select select-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white {{ $errors->has("mappings.$i.do_type") ? 'border-red-400' : '' }}">
                                    <option value="">Pilih DO type</option>
                                    @foreach ($opsi['do'] as $val => $teks)
                                        <option value="{{ $val }}">{{ $teks }}</option>
                                    @endforeach
                                </select>
                                @error("mappings.$i.do_type")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="{{ $label }}">Cost Center</label>
                                @include('partials.coa-select', [
                                    'path' => "mappings.$i.cost_center_code",
                                    'options' => $opsi['costCenters'],
                                    'invalid' => $errors->has("mappings.$i.cost_center_code"),
                                    'placeholder' => 'Pilih cost center...',
                                    'searchPlaceholder' => 'Cari kode atau nama cost center...',
                                    'emptyText' => 'Belum ada cost center di perusahaan ini.',
                                ])
                                @error("mappings.$i.cost_center_code")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-1">
                                <label class="{{ $label }}">Akun COA</label>
                                @include('partials.coa-select', [
                                    'path' => "mappings.$i.coa_code",
                                    'options' => $opsi['coas'],
                                    'invalid' => $errors->has("mappings.$i.coa_code"),
                                ])
                                @error("mappings.$i.coa_code")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <span class="{{ $label }}">Posisi</span>
                                <div class="flex items-center gap-2 pt-1">
                                    @foreach ($opsi['kind'] as $val => $teks)
                                        <label wire:key="kind-{{ $m['key'] }}-{{ $val }}">
                                            <input type="radio" value="{{ $val }}"
                                                name="kind-{{ $m['key'] }}"
                                                wire:model="mappings.{{ $i }}.kind" class="peer sr-only">
                                            <span class="{{ $chip }}">{{ $teks }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error("mappings.$i.kind")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="flex flex-col items-center justify-center gap-2.5 rounded-xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
                        <div
                            class="flex size-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                            <span class="icon-[tabler--sitemap-off] size-6 text-slate-400"></span>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Belum ada mapping</p>
                        <p class="text-xs text-slate-400">Produk boleh disimpan tanpa mapping, atau tambahkan
                            sekarang.</p>
                        <button type="button" wire:click="addMapping" class="btn btn-sm btn-success mt-1 gap-1.5">
                            <span class="icon-[tabler--plus] size-4"></span>
                            Tambah Mapping
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ============ TOMBOL SIMPAN ============ --}}
        <div
            class="sticky bottom-0 z-20 -mx-1 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
            <span class="hidden text-xs text-slate-500 dark:text-slate-400 sm:inline">
                {{ count($mappings) }} mapping akan disimpan bersama produk ini
            </span>
            <div class="ms-auto flex items-center gap-2">
                <button type="button" wire:click="backToList"
                    class="btn btn-sm btn-outline btn-secondary">Batal</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="btn btn-sm btn-success gap-1.5">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                        <span class="icon-[tabler--device-floppy] size-4"></span>
                        Simpan
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                        <span class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
