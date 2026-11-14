<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithEvents;

class ProductImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithValidation,
    SkipsEmptyRows,
    WithEvents
{
    public const REQUIRED_HEADERS = [
        'company_code',
        'product_code',
        'product_name',
        'coa_code'
    ];

    public function model(array $row): Model|array|null
    {
        return new Product([
            'company_code' => trim((string) $row['company_code']),
            'product_code' => trim((string) $row['product_code']),
            'product_name' => $row['product_name'],
            'coa_code'     => $row['coa_code'],
        ]);
    }

    public function uniqueBy(): string|array
    {
        return ['company_code', 'product_code'];
    }


    public function rules(): array
    {
        return [
            'company_code' => ['required', 'exists:companies,code'],
            'product_code' => ['required'],
            'product_name' => ['required'],
            'coa_code'     => ['required'],
        ];
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
