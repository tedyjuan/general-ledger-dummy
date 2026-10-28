<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    private const JT = 1_000_000; // 1 juta

    /** Set false kalau data sudah dari database */
    public bool $demo = true;

    /* [H] RENDER */
    public function render()
    {
        $jt   = self::JT;
        $user = Auth::user();

        /* ---------- LABA RUGI (dummy, Jan - Okt, dalam juta) ---------- */
        $months  = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'];
        $revenue = array_map(fn($v) => $v * $jt, [3200, 3350, 3480, 3390, 3620, 3710, 3590, 3830, 3960, 4270]);
        $expense = array_map(fn($v) => $v * $jt, [2650, 2700, 2780, 2760, 2890, 2950, 2900, 3040, 3120, 3360]);

        $ytdRevenue = array_sum($revenue);
        $ytdExpense = array_sum($expense);
        $netIncome  = $ytdRevenue - $ytdExpense;
        $cogs       = 17500 * $jt;

        /* ---------- NERACA ---------- */
        $assets = [
            ['label' => 'Kas & Bank',         'value' => 8900 * $jt,  'stroke' => 'stroke-emerald-500', 'dot' => 'bg-emerald-500'],
            ['label' => 'Piutang Usaha',      'value' => 12400 * $jt, 'stroke' => 'stroke-sky-500',     'dot' => 'bg-sky-500'],
            ['label' => 'Persediaan',         'value' => 9300 * $jt,  'stroke' => 'stroke-amber-500',   'dot' => 'bg-amber-500'],
            ['label' => 'Aset Tetap (Neto)',  'value' => 15600 * $jt, 'stroke' => 'stroke-violet-500',  'dot' => 'bg-violet-500'],
            ['label' => 'Aset Lainnya',       'value' => 2550 * $jt,  'stroke' => 'stroke-slate-400',   'dot' => 'bg-slate-400'],
        ];
        $totalAssets = array_sum(array_column($assets, 'value'));

        $cum = 0;
        foreach ($assets as &$a) {
            $a['pct']    = round($a['value'] / $totalAssets * 100, 2);
            $a['offset'] = -$cum;
            $cum        += $a['pct'];
        }
        unset($a);

        $totalLiabilities = (9800 + 2100 + 7500 + 1900) * $jt;           // utang usaha, pajak, bank, akrual
        $totalEquity      = (15000 + 5200) * $jt + $netIncome;           // modal + laba ditahan + laba berjalan

        /* ---------- TRIAL BALANCE ---------- */
        $tbDebit  = $totalAssets + $ytdExpense;
        $tbCredit = $totalLiabilities + ($totalEquity - $netIncome) + $ytdRevenue;

        /* ---------- KPI ---------- */
        $kpis = [
            ['label' => 'Total Aset',       'value' => $totalAssets,      'delta' => 2.4, 'good' => true,  'note' => 'vs bulan lalu', 'tone' => 'text-emerald-800 dark:text-emerald-400'],
            ['label' => 'Total Kewajiban',  'value' => $totalLiabilities, 'delta' => 1.1, 'good' => false, 'note' => 'vs bulan lalu', 'tone' => 'text-slate-800 dark:text-white'],
            ['label' => 'Total Ekuitas',    'value' => $totalEquity,      'delta' => 3.6, 'good' => true,  'note' => 'vs bulan lalu', 'tone' => 'text-slate-800 dark:text-white'],
            ['label' => 'Laba Bersih YTD',  'value' => $netIncome,        'delta' => 8.3, 'good' => true,  'note' => 'vs bulan lalu', 'tone' => 'text-emerald-700 dark:text-emerald-400'],
        ];

        /* ---------- GRAFIK PENDAPATAN VS BEBAN (SVG) ---------- */
        $cw = 640;
        $chh = 250;
        $pl = 56;
        $pr = 16;
        $pt = 16;
        $pb = 30;
        $maxY  = ceil(max(max($revenue), max($expense)) / (1000 * $jt)) * 1000 * $jt;
        $stepX = ($cw - $pl - $pr) / (count($months) - 1);
        $yOf   = fn($v) => $pt + ($chh - $pt - $pb) * (1 - $v / $maxY);
        $toPts = function (array $series) use ($pl, $stepX, $yOf) {
            $out = [];
            foreach ($series as $i => $v) {
                $out[] = ['x' => round($pl + $i * $stepX, 1), 'y' => round($yOf($v), 1), 'v' => $v];
            }
            return $out;
        };
        $revPts = $toPts($revenue);
        $expPts = $toPts($expense);
        $line   = fn($p) => implode(' ', array_map(fn($q) => $q['x'] . ',' . $q['y'], $p));
        $baseY  = $chh - $pb;
        $lastX  = $revPts[count($revPts) - 1]['x'];

        $grid = [];
        for ($i = 0; $i <= 4; $i++) {
            $v      = $maxY / 4 * $i;
            $grid[] = [
                'y'     => round($yOf($v), 1),
                'label' => $v == 0 ? '0' : rtrim(rtrim(number_format($v / 1e9, 2, ',', '.'), '0'), ',') . ' M',
            ];
        }

        $chart = [
            'w' => $cw,
            'h' => $chh,
            'pl' => $pl,
            'pr' => $pr,
            'baseY' => $baseY,
            'grid'    => $grid,
            'months'  => $months,
            'revPts'  => $revPts,
            'expPts'  => $expPts,
            'revLine' => $line($revPts),
            'expLine' => $line($expPts),
            'revArea' => 'M' . $revPts[0]['x'] . ',' . $baseY . ' L' . str_replace(' ', ' L', $line($revPts)) . ' L' . $lastX . ',' . $baseY . ' Z',
        ];

        /* ---------- STRUKTUR BEBAN ---------- */
        $expenseBreakdown = [
            ['label' => 'Beban Pokok Penjualan', 'value' => 17500 * $jt, 'bar' => 'bg-emerald-500'],
            ['label' => 'Gaji & Tunjangan',      'value' => 5800 * $jt,  'bar' => 'bg-sky-500'],
            ['label' => 'Pemasaran',             'value' => 1900 * $jt,  'bar' => 'bg-amber-500'],
            ['label' => 'Sewa & Utilitas',       'value' => 1450 * $jt,  'bar' => 'bg-violet-500'],
            ['label' => 'Penyusutan',            'value' => 1100 * $jt,  'bar' => 'bg-rose-400'],
            ['label' => 'Beban Lain-lain',       'value' => 1400 * $jt,  'bar' => 'bg-slate-400'],
        ];
        $expenseTotal = array_sum(array_column($expenseBreakdown, 'value'));

        /* ---------- RASIO KEUANGAN ---------- */
        $currentAssets = (8900 + 12400 + 9300) * $jt;
        $currentLiab   = (9800 + 2100 + 1900) * $jt;
        $fmtNum = fn($n, $d = 2) => number_format($n, $d, ',', '.');

        $ratios = [
            ['label' => 'Rasio Lancar',        'value' => $fmtNum($currentAssets / $currentLiab) . 'x',            'hint' => 'Aset lancar ÷ kewajiban lancar'],
            ['label' => 'Debt to Equity',      'value' => $fmtNum($totalLiabilities / $totalEquity) . 'x',        'hint' => 'Total kewajiban ÷ ekuitas'],
            ['label' => 'Margin Kotor',        'value' => $fmtNum(($ytdRevenue - $cogs) / $ytdRevenue * 100, 1) . '%', 'hint' => '(Pendapatan − HPP) ÷ pendapatan'],
            ['label' => 'Margin Laba Bersih',  'value' => $fmtNum($netIncome / $ytdRevenue * 100, 1) . '%',       'hint' => 'Laba bersih ÷ pendapatan'],
        ];

        /* ---------- AGING PIUTANG & UTANG ---------- */
        $bucketStyle = ['bg-emerald-500', 'bg-amber-400', 'bg-orange-500', 'bg-rose-500', 'bg-rose-700'];
        $bucketLabel = ['Belum jatuh tempo', '1 - 30 hari', '31 - 60 hari', '61 - 90 hari', '> 90 hari'];
        $makeAging = function (array $values) use ($bucketStyle, $bucketLabel, $jt) {
            $rows = [];
            foreach ($values as $i => $v) {
                $rows[] = ['label' => $bucketLabel[$i], 'value' => $v * $jt, 'bar' => $bucketStyle[$i]];
            }
            return ['total' => array_sum(array_column($rows, 'value')), 'max' => max(array_column($rows, 'value')), 'rows' => $rows];
        };
        $aging = [
            'Piutang Usaha (AR)' => $makeAging([6200, 3100, 1900, 800, 400]),
            'Utang Usaha (AP)'   => $makeAging([5100, 2700, 1200, 500, 300]),
        ];

        /* ---------- ARUS KAS (YTD) ---------- */
        $cashFlow = [
            ['label' => 'Aktivitas Operasi', 'value' => 9450 * $jt],
            ['label' => 'Aktivitas Investasi', 'value' => -3200 * $jt],
            ['label' => 'Aktivitas Pendanaan', 'value' => -1100 * $jt],
        ];
        $cashNet     = array_sum(array_column($cashFlow, 'value'));
        $cashEnd     = 8900 * $jt;
        $cashStart   = $cashEnd - $cashNet;

        /* ---------- STATUS JURNAL ---------- */
        $journalStatus = [
            ['label' => 'Posted',               'count' => 1284, 'bar' => 'bg-emerald-500'],
            ['label' => 'Menunggu persetujuan', 'count' => 23,   'bar' => 'bg-amber-500'],
            ['label' => 'Draft',                'count' => 17,   'bar' => 'bg-slate-400'],
            ['label' => 'Reversal / batal',     'count' => 4,    'bar' => 'bg-rose-500'],
        ];
        $journalTotal = array_sum(array_column($journalStatus, 'count'));

        /* ---------- PERIODE AKUNTANSI ---------- */
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $periods = [];
        foreach ($monthNames as $i => $m) {
            $status = $i < 9 ? 'closed' : ($i === 9 ? 'open' : 'upcoming');
            $periods[] = [
                'label'  => $m,
                'status' => $status,
                'cls'    => match ($status) {
                    'closed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400',
                    'open'   => 'border-emerald-600 bg-emerald-600 text-white',
                    default  => 'border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500',
                },
            ];
        }

        $closing = [
            ['label' => 'Trial Balance seimbang',            'done' => true],
            ['label' => 'Penyusutan aset tetap',             'done' => true],
            ['label' => 'Akrual & biaya dibayar dimuka',     'done' => true],
            ['label' => 'Revaluasi mata uang asing',         'done' => true],
            ['label' => 'Rekonsiliasi bank',                 'done' => false],
            ['label' => 'Seluruh jurnal berstatus Posted',   'done' => false],
        ];
        $closingDone = count(array_filter($closing, fn($c) => $c['done']));

        /* ---------- PERLU PERHATIAN ---------- */
        $alerts = [
            ['text' => '23 jurnal menunggu persetujuan',          'dot' => 'bg-amber-500'],
            ['text' => '5 transaksi bank belum direkonsiliasi',   'dot' => 'bg-amber-500'],
            ['text' => '2 akun memiliki saldo tidak normal',      'dot' => 'bg-rose-500'],
            ['text' => 'Periode ' . now()->translatedFormat('F') . ' ditutup ' . (now()->endOfMonth()->day - now()->day) . ' hari lagi', 'dot' => 'bg-sky-500'],
        ];

        /* ---------- JURNAL TERBARU ---------- */
        $journals = [
            ['date' => '26 Okt 2026', 'no' => 'JV-202610-0128', 'desc' => 'Pelunasan piutang PT Maju Bersama',      'src' => 'AR',         'amount' => 15000000,  'status' => 'posted'],
            ['date' => '25 Okt 2026', 'no' => 'JV-202610-0127', 'desc' => 'Pembayaran vendor PT Sinar Logistik',     'src' => 'AP',         'amount' => 42350000,  'status' => 'posted'],
            ['date' => '25 Okt 2026', 'no' => 'JV-202610-0126', 'desc' => 'Beban gaji periode Oktober',              'src' => 'Payroll',    'amount' => 385000000, 'status' => 'pending'],
            ['date' => '24 Okt 2026', 'no' => 'JV-202610-0125', 'desc' => 'Penyusutan aset tetap Oktober',           'src' => 'Aset Tetap', 'amount' => 91200000,  'status' => 'posted'],
            ['date' => '23 Okt 2026', 'no' => 'JV-202610-0124', 'desc' => 'Penyesuaian akrual listrik dan air',      'src' => 'Manual',     'amount' => 18750000,  'status' => 'draft'],
            ['date' => '22 Okt 2026', 'no' => 'JV-202610-0123', 'desc' => 'Penjualan kredit INV-2026-1043',          'src' => 'AR',         'amount' => 128400000, 'status' => 'posted'],
            ['date' => '21 Okt 2026', 'no' => 'JV-202610-0122', 'desc' => 'Koreksi salah klasifikasi akun beban',    'src' => 'Manual',     'amount' => 6500000,   'status' => 'reversed'],
            ['date' => '20 Okt 2026', 'no' => 'JV-202610-0121', 'desc' => 'Setoran kas ke bank',                     'src' => 'Kas & Bank', 'amount' => 50000000,  'status' => 'posted'],
        ];
        $statusMap = [
            'posted'   => ['Posted',   'badge-success'],
            'pending'  => ['Menunggu', 'badge-warning'],
            'draft'    => ['Draft',    'badge-neutral'],
            'reversed' => ['Reversal', 'badge-error'],
        ];

        return view('livewire.dashboard', [
            'user'   => $user,
            'period' => now()->translatedFormat('F Y'),
            'fmt'    => fn($n) => $this->short($n),
            'rp'     => fn($n) => ($n < 0 ? '-' : '') . 'Rp' . number_format(abs($n), 0, ',', '.'),

            'kpis' => $kpis,
            'chart' => $chart,
            'ytdRevenue' => $ytdRevenue,
            'ytdExpense' => $ytdExpense,
            'netIncome' => $netIncome,

            'assets' => $assets,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'tbDebit' => $tbDebit,
            'tbCredit' => $tbCredit,
            'equationBalanced' => $totalAssets === $totalLiabilities + $totalEquity,

            'expenseBreakdown' => $expenseBreakdown,
            'expenseTotal' => $expenseTotal,
            'ratios' => $ratios,
            'aging' => $aging,
            'cashFlow' => $cashFlow,
            'cashNet' => $cashNet,
            'cashStart' => $cashStart,
            'cashEnd' => $cashEnd,
            'journalStatus' => $journalStatus,
            'journalTotal' => $journalTotal,
            'periods' => $periods,
            'closing' => $closing,
            'closingDone' => $closingDone,
            'alerts' => $alerts,
            'journals' => $journals,
            'statusMap' => $statusMap,
        ]);
    }

    /** Format ringkas: Rp48,75 M (miliar), Rp8,9 Jt (juta) */
    private function short(float|int $n): string
    {
        $abs  = abs($n);
        $sign = $n < 0 ? '-' : '';

        [$div, $suffix] = match (true) {
            $abs >= 1e12 => [1e12, ' T'],
            $abs >= 1e9  => [1e9, ' M'],
            $abs >= 1e6  => [1e6, ' Jt'],
            default      => [1, ''],
        };

        $value = rtrim(rtrim(number_format($abs / $div, 2, ',', '.'), '0'), ',');

        return $sign . 'Rp' . $value . $suffix;
    }
}
