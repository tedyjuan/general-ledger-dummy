<div x-data="{
    resetOpen: false,
    // format angka: 1234567 -> 1.234.567 (minus hanya jika neg = true)
    fmt(v, neg) {
        v = String(v);
        const minus = neg && v.trim().startsWith('-');
        const digit = v.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        if (digit === '') return minus ? '-' : '';
        return (minus ? '-' : '') + digit.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    raw(v) {
        const r = String(v).replace(/\./g, '');
        return (r === '' || r === '-') ? '0' : r;
    },
    ketik(e, path, neg) {
        const el = e.target;
        const sebelum = el.value.slice(0, el.selectionStart).replace(/\D/g, '').length;

        el.value = this.fmt(el.value, neg);

        // kursor tetap di posisi yang benar
        let n = 0,
            pos = 0;
        while (pos < el.value.length && n < sebelum) {
            if (/\d/.test(el.value[pos])) n++;
            pos++;
        }
        el.setSelectionRange(pos, pos);

        // angka murni ke Livewire, tanpa request
        $wire.set(path, this.raw(el.value), false);
    },
    selesai(e, path) {
        const el = e.target;
        if (el.value === '' || el.value === '-') el.value = '0';
        $wire.set(path, this.raw(el.value), false);
        // hitung ulang Closing Balance & Total
        $wire.$refresh();
    },
}" @keydown.escape.window="resetOpen = false">
    @php
        $user = \Illuminate\Support\Facades\Auth::user();
        $bisaEdit = (bool) $user?->izin('account-balance', 'update');

        $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $label = 'mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200';
        $inputRo =
            'input w-full cursor-not-allowed bg-slate-100 text-right font-mono tabular-nums text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300';
        $inputEd =
            'input w-full bg-white ps-10 text-right font-mono tabular-nums dark:border-slate-700 dark:bg-slate-900 dark:text-white';

        // nilai state (angka murni) -> tampilan 1.234.567
        $fmt = fn($v) => number_format((int) str_replace('.', '', (string) $v), 0, ',', '.');

        $akunTerpilih = $accounts->firstWhere('account_number', $selectedCoa);
    @endphp

    {{-- ============ HEADER HALAMAN ============ --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Account Balance Setup</h1>
        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400">
                <span class="icon-[tabler--building] size-3.5"></span>
                {{ $activeCompany }}
            </span>
            <span>Atur saldo awal dan mutasi debit/kredit per bulan untuk tiap akun</span>
        </div>
    </div>

    {{-- ============ FILTER ============ --}}
    <div class="card card-border mb-6 border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form wire:submit="cari" class="grid items-start gap-4 p-4 sm:grid-cols-2 lg:grid-cols-[10rem_1fr_1fr_auto]">

            {{-- "inert" menonaktifkan semua klik dan fokus di dalamnya, termasuk dropdown custom --}}
            <div @if ($searched) inert @endif class="{{ $searched ? 'opacity-60' : '' }}">
                <label for="year" class="{{ $label }}">Tahun</label>
                <select id="year" wire:model="year" @disabled($searched)
                    class="select w-full bg-white disabled:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-white {{ $errors->has('year') ? 'border-red-400' : '' }}">
                    @foreach ($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
                @error('year')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div @if ($searched) inert @endif class="{{ $searched ? 'opacity-60' : '' }}">
                <label class="{{ $label }}">Depo</label>
                @include('partials.coa-select', [
                    'path' => 'depo_code',
                    'options' => $depoOptions,
                    'invalid' => $errors->has('depo_code'),
                    'placeholder' => 'Pilih depo...',
                    'searchPlaceholder' => 'Cari kode atau nama depo...',
                    'emptyText' => 'Belum ada depo di perusahaan ini.',
                ])
                @error('depo_code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div @if ($searched) inert @endif class="{{ $searched ? 'opacity-60' : '' }}">
                <label class="{{ $label }}">Cost Center</label>
                @include('partials.coa-select', [
                    'path' => 'cost_center_code',
                    'options' => $ccOptions,
                    'invalid' => $errors->has('cost_center_code'),
                    'placeholder' => 'Pilih cost center...',
                    'searchPlaceholder' => 'Cari kode atau nama cost center...',
                    'emptyText' => 'Belum ada cost center di perusahaan ini.',
                ])
                @error('cost_center_code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:pt-7">
                @if ($searched)
                    <button type="button" @click="resetOpen = true"
                        class="btn btn-outline btn-error w-full gap-2 shadow-xs lg:w-auto" title="Reset pencarian"
                        aria-label="Reset pencarian">
                        <span class="icon-[tabler--refresh] size-5"></span>
                        Reset
                    </button>
                @else
                    <button type="submit" wire:loading.attr="disabled" wire:target="cari"
                        class="btn btn-success w-full gap-2 shadow-xs lg:w-auto" title="Cari akun"
                        aria-label="Cari akun">
                        <span wire:loading.remove wire:target="cari" class="icon-[tabler--search] size-5"></span>
                        <span wire:loading wire:target="cari"
                            class="icon-[tabler--loader-2] size-5 animate-spin"></span>
                        <span class="lg:hidden">Cari</span>
                    </button>
                @endif
            </div>
        </form>
    </div>

    @if (!$searched)
        <div
            class="flex flex-col items-center justify-center gap-2.5 rounded-xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-700">
            <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                <span class="icon-[tabler--filter-search] size-7 text-slate-400"></span>
            </div>
            <p class="text-base font-semibold text-slate-700 dark:text-slate-300">Pilih tahun, depo, dan cost center
            </p>
            <p class="text-xs text-slate-400">Lalu klik tombol cari untuk menampilkan daftar akun.</p>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">

            {{-- ============ KIRI: DAFTAR AKUN ============ --}}
            <div
                class="card card-border overflow-hidden border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-3 dark:border-slate-800">
                    <div class="relative">
                        <span
                            class="icon-[tabler--search] pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
                        <input type="search" wire:model.live.debounce.300ms="accountSearch"
                            placeholder="Cari account number / nama..."
                            class="input input-sm w-full bg-white ps-9 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $accounts->count() }}</span>
                        akun
                        @if ($accounts->count() >= $maxAccounts)
                            (dibatasi {{ $maxAccounts }}, persempit pencarian)
                        @endif
                        &middot; <span class="inline-block size-2 rounded-full bg-emerald-500 align-middle"></span>
                        sudah ada data saldo
                    </p>
                </div>

                <div class="max-h-[34rem] overflow-y-auto" wire:loading.class="opacity-60"
                    wire:target="accountSearch, selectAccount">
                    <table class="table table-sm">
                        <thead
                            class="sticky top-0 z-10 bg-sky-100 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <tr>
                                <th>Account No</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($accounts as $a)
                                @php $aktif = $selectedCoa === (string) $a->account_number; @endphp
                                <tr wire:key="acc-{{ $a->account_number }}"
                                    wire:click="selectAccount('{{ $a->account_number }}')"
                                    class="cursor-pointer transition-colors {{ $aktif ? 'bg-emerald-50 dark:bg-emerald-950/30' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                                    <td class="whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="size-2 shrink-0 rounded-full {{ isset($hasBalance[(string) $a->account_number]) ? 'bg-emerald-500' : 'bg-transparent' }}"></span>
                                            <span
                                                class="font-mono text-sm tabular-nums {{ $aktif ? 'font-semibold text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-200' }}">{{ $a->account_number }}</span>
                                        </div>
                                    </td>
                                    <td class="text-slate-700 dark:text-slate-200">{{ $a->name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="p-8 text-center text-sm text-slate-500">
                                        Tidak ada akun ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ============ KANAN: SALDO AKUN ============ --}}
            <div class="card card-border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @if ($selectedCoa === '')
                    <div class="flex flex-col items-center justify-center gap-2.5 p-12 text-center">
                        <div
                            class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                            <span class="icon-[tabler--pointer] size-7 text-slate-400"></span>
                        </div>
                        <p class="text-base font-semibold text-slate-700 dark:text-slate-300">Pilih salah satu akun</p>
                        <p class="text-xs text-slate-400">Klik baris pada daftar akun untuk melihat saldonya.</p>
                    </div>
                @else
                    {{-- Ringkasan akun & filter --}}
                    <div
                        class="flex flex-col gap-2 border-b border-slate-100 p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold text-slate-800 dark:text-white">
                                <span
                                    class="font-mono text-emerald-700 dark:text-emerald-400">{{ $selectedCoa }}</span>
                                &ndash; {{ $akunTerpilih->name ?? '' }}
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                Tahun {{ $aYear }} &middot; {{ $aDepo }} {{ $depoNama }} &middot;
                                {{ $aCc }} {{ $ccNama }}
                            </p>
                        </div>
                        @if ($editing)
                            <span class="badge badge-soft badge-warning badge-sm">Mode edit</span>
                        @endif
                    </div>

                    <div class="space-y-5 p-4" wire:loading.class="opacity-60" wire:target="selectAccount">

                        {{-- Opening & Closing --}}
                        <div class="grid items-center gap-3 sm:grid-cols-[9rem_1fr_auto]">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Opening
                                Balance</label>

                            @if ($editing)
                                <div>
                                    <div class="relative">
                                        <span
                                            class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
                                        <input type="text" inputmode="text" autocomplete="off"
                                            value="{{ $fmt($opening) }}" @input="ketik($event, 'opening', true)"
                                            @blur="selesai($event, 'opening')" @focus="$el.select()"
                                            class="{{ $inputEd }} {{ $errors->has('opening') ? 'border-red-400' : '' }}">
                                    </div>
                                    @error('opening')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <input type="text" disabled class="{{ $inputRo }}"
                                    value="Rp {{ number_format($openingNum, 0, ',', '.') }}">
                            @endif

                            <div>
                                @if ($bisaEdit && !$editing)
                                    <button type="button" wire:click="startEdit" wire:loading.attr="disabled"
                                        wire:target="startEdit" class="btn btn-square btn-primary" title="Edit saldo"
                                        aria-label="Edit saldo">
                                        <span class="icon-[tabler--pencil] size-4"></span>
                                    </button>
                                @endif
                            </div>

                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Closing
                                Balance</label>
                            <input type="text" disabled class="{{ $inputRo }}"
                                value="Rp {{ number_format($closing, 0, ',', '.') }}">
                            <div></div>
                        </div>

                        {{-- Debit / Credit per bulan --}}
                        <div>
                            <div
                                class="grid grid-cols-[7rem_1fr_1fr] items-center gap-3 pb-2 text-center text-sm font-bold text-slate-800 dark:text-white">
                                <span></span>
                                <span>DEBIT</span>
                                <span>CREDIT</span>
                            </div>

                            <div class="space-y-2">
                                @foreach ($bulan as $no => $nama)
                                    <div wire:key="bln-{{ $no }}"
                                        class="grid grid-cols-[7rem_1fr_1fr] items-start gap-3">
                                        <div class="pt-2 text-sm text-slate-700 dark:text-slate-200">
                                            <span class="inline-block w-5 font-semibold">{{ $no }}</span>
                                            <span
                                                class="text-xs text-slate-500 dark:text-slate-400">{{ $nama }}</span>
                                        </div>

                                        @if ($editing)
                                            <div>
                                                <div class="relative">
                                                    <span
                                                        class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
                                                    <input type="text" inputmode="numeric" autocomplete="off"
                                                        value="{{ $fmt($debit[$no] ?? 0) }}"
                                                        @input="ketik($event, 'debit.{{ $no }}', false)"
                                                        @blur="selesai($event, 'debit.{{ $no }}')"
                                                        @focus="$el.select()"
                                                        class="{{ $inputEd }} {{ $errors->has("debit.$no") ? 'border-red-400' : '' }}">
                                                </div>
                                                @error("debit.$no")
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <div class="relative">
                                                    <span
                                                        class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
                                                    <input type="text" inputmode="numeric" autocomplete="off"
                                                        value="{{ $fmt($credit[$no] ?? 0) }}"
                                                        @input="ketik($event, 'credit.{{ $no }}', false)"
                                                        @blur="selesai($event, 'credit.{{ $no }}')"
                                                        @focus="$el.select()"
                                                        class="{{ $inputEd }} {{ $errors->has("credit.$no") ? 'border-red-400' : '' }}">
                                                </div>
                                                @error("credit.$no")
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @else
                                            <input type="text" disabled class="{{ $inputRo }}"
                                                value="Rp {{ number_format($debitNum[$no], 0, ',', '.') }}">
                                            <input type="text" disabled class="{{ $inputRo }}"
                                                value="Rp {{ number_format($creditNum[$no], 0, ',', '.') }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Total --}}
                            <div
                                class="mt-3 grid grid-cols-[7rem_1fr_1fr] items-center gap-3 border-t border-slate-200 pt-3 dark:border-slate-700">
                                <span class="text-sm font-bold text-slate-800 dark:text-white">Total</span>
                                <span
                                    class="px-3 text-right font-mono text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-400">Rp
                                    {{ number_format($totalDebit, 0, ',', '.') }}</span>
                                <span
                                    class="px-3 text-right font-mono text-sm font-bold tabular-nums text-rose-700 dark:text-rose-400">Rp
                                    {{ number_format($totalCredit, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol simpan (mode edit) --}}
                    @if ($editing)
                        <div
                            class="flex items-center justify-between gap-3 border-t border-slate-100 p-3 dark:border-slate-800">
                            <span class="hidden text-xs text-slate-500 dark:text-slate-400 sm:inline">
                                Opening balance bulan berikutnya dihitung otomatis dari saldo bulan sebelumnya.
                            </span>
                            <div class="ms-auto flex items-center gap-2">
                                <button type="button" wire:click="cancelEdit"
                                    class="btn btn-sm btn-outline btn-secondary">Batal</button>
                                <button type="button" wire:click="save" wire:loading.attr="disabled"
                                    wire:target="save" class="btn btn-sm btn-success gap-1.5">
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
                    @endif
                @endif
            </div>
        </div>
    @endif
    {{-- ============ MODAL KONFIRMASI RESET ============ --}}
    @if ($searched)
        <div x-show="resetOpen" x-cloak x-transition.opacity.duration.150ms
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
            @click.self="resetOpen = false" role="dialog" aria-modal="true" aria-labelledby="reset-title">
            <div
                class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                        <span class="icon-[tabler--refresh-alert] size-6"></span>
                    </div>
                    <div>
                        <h3 id="reset-title" class="text-lg font-bold text-slate-800 dark:text-white">Reset Pencarian
                        </h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Apakah Anda yakin ingin mereset pencarian? Filter tahun, depo, dan cost center akan
                            dibuka kembali, dan daftar akun akan dikosongkan.
                        </p>
                        @if ($editing)
                            <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">
                                Perubahan saldo yang belum disimpan akan hilang.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" @click="resetOpen = false"
                        class="btn btn-sm btn-outline btn-secondary">Batal</button>
                    <button type="button" wire:loading.attr="disabled" wire:target="resetCari"
                        @click="$wire.resetCari().then(() => resetOpen = false)"
                        class="btn btn-sm flex items-center gap-1.5 bg-amber-600 text-white hover:bg-amber-700">
                        <span wire:loading.remove wire:target="resetCari" class="flex items-center gap-1.5">
                            <span class="icon-[tabler--refresh] size-4"></span>
                            Ya, Reset
                        </span>
                        <span wire:loading wire:target="resetCari" class="flex items-center gap-1.5">
                            <span class="icon-[tabler--loader-2] size-4 animate-spin"></span>
                            Mereset...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
