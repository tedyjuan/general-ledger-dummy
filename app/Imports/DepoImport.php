<?php

namespace App\Imports;

use App\Models\Depo;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Validators\ValidationException;

class DepoImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithValidation,
    SkipsEmptyRows,
    WithEvents
{
    public const REQUIRED_HEADERS = [
        'code',
        'name',
        'alias',
        'area_code',
        'status_depo',
        'npwp',
        'city',
        'address',
        'postal_code',
        'phone_no',
        'fiscal_year',
        'company_code',
    ];

    public function model(array $row): Model|array|null
    {
        return new Depo([
            'company_code' => trim((string) $row['company_code']),
            'code'         => str_pad(trim((string) $row['code']), 3, '0', STR_PAD_LEFT),
            'name'         => $row['name'],
            'alias'        => $row['alias'],
            'area_code'    => str_pad(trim((string) $row['area_code']), 2, '0', STR_PAD_LEFT),
            'status_depo'  => $row['status_depo'],
            'npwp'         => (string) $row['npwp'],
            'city'         => $row['city'],
            'address'      => $row['address'],
            'postal_code'  => (string) $row['postal_code'],
            'phone_no'     => (string) $row['phone_no'],
            'fiscal_year'  => (int) $row['fiscal_year'],
        ]);
    }

    public function uniqueBy(): string|array
    {
        return ['company_code', 'code'];
    }


    public function rules(): array
    {
        return [
            'company_code' => ['required', 'exists:companies,code'],
            'code'         => ['required'],
            'name'         => ['required'],
            'alias'        => ['required'],
            'area_code'    => ['required'],
            'status_depo'  => ['required', 'in:pusat,depo'],
            'fiscal_year'  => ['required', 'integer', 'digits:4'],
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
