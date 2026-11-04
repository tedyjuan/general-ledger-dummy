<?php

namespace App\Imports;

use App\Models\FiscalPeriod;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithEvents;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FiscalPeriodImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithValidation,
    SkipsEmptyRows,
    WithEvents
{
    public const REQUIRED_HEADERS = [
        'depo_code',
        'year',
        'period',
        'start_date',
        'end_date',
        'status',
    ];

    public function model(array $row): Model|array|null
    {
        return new FiscalPeriod([
            'depo_code'  => trim((string) $row['depo_code']),
            'year'       => trim((string) $row['year']),
            'period'     => str_pad(trim((string) $row['period']), 2, '0', STR_PAD_LEFT),
            'start_date' => $this->tanggal($row['start_date']),
            'end_date'   => $this->tanggal($row['end_date']),
            'status'     => $row['status'],
        ]);
    }

    public function uniqueBy(): string|array
    {
        return ['depo_code', 'year', 'period'];
    }


    public function rules(): array
    {
        return [
            'depo_code'  => ['required', 'exists:depos,code'],
            'year'       => ['required'],
            'period'     => ['required'],
            'start_date' => ['required'],
            'end_date'   => ['required'],
            'status'     => ['required'],
        ];
    }
    /**
     * Excel menyimpan tanggal sebagai angka serial (45292 = 01/01/2024).
     * Hasil: 'Y-m-d'. Teks 'd/m/Y' juga didukung.
     */
    private function tanggal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        try {
            return Carbon::createFromFormat('!d/m/Y', trim((string) $value))->format('Y-m-d');
        } catch (\Throwable) {
            return trim((string) $value);   // tidak terbaca: biarkan ditolak validasi
        }
    }
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $sheet = $event->getReader()->getActiveSheet();
                $headers = collect($sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0])
                    ->filter()
                    ->map(fn($h) => \Illuminate\Support\Str::slug((string) $h, '_'))
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
}
