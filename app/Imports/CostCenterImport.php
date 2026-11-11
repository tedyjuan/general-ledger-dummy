<?php

namespace App\Imports;

use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class CostCenterImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithBatchInserts,
    WithChunkReading,
    WithValidation,
    SkipsEmptyRows
{
    public const REQUIRED_HEADERS = [
        'cost_center',
        'name',
        'company_code',
        'area_code',
        'department_code',
        'division_code',
        'segment_code',
        'manager',
        'description',
    ];

    private bool $headersChecked = false;

    public function model(array $row): Model|array|null
    {
        $this->validateHeaders($row);

        return new CostCenter([
            'cost_center'     => $row['cost_center'],
            'name'            => $row['name'],
            'company_code'    => $row['company_code'],
            'area_code'       => $row['area_code'],
            'department_code' => $row['department_code'],
            'division_code'   => $row['division_code'],
            'segment_code'    => $row['segment_code'],
            'manager'         => $row['manager'],
            'description'     => $row['description'],
        ]);
    }

    /**
     * Header dicek sekali saja, dari key baris pertama.
     * Key sudah berupa slug (cost_center, company_code, dst).
     */
    private function validateHeaders(array $row): void
    {
        if ($this->headersChecked) {
            return;
        }

        $this->headersChecked = true;

        $missing = array_diff(self::REQUIRED_HEADERS, array_keys($row));

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Header Excel tidak lengkap. Kolom hilang: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Normalisasi sebelum validasi:
     * cast ke string, trim, dan string kosong menjadi null.
     * Header dicek di sini juga supaya error header muncul
     * sebelum error validasi per kolom.
     */
    public function prepareForValidation(array $data, int $index): array
    {
        $this->validateHeaders($data);

        foreach (self::REQUIRED_HEADERS as $column) {
            $value = $data[$column] ?? null;

            if (is_null($value)) {
                $data[$column] = null;
                continue;
            }

            $value = trim((string) $value);
            $data[$column] = $value === '' ? null : $value;
        }

        return $data;
    }

    public function uniqueBy(): string|array
    {
        // Harus sesuai dengan unique index di tabel cost_centers
        return ['company_code', 'cost_center'];
    }

    public function rules(): array
    {
        return [
            'cost_center'     => ['required', 'string', 'max:50'],
            'name'            => ['required', 'string', 'max:255'],
            'company_code'    => ['required', 'string', 'exists:companies,code'],
            'area_code'       => ['required', 'string', 'max:50'],
            'department_code' => ['required', 'string', 'max:50'],
            'division_code'   => ['required', 'string', 'max:50'],
            'segment_code'    => ['required', 'string', 'max:50'],
            'manager'         => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'company_code.exists' => 'Company code :input tidak ditemukan di master company.',
            '*.required'          => 'Kolom :attribute wajib diisi.',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
