<?php

namespace App\Imports;

use App\Models\ProductMapped;
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
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Validators\Failure;

class ProductMappingImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithValidation,
    SkipsEmptyRows,
    SkipsOnFailure,
    WithBatchInserts,
    WithEvents
{
    use RemembersRowNumber;

    public const REQUIRED_HEADERS = [
        'company_code',
        'product_code',
        'transaction_type',
        'operational_type',
        'cost_center_code',
        'coa_code',
        'kind',
        'do_type',
    ];

    /** Lookup FK (di-load sekali) */
    private array $companies   = [];
    private array $products    = [];
    private array $coas        = [];
    private array $costCenters = [];

    /** Baris yang gagal: [excel_row, alasan, data...] */
    private array $failedRows = [];

    private int $successCount = 0;

    public function __construct()
    {
        $this->companies = DB::table('companies')->pluck('code')
            ->mapWithKeys(fn($c) => [$this->key($c) => true])->all();

        $this->products = DB::table('products')->select('company_code', 'product_code')->get()
            ->mapWithKeys(fn($r) => [$this->key($r->company_code, $r->product_code) => true])->all();

        $this->coas = DB::table('chart_of_accounts')->select('company_code', 'account_number')->get()
            ->mapWithKeys(fn($r) => [$this->key($r->company_code, $r->account_number) => true])->all();

        $this->costCenters = DB::table('cost_centers')->select('company_code', 'cost_center')->get()
            ->mapWithKeys(fn($r) => [$this->key($r->company_code, $r->cost_center) => true])->all();
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /** Normalisasi nilai sel Excel jadi string bersih (angka 100303.0 -> "100303"). */
    private function clean(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        // buang spasi biasa, non-breaking space, dan karakter zero-width
        $value = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', '', (string) $value);

        return trim($value);
    }

    /** Key lookup (lowercase, meniru collation *_ci MySQL). */
    private function key(mixed ...$parts): string
    {
        return mb_strtolower(implode('|', array_map(fn($p) => $this->clean($p), $parts)));
    }

    private function fail(array $row, string $reason): void
    {
        $this->failedRows[] = [
            'excel_row'        => $this->getRowNumber(),
            'reason'           => $reason,
            'company_code'     => $this->clean($row['company_code'] ?? ''),
            'product_code'     => $this->clean($row['product_code'] ?? ''),
            'transaction_type' => $this->clean($row['transaction_type'] ?? ''),
            'operational_type' => $this->clean($row['operational_type'] ?? ''),
            'cost_center_code' => $this->clean($row['cost_center_code'] ?? ''),
            'coa_code'         => $this->clean($row['coa_code'] ?? ''),
            'kind'             => $this->clean($row['kind'] ?? ''),
            'do_type'          => $this->clean($row['do_type'] ?? ''),
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

        $data['kind'] = strtolower($data['kind']);

        return $data;
    }

    public function model(array $row): Model|array|null
    {
        $company = $this->clean($row['company_code']);
        $product = $this->clean($row['product_code']);
        $coa     = $this->clean($row['coa_code']);
        $cc      = $this->clean($row['cost_center_code']);

        $errors = [];

        if (! isset($this->companies[$this->key($company)])) {
            $errors[] = "company_code '{$company}' tidak ada di tabel companies";
        }
        if (! isset($this->products[$this->key($company, $product)])) {
            $errors[] = "product '{$company}'+'{$product}' tidak ada di tabel products";
        }
        if (! isset($this->coas[$this->key($company, $coa)])) {
            $errors[] = "coa '{$company}'+'{$coa}' tidak ada di chart_of_accounts";
        }
        if (! isset($this->costCenters[$this->key($company, $cc)])) {
            $errors[] = "cost center '{$company}'+'{$cc}' tidak ada di cost_centers";
        }

        if ($errors) {
            $this->fail($row, implode('; ', $errors));
            return null;
        }

        $this->successCount++;

        return new ProductMapped([
            'company_code'     => $company,
            'product_code'     => $product,
            'transaction_type' => $this->clean($row['transaction_type']),
            'operational_type' => $this->clean($row['operational_type']),
            'cost_center_code' => $cc,
            'coa_code'         => $coa,
            'kind'             => strtolower($this->clean($row['kind'])),
            'do_type'          => $this->clean($row['do_type']),
        ]);
    }

    public function uniqueBy(): string|array
    {
        return self::REQUIRED_HEADERS;
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function rules(): array
    {
        return [
            'company_code'     => ['required'],
            'product_code'     => ['required'],
            'transaction_type' => ['required'],
            'operational_type' => ['required'],
            'cost_center_code' => ['required'],
            'coa_code'         => ['required'],
            'kind'             => ['required', 'in:debit,credit'],
            'do_type'          => ['required'],
        ];
    }

    /** Baris yang gagal validasi (required / kind) */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $f) {
            $v = $f->values();

            $this->failedRows[] = [
                'excel_row'        => $f->row(),
                'reason'           => implode('; ', $f->errors()),
                'company_code'     => $this->clean($v['company_code'] ?? ''),
                'product_code'     => $this->clean($v['product_code'] ?? ''),
                'transaction_type' => $this->clean($v['transaction_type'] ?? ''),
                'operational_type' => $this->clean($v['operational_type'] ?? ''),
                'cost_center_code' => $this->clean($v['cost_center_code'] ?? ''),
                'coa_code'         => $this->clean($v['coa_code'] ?? ''),
                'kind'             => $this->clean($v['kind'] ?? ''),
                'do_type'          => $this->clean($v['do_type'] ?? ''),
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

        // Detail per baris Excel (1 baris Excel = 1 blok)
        foreach ($failed as $f) {
            $command->newLine();
            $command->warn("Excel row #{$f['excel_row']}");
            $command->line("  Alasan           : {$f['reason']}");
            $command->line("  company_code     : {$f['company_code']}");
            $command->line("  product_code     : {$f['product_code']}");
            $command->line("  transaction_type : {$f['transaction_type']}");
            $command->line("  operational_type : {$f['operational_type']}");
            $command->line("  cost_center_code : {$f['cost_center_code']}");
            $command->line("  coa_code         : {$f['coa_code']}");
            $command->line("  kind             : {$f['kind']}");
            $command->line("  do_type          : {$f['do_type']}");
        }

        $command->newLine();
    }
}
