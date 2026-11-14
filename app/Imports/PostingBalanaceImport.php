<?php

namespace App\Imports;

use App\Models\PostingBalance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Validators\Failure;

class PostingBalanaceImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithUpsertColumns,
    WithValidation,
    SkipsEmptyRows,
    SkipsOnFailure,
    WithBatchInserts,
    WithEvents
{
    use RemembersRowNumber;

    public const REQUIRED_HEADERS = [
        'company_code',
        'depo_code',
        'year',
        'period',
        'cost_center_code',
        'coa_code',
    ];

    private array $companies = [];

    // Lookup exact : key(company, code)             => kode asli DB
    // Lookup norm  : key(company, ltrim(code, '0')) => kode asli DB
    private array $depos = [];
    private array $deposNorm = [];
    private array $coas = [];
    private array $coasNorm = [];
    private array $costCenters = [];
    private array $costCentersNorm = [];

    private array $failedRows = [];
    private int $successCount = 0;

    public function __construct()
    {
        $this->companies = DB::table('companies')->pluck('code')
            ->mapWithKeys(fn($c) => [$this->key($c) => true])->all();

        [$this->depos, $this->deposNorm] = $this->buildLookup(
            DB::table('depos')->select('company_code', 'code')->get(),
            'code'
        );

        [$this->coas, $this->coasNorm] = $this->buildLookup(
            DB::table('chart_of_accounts')->select('company_code', 'account_number')->get(),
            'account_number'
        );

        [$this->costCenters, $this->costCentersNorm] = $this->buildLookup(
            DB::table('cost_centers')->select('company_code', 'cost_center')->get(),
            'cost_center'
        );
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /** Angka 100303.0 -> "100303", buang spasi & karakter tersembunyi. */
    private function clean(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        $value = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', '', (string) $value);

        return trim($value);
    }

    private function key(mixed ...$parts): string
    {
        return mb_strtolower(implode('|', array_map(fn($p) => $this->clean($p), $parts)));
    }

    /** Hilangkan nol di depan: "01999999" -> "1999999". Semua nol -> "0". */
    private function stripZero(string $code): string
    {
        $s = ltrim($code, '0');

        return $s === '' ? '0' : $s;
    }

    /**
     * Bangun 2 lookup: exact & tanpa nol di depan.
     * Value = kode asli dari database (supaya nol di depan tetap terjaga).
     *
     * @return array{0: array, 1: array}
     */
    private function buildLookup($rows, string $codeField): array
    {
        $exact = [];
        $norm  = [];

        foreach ($rows as $r) {
            $real = $this->clean($r->{$codeField});

            $exact[$this->key($r->company_code, $real)] = $real;

            $nKey = $this->key($r->company_code, $this->stripZero($real));
            // jangan timpa kalau sudah ada (hindari ambigu, ambil yang pertama)
            $norm[$nKey] ??= $real;
        }

        return [$exact, $norm];
    }

    /** Cari kode asli di master. Exact dulu, lalu fallback tanpa nol di depan. */
    private function resolve(array $exact, array $norm, string $company, string $code): ?string
    {
        $k = $this->key($company, $code);

        if (isset($exact[$k])) {
            return $exact[$k];
        }

        $nk = $this->key($company, $this->stripZero($this->clean($code)));

        return $norm[$nk] ?? null;
    }

    /** "-1.234", "(1.234)", "1.234-", "Rp -5.000", -5000, -5000.0 -> int. Kosong -> 0. */
    private function toInt(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $s = trim((string) $value);

        // minus: tanda "-" di mana pun (termasuk unicode minus / en dash) atau dalam kurung
        $negatif = preg_match('/^\(.*\)$/', $s) === 1
            || preg_match('/[-\x{2212}\x{2013}]/u', $s) === 1;

        $digit = preg_replace('/\D/', '', $s);

        if ($digit === '') {
            return 0;
        }

        $n = (int) $digit;

        return $negatif ? -$n : $n;
    }

    private function fail(array $row, string $reason): void
    {
        $this->failedRows[] = [
            'excel_row'        => $this->getRowNumber(),
            'reason'           => $reason,
            'company_code'     => $this->clean($row['company_code'] ?? ''),
            'depo_code'        => $this->clean($row['depo_code'] ?? ''),
            'year'             => $this->clean($row['year'] ?? ''),
            'period'           => $this->clean($row['period'] ?? ''),
            'cost_center_code' => $this->clean($row['cost_center_code'] ?? ''),
            'coa_code'         => $this->clean($row['coa_code'] ?? ''),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Import                                                             */
    /* ------------------------------------------------------------------ */

    public function prepareForValidation($data, $index)
    {
        foreach (self::REQUIRED_HEADERS as $k) {
            $data[$k] = $this->clean($data[$k] ?? '');
        }

        return $data;
    }

    public function model(array $row): Model|array|null
    {
        $company = $this->clean($row['company_code']);
        $depoRaw = str_pad($this->clean($row['depo_code']), 3, '0', STR_PAD_LEFT);
        $year    = $this->clean($row['year']);
        $period  = str_pad($this->clean($row['period']), 2, '0', STR_PAD_LEFT);
        $ccRaw   = $this->clean($row['cost_center_code']);
        $coaRaw  = $this->clean($row['coa_code']);

        $opening = $this->toInt($row['opening_balance'] ?? 0);
        $debit   = $this->toInt($row['debit'] ?? 0);
        $credit  = $this->toInt($row['credit'] ?? 0);

        // Cocokkan ke master (toleran nol di depan), pakai kode asli DB
        $depo = $this->resolve($this->depos, $this->deposNorm, $company, $depoRaw);
        $cc   = $this->resolve($this->costCenters, $this->costCentersNorm, $company, $ccRaw);
        $coa  = $this->resolve($this->coas, $this->coasNorm, $company, $coaRaw);

        $errors = [];

        if (! isset($this->companies[$this->key($company)])) {
            $errors[] = "company '{$company}' tidak ada di companies";
        }
        if ($depo === null) {
            $errors[] = "depo '{$company}'+'{$depoRaw}' tidak ada di depos";
        }
        if ($cc === null) {
            $errors[] = "cost center '{$company}'+'{$ccRaw}' tidak ada di cost_centers";
        }
        if ($coa === null) {
            $errors[] = "coa '{$company}'+'{$coaRaw}' tidak ada di chart_of_accounts";
        }
        if (! preg_match('/^\d{4}$/', $year)) {
            $errors[] = "year '{$year}' bukan 4 digit";
        }
        if (! preg_match('/^\d{2}$/', $period)) {
            $errors[] = "period '{$period}' bukan 2 digit";
        }
        if ($debit < 0) {
            $errors[] = "debit {$debit} tidak boleh minus";
        }
        if ($credit < 0) {
            $errors[] = "credit {$credit} tidak boleh minus";
        }

        if ($errors) {
            $this->fail($row, implode('; ', $errors));
            return null;
        }

        $this->successCount++;

        return new PostingBalance([
            'company_code'     => $company,
            'depo_code'        => $depo,
            'year'             => $year,
            'period'           => $period,
            'cost_center_code' => $cc,
            'coa_code'         => $coa,
            'opening_balance'  => $opening,
            'debit'            => $debit,
            'credit'           => $credit,
        ]);
    }

    public function uniqueBy(): string|array
    {
        return ['company_code', 'depo_code', 'year', 'period', 'cost_center_code', 'coa_code'];
    }

    /** Kolom yang ditimpa saat baris dengan kunci sama sudah ada */
    public function upsertColumns(): array
    {
        return ['opening_balance', 'debit', 'credit'];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function rules(): array
    {
        return [
            'company_code'     => ['required'],
            'depo_code'        => ['required'],
            'year'             => ['required'],
            'period'           => ['required'],
            'cost_center_code' => ['required'],
            'coa_code'         => ['required'],
        ];
    }

    /** Baris yang gagal validasi (required) */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $f) {
            $v = $f->values();

            $this->failedRows[] = [
                'excel_row'        => $f->row(),
                'reason'           => implode('; ', $f->errors()),
                'company_code'     => $this->clean($v['company_code'] ?? ''),
                'depo_code'        => $this->clean($v['depo_code'] ?? ''),
                'year'             => $this->clean($v['year'] ?? ''),
                'period'           => $this->clean($v['period'] ?? ''),
                'cost_center_code' => $this->clean($v['cost_center_code'] ?? ''),
                'coa_code'         => $this->clean($v['coa_code'] ?? ''),
            ];
        }
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $sheet = $event->getReader()->getActiveSheet();
                $headers = collect($sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0])
                    ->filter()
                    ->map(fn($h) => Str::slug((string) $h, '_'))
                    ->all();

                $missing = array_diff(self::REQUIRED_HEADERS, $headers);

                if ($missing) {
                    throw new \RuntimeException(
                        'Header Excel tidak lengkap. Kolom hilang: ' . implode(', ', $missing)
                    );
                }
            },
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Report ke terminal                                                 */
    /* ------------------------------------------------------------------ */

    public function failedRows(): array
    {
        usort($this->failedRows, fn($a, $b) => $a['excel_row'] <=> $b['excel_row']);

        return $this->failedRows;
    }

    public function successCount(): int
    {
        return $this->successCount;
    }

    /** @param \Illuminate\Console\Command $command */
    public function report($command): void
    {
        $failed = $this->failedRows();

        $command->info("Berhasil diproses : {$this->successCount}");

        if (! $failed) {
            $command->info('Tidak ada baris yang gagal.');
            return;
        }

        $command->error('Gagal / dilewati  : ' . count($failed) . ' baris');

        foreach ($failed as $f) {
            $command->newLine();
            $command->warn("Excel row #{$f['excel_row']}");
            $command->line("  Alasan           : {$f['reason']}");
            $command->line("  company_code     : {$f['company_code']}");
            $command->line("  depo_code        : {$f['depo_code']}");
            $command->line("  year / period    : {$f['year']} / {$f['period']}");
            $command->line("  cost_center_code : {$f['cost_center_code']}");
            $command->line("  coa_code         : {$f['coa_code']}");
        }

        $command->newLine();
    }
}
