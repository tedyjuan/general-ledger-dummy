<div>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                    Dashboard Buku Besar
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Selamat datang, {{ $user->name }} &middot; Periode {{ $period }}
                    <span class="badge badge-soft badge-success ml-1 align-middle">Open</span>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button"
                    class="btn btn-outline btn-sm border-emerald-200 bg-white text-emerald-800 hover:bg-emerald-50 dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-400 dark:hover:bg-slate-800">
                    Export PDF
                </button>

                <button type="button"
                    class="btn btn-sm border-emerald-600 bg-emerald-600 text-white hover:border-emerald-700 hover:bg-emerald-700">
                    + Jurnal Baru
                </button>
            </div>
        </div>
    </x-slot>

    @if ($demo)
        <div role="alert" class="alert alert-soft alert-info mb-6">
            <span>Angka pada dashboard ini adalah <strong>data contoh (dummy)</strong> dan belum terhubung ke transaksi
                sebenarnya. Satuan: M = miliar, Jt = juta rupiah.</span>
        </div>
    @endif

    @if ($user->is_password_default)
        <div role="alert" class="alert alert-soft alert-warning mb-6">
            <span>Anda masih memakai password default. Segera ganti password demi keamanan akun.</span>
        </div>
    @endif

    {{-- KPI --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $k)
            <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="card-body gap-2 p-5">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $k['label'] }}</p>
                    <h2 class="text-2xl font-bold {{ $k['tone'] }}">{{ $fmt($k['value']) }}</h2>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-soft {{ $k['good'] ? 'badge-success' : 'badge-warning' }}">
                            {{ $k['delta'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($k['delta']), 1, ',', '.') }}%
                        </span>
                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $k['note'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- BARIS A: GRAFIK + TRIAL BALANCE --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-3">

        {{-- Pendapatan vs Beban --}}
        <div
            class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
            <div
                class="flex flex-col gap-3 border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-800 dark:text-white">Pendapatan vs Beban</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tren bulanan Januari - Oktober
                        {{ now()->year }}</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5"><span
                            class="size-2.5 rounded-full bg-emerald-500"></span>Pendapatan</span>
                    <span class="flex items-center gap-1.5"><span
                            class="size-2.5 rounded-full bg-rose-400"></span>Beban</span>
                </div>
            </div>

            <div class="p-4">
                <svg viewBox="0 0 {{ $chart['w'] }} {{ $chart['h'] }}" class="h-auto w-full" role="img"
                    aria-label="Grafik pendapatan dan beban bulanan">
                    <defs>
                        <linearGradient id="revFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.28" />
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                        </linearGradient>
                    </defs>

                    @foreach ($chart['grid'] as $g)
                        <line x1="{{ $chart['pl'] }}" x2="{{ $chart['w'] - $chart['pr'] }}" y1="{{ $g['y'] }}"
                            y2="{{ $g['y'] }}" stroke-dasharray="3 4"
                            class="stroke-slate-200 dark:stroke-slate-800" />
                        <text x="{{ $chart['pl'] - 8 }}" y="{{ $g['y'] + 3.5 }}" text-anchor="end"
                            class="fill-slate-400 dark:fill-slate-500"
                            style="font-size:10px">{{ $g['label'] }}</text>
                    @endforeach

                    <path d="{{ $chart['revArea'] }}" fill="url(#revFill)" />
                    <polyline points="{{ $chart['revLine'] }}" fill="none" stroke-width="2.5"
                        stroke-linejoin="round" stroke-linecap="round" class="stroke-emerald-500" />
                    <polyline points="{{ $chart['expLine'] }}" fill="none" stroke-width="2.5"
                        stroke-linejoin="round" stroke-linecap="round" class="stroke-rose-400" />

                    @foreach ($chart['revPts'] as $i => $p)
                        <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3.5"
                            class="fill-white stroke-emerald-500 dark:fill-slate-900" stroke-width="2">
                            <title>{{ $chart['months'][$i] }} - Pendapatan {{ $rp($p['v']) }}</title>
                        </circle>
                        <circle cx="{{ $chart['expPts'][$i]['x'] }}" cy="{{ $chart['expPts'][$i]['y'] }}" r="3.5"
                            class="fill-white stroke-rose-400 dark:fill-slate-900" stroke-width="2">
                            <title>{{ $chart['months'][$i] }} - Beban {{ $rp($chart['expPts'][$i]['v']) }}</title>
                        </circle>
                        <text x="{{ $p['x'] }}" y="{{ $chart['h'] - 8 }}" text-anchor="middle"
                            class="fill-slate-400 dark:fill-slate-500"
                            style="font-size:10px">{{ $chart['months'][$i] }}</text>
                    @endforeach
                </svg>
            </div>

            <div
                class="grid grid-cols-3 divide-x divide-slate-100 border-t border-slate-100 dark:divide-slate-800 dark:border-slate-800">
                <div class="p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pendapatan YTD</p>
                    <p class="mt-1 font-bold text-emerald-700 dark:text-emerald-400">{{ $fmt($ytdRevenue) }}</p>
                </div>
                <div class="p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Beban YTD</p>
                    <p class="mt-1 font-bold text-rose-600 dark:text-rose-400">{{ $fmt($ytdExpense) }}</p>
                </div>
                <div class="p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Laba Bersih YTD</p>
                    <p class="mt-1 font-bold text-slate-800 dark:text-white">{{ $fmt($netIncome) }}</p>
                </div>
            </div>
        </div>

        {{-- Kolom kanan --}}
        <div class="flex flex-col gap-6">

            {{-- Trial balance --}}
            <div
                class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <div>
                        <h2 class="font-semibold text-slate-800 dark:text-white">Status Trial Balance</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Per
                            {{ now()->translatedFormat('d F Y') }}</p>
                    </div>
                    @if ($tbDebit === $tbCredit)
                        <span class="badge badge-soft badge-success">Seimbang</span>
                    @else
                        <span class="badge badge-soft badge-error">Tidak seimbang</span>
                    @endif
                </div>

                <dl class="divide-y divide-slate-100 px-4 text-sm dark:divide-slate-800">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">Total Debit</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $rp($tbDebit) }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">Total Kredit</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $rp($tbCredit) }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">Selisih</dt>
                        <dd class="font-semibold text-emerald-700 dark:text-emerald-400">
                            {{ $rp($tbDebit - $tbCredit) }}</dd>
                    </div>
                </dl>

                <div class="m-4 rounded-lg bg-emerald-50/60 p-3 text-xs dark:bg-slate-800/60">
                    <p class="mb-2 font-medium text-slate-600 dark:text-slate-300">
                        Persamaan akuntansi {{ $equationBalanced ? '✓' : '✗' }}
                    </p>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-slate-600 dark:text-slate-300">
                        <span><strong>Aset</strong> {{ $fmt($totalAssets) }}</span>
                        <span>=</span>
                        <span><strong>Kewajiban</strong> {{ $fmt($totalLiabilities) }}</span>
                        <span>+</span>
                        <span><strong>Ekuitas</strong> {{ $fmt($totalEquity) }}</span>
                    </div>
                </div>
            </div>

            {{-- Perlu perhatian --}}
            <div
                class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <h2 class="font-semibold text-slate-800 dark:text-white">Perlu Perhatian</h2>
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($alerts as $a)
                        <li class="flex items-center gap-3 px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            <span class="size-2 shrink-0 rounded-full {{ $a['dot'] }}"></span>
                            {{ $a['text'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- BARIS B: KOMPOSISI ASET, STRUKTUR BEBAN, RASIO --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-3">

        {{-- Komposisi aset --}}
        <div
            class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <h2 class="font-semibold text-slate-800 dark:text-white">Komposisi Aset</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Posisi per akhir {{ $period }}</p>
            </div>

            <div class="flex flex-col items-center gap-4 p-4 sm:flex-row lg:flex-col xl:flex-row">
                <div class="relative size-36 shrink-0">
                    <svg viewBox="0 0 36 36" class="size-full">
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="4"
                            class="stroke-slate-100 dark:stroke-slate-800" />
                        @foreach ($assets as $a)
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="4"
                                stroke-dasharray="{{ $a['pct'] }} {{ 100 - $a['pct'] }}"
                                stroke-dashoffset="{{ $a['offset'] }}"
                                style="transform: rotate(-90deg); transform-origin: 50% 50%;"
                                class="{{ $a['stroke'] }}">
                                <title>{{ $a['label'] }} - {{ $fmt($a['value']) }}</title>
                            </circle>
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-[10px] text-slate-400">Total Aset</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-white">{{ $fmt($totalAssets) }}</span>
                    </div>
                </div>

                <ul class="w-full space-y-2 text-sm">
                    @foreach ($assets as $a)
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <span class="size-2.5 rounded-full {{ $a['dot'] }}"></span>{{ $a['label'] }}
                            </span>
                            <span
                                class="font-medium text-slate-800 dark:text-slate-200">{{ number_format($a['pct'], 1, ',', '.') }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Struktur beban --}}
        <div
            class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <h2 class="font-semibold text-slate-800 dark:text-white">Struktur Beban</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Total {{ $fmt($expenseTotal) }} (YTD)</p>
            </div>

            <div class="space-y-3.5 p-4">
                @foreach ($expenseBreakdown as $e)
                    @php $pct = $e['value'] / $expenseTotal * 100; @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-300">{{ $e['label'] }}</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">
                                {{ $fmt($e['value']) }}
                                <span
                                    class="ml-1 text-xs font-normal text-slate-400">{{ number_format($pct, 1, ',', '.') }}%</span>
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $e['bar'] }}"
                                style="width: {{ round($pct) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Rasio --}}
        <div
            class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <h2 class="font-semibold text-slate-800 dark:text-white">Rasio Keuangan</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Likuiditas, solvabilitas, profitabilitas</p>
            </div>

            <div class="grid grid-cols-2 gap-3 p-4">
                @foreach ($ratios as $r)
                    <div class="rounded-xl border border-emerald-100 p-3 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $r['label'] }}</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-400">{{ $r['value'] }}
                        </p>
                        <p class="mt-1 text-[11px] leading-snug text-slate-400 dark:text-slate-500">
                            {{ $r['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- BARIS C: AGING AR/AP + ARUS KAS --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-3">

        @foreach ($aging as $title => $ag)
            <div
                class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <h2 class="font-semibold text-slate-800 dark:text-white">Aging {{ $title }}</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Total {{ $fmt($ag['total']) }}</p>
                </div>

                <div class="space-y-3.5 p-4">
                    @foreach ($ag['rows'] as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-300">{{ $row['label'] }}</span>
                                <span
                                    class="font-medium text-slate-800 dark:text-slate-200">{{ $fmt($row['value']) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $row['bar'] }}"
                                    style="width: {{ round(($row['value'] / $ag['max']) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Arus kas --}}
        <div
            class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <h2 class="font-semibold text-slate-800 dark:text-white">Ringkasan Arus Kas</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Januari - Oktober {{ now()->year }}</p>
            </div>

            <dl class="divide-y divide-slate-100 px-4 text-sm dark:divide-slate-800">
                <div class="flex justify-between py-2.5">
                    <dt class="text-slate-500 dark:text-slate-400">Saldo awal kas</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $fmt($cashStart) }}</dd>
                </div>
                @foreach ($cashFlow as $c)
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">{{ $c['label'] }}</dt>
                        <dd
                            class="font-medium {{ $c['value'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $c['value'] >= 0 ? '+' : '' }}{{ $fmt($c['value']) }}
                        </dd>
                    </div>
                @endforeach
                <div class="flex justify-between py-2.5">
                    <dt class="text-slate-500 dark:text-slate-400">Kenaikan kas bersih</dt>
                    <dd class="font-semibold text-slate-800 dark:text-white">
                        {{ $cashNet >= 0 ? '+' : '' }}{{ $fmt($cashNet) }}</dd>
                </div>
                <div class="flex justify-between bg-emerald-50/50 py-3 dark:bg-slate-800/50 -mx-4 px-4">
                    <dt class="font-medium text-slate-700 dark:text-slate-200">Saldo akhir kas</dt>
                    <dd class="font-bold text-emerald-700 dark:text-emerald-400">{{ $fmt($cashEnd) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- BARIS D: STATUS JURNAL + PERIODE --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-3">

        {{-- Status jurnal --}}
        <div
            class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <h2 class="font-semibold text-slate-800 dark:text-white">Status Jurnal</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ number_format($journalTotal, 0, ',', '.') }} jurnal tahun berjalan</p>
            </div>

            <div class="p-4">
                <div class="flex h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    @foreach ($journalStatus as $s)
                        <div class="{{ $s['bar'] }}" style="width: {{ ($s['count'] / $journalTotal) * 100 }}%"
                            title="{{ $s['label'] }}"></div>
                    @endforeach
                </div>

                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($journalStatus as $s)
                        <li class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <span class="size-2.5 rounded-full {{ $s['bar'] }}"></span>{{ $s['label'] }}
                            </span>
                            <span
                                class="font-medium text-slate-800 dark:text-slate-200">{{ number_format($s['count'], 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Periode akuntansi --}}
        <div
            class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
            <div
                class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <div>
                    <h2 class="font-semibold text-slate-800 dark:text-white">Periode Akuntansi & Penutupan</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tahun fiskal {{ now()->year }}</p>
                </div>
                <span class="badge badge-soft badge-warning">{{ $closingDone }}/{{ count($closing) }} tugas
                    selesai</span>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-6 gap-2 sm:grid-cols-12">
                    @foreach ($periods as $p)
                        <div class="rounded-lg border py-2 text-center text-xs font-medium {{ $p['cls'] }}">
                            {{ $p['label'] }}</div>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5"><span
                            class="size-2.5 rounded bg-emerald-200 dark:bg-emerald-700"></span>Ditutup</span>
                    <span class="flex items-center gap-1.5"><span
                            class="size-2.5 rounded bg-emerald-600"></span>Terbuka</span>
                    <span class="flex items-center gap-1.5"><span
                            class="size-2.5 rounded bg-slate-200 dark:bg-slate-700"></span>Belum dibuka</span>
                </div>

                <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-200">Checklist penutupan
                            {{ now()->translatedFormat('F') }}</span>
                        <span
                            class="text-slate-500 dark:text-slate-400">{{ round(($closingDone / count($closing)) * 100) }}%</span>
                    </div>
                    <div class="mb-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-emerald-500"
                            style="width: {{ ($closingDone / count($closing)) * 100 }}%"></div>
                    </div>

                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($closing as $c)
                            <li
                                class="flex items-center gap-2.5 text-sm {{ $c['done'] ? 'text-slate-600 dark:text-slate-300' : 'text-slate-500 dark:text-slate-400' }}">
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold
                                    {{ $c['done'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' }}">
                                    {{ $c['done'] ? '✓' : '!' }}
                                </span>
                                {{ $c['label'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL JURNAL TERBARU --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div
            class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">Jurnal Terbaru</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Transaksi jurnal umum terakhir</p>
            </div>
            <button type="button"
                class="btn btn-sm btn-outline border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-slate-800">
                Lihat Semua
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Jurnal</th>
                        <th>Keterangan</th>
                        <th>Sumber</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($journals as $j)
                        <tr class="hover:bg-emerald-50/40 dark:hover:bg-slate-800/60">
                            <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $j['date'] }}</td>
                            <td class="whitespace-nowrap font-medium text-emerald-700 dark:text-emerald-400">
                                {{ $j['no'] }}</td>
                            <td class="text-slate-600 dark:text-slate-300">{{ $j['desc'] }}</td>
                            <td class="whitespace-nowrap text-slate-500 dark:text-slate-400">{{ $j['src'] }}</td>
                            <td class="whitespace-nowrap text-right font-medium text-slate-700 dark:text-slate-200">
                                {{ $rp($j['amount']) }}</td>
                            <td class="whitespace-nowrap text-right font-medium text-slate-700 dark:text-slate-200">
                                {{ $rp($j['amount']) }}</td>
                            <td class="text-center">
                                <span
                                    class="badge badge-soft {{ $statusMap[$j['status']][1] }}">{{ $statusMap[$j['status']][0] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 p-4 dark:border-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Menampilkan {{ count($journals) }} dari {{ number_format($journalTotal, 0, ',', '.') }} jurnal
            </p>
        </div>
    </div>
</div>
