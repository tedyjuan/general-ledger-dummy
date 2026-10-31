<?php

namespace App\Imports;

use App\Models\TrialBalanceAccountGroup1;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;

class TialBalanceAImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithUpsertColumns,
    WithValidation,
    SkipsEmptyRows,
    WithEvents
{
    public const REQUIRED_HEADERS = [
        'company_code',
        'code',
        'description',
        'account_type',
    ];

    public const ACCOUNT_TYPES = [
        'asset',
        'liability',
        'equity',
        'retained earning account',
        'income',
        'expense',
    ];

    public function model(array $row): Model|array|null
    {
        return new TrialBalanceAccountGroup1([
            'company_code' => $row['company_code'],
            'code'         => $row['code'],
            'description'  => $row['description'],
            'account_type' => $row['account_type'],
        ]);
    }

    /** Harus sama dengan unique index: unique_tbag_1 */
    public function uniqueBy(): string|array
    {
        return ['company_code', 'code', 'account_type'];
    }

    /** Saat baris sudah ada, hanya kolom ini yang diperbarui */
    public function upsertColumns(): array
    {
        return ['description', 'updated_at'];
    }

    /** Rapikan data sebelum divalidasi dan disimpan */
    public function prepareForValidation(array $data, int $index): array
    {
        $data['company_code'] = strtoupper(trim((string) ($data['company_code'] ?? '')));
        $data['code']         = strtoupper(trim((string) ($data['code'] ?? '')));
        $data['description']  = trim((string) ($data['description'] ?? ''));
        $data['account_type'] = Str::of((string) ($data['account_type'] ?? ''))
            ->squish()
            ->lower()
            ->toString();

        return $data;
    }

    public function rules(): array
    {
        return [
            'company_code' => ['required', 'exists:companies,code'],
            'code'         => ['required', 'max:20'],
            'description'  => ['nullable', 'max:150'],
            'account_type' => ['required', Rule::in(self::ACCOUNT_TYPES)],
        ];
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
}
