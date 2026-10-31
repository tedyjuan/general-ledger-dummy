<?php

namespace App\Imports;

use App\Models\TrialBalanceAccountGroup2;
use App\Models\TrialBalanceAccountGroup3;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class TialBalanceCImport extends StringValueBinder implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithUpsertColumns,
    WithValidation,
    WithBatchInserts,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsEmptyRows
{
    public const REQUIRED_HEADERS = [
        'company_code',
        'code',
        'description',
        'tbag_2_code',
        'tbag_1_code',
        'tbag_1_account_type',
    ];

    public const ACCOUNT_TYPES = [
        'asset',
        'liability',
        'equity',
        'retained earning account',
        'income',
        'expense',
    ];

    /** Set key Group 2: "COMPANY|CODE|TBAG1_CODE|account type" => true */
    private ?array $parents = null;

    private bool $headersChecked = false;

    public function model(array $row): Model|array|null
    {
        return new TrialBalanceAccountGroup3([
            'company_code'        => $row['company_code'],
            'code'                => $row['code'],
            'description'         => $row['description'] ?: null,
            'tbag_2_code'         => $row['tbag_2_code'],
            'tbag_1_code'         => $row['tbag_1_code'],
            'tbag_1_account_type' => $row['tbag_1_account_type'],
        ]);
    }

    /** Harus sama dengan unique index: unique_tbag_3 */
    public function uniqueBy(): string|array
    {
        return ['company_code', 'code', 'tbag_2_code', 'tbag_1_code', 'tbag_1_account_type'];
    }

    public function upsertColumns(): array
    {
        return ['description', 'updated_at'];
    }

    public function prepareForValidation(array $data, int $index): array
    {
        if (! $this->headersChecked) {
            $missing = array_diff(self::REQUIRED_HEADERS, array_keys($data));

            if ($missing) {
                throw new \RuntimeException(
                    'Header Excel tidak lengkap. Kolom hilang: ' . implode(', ', $missing)
                        . ' | Kolom terbaca: ' . implode(', ', array_keys($data))
                );
            }

            $this->headersChecked = true;
        }

        $data['company_code']        = strtoupper(trim((string) ($data['company_code'] ?? '')));
        $data['code']                = strtoupper(trim((string) ($data['code'] ?? '')));
        $data['description']         = trim((string) ($data['description'] ?? ''));
        $data['tbag_2_code']         = strtoupper(trim((string) ($data['tbag_2_code'] ?? '')));
        $data['tbag_1_code']         = strtoupper(trim((string) ($data['tbag_1_code'] ?? '')));
        $data['tbag_1_account_type'] = Str::of((string) ($data['tbag_1_account_type'] ?? ''))
            ->squish()
            ->lower()
            ->toString();

        $key = $this->parentKey(
            $data['company_code'],
            $data['tbag_2_code'],
            $data['tbag_1_code'],
            $data['tbag_1_account_type']
        );
        $data['parent_exists'] = isset($this->parents()[$key]) ? 1 : null;

        return $data;
    }

    public function rules(): array
    {
        return [
            'company_code'        => ['required', 'exists:companies,code'],
            'code'                => ['required', 'max:20'],
            'description'         => ['nullable', 'max:150'],
            'tbag_2_code'         => ['required', 'max:20'],
            'tbag_1_code'         => ['required', 'max:20'],
            'tbag_1_account_type' => ['required', Rule::in(self::ACCOUNT_TYPES)],
            'parent_exists'       => ['required'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'parent_exists.required' => 'Group 2 (company_code + tbag_2_code + tbag_1_code + tbag_1_account_type) tidak ditemukan.',
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

    private function parents(): array
    {
        return $this->parents ??= TrialBalanceAccountGroup2::query()
            ->get(['company_code', 'code', 'tbag_1_code', 'tbag_1_account_type'])
            ->mapWithKeys(fn($g) => [
                $this->parentKey(
                    $g->company_code,
                    $g->code,
                    $g->tbag_1_code,
                    Str::lower($g->tbag_1_account_type)
                ) => true,
            ])
            ->all();
    }

    private function parentKey(string $company, string $group2Code, string $group1Code, string $type): string
    {
        return strtoupper($company) . '|' . strtoupper($group2Code) . '|' . strtoupper($group1Code) . '|' . $type;
    }
}
