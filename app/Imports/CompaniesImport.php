<?php

namespace App\Imports;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class CompaniesImport implements ToModel, WithHeadingRow, WithUpserts
{
    public function model(array $row): Model|array|null
    {
        if (blank($row['code'])) {
            return null;
        }

        return new Company([
            'code' => trim((string) $row['code']),
            'name' => trim((string) $row['name']),
        ]);
    }

    public function uniqueBy(): string|array
    {
        return 'code';
    }
}
