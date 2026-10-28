<?php

namespace App\Imports;

use App\Models\Casbin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Events\BeforeImport;

class CasbinImport implements
    OnEachRow,
    WithHeadingRow,
    SkipsEmptyRows,
    WithEvents
{
    public const REQUIRED_HEADERS = [
        'ptype',
        'v0',
        'v1',
        'v2',
        'v3',
        'v4',
        'v5',
    ];

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        // Lewati baris kosong
        if (collect($data)->every(
            fn($value) => $value === null || trim((string) $value) === ''
        )) {
            return;
        }

        $validator = Validator::make($data, [
            'ptype' => ['required', 'string'],
            'v0'    => ['required', 'string'],
            'v1'    => ['required', 'string'],
            'v2'    => ['nullable', 'string'],
            'v3'    => ['nullable', 'string'],
            'v4'    => ['nullable', 'string'],
            'v5'    => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                $validator->errors()->toArray()
            );
        }

        Casbin::create([
            'ptype' => $data['ptype'],
            'v0'    => $data['v0'],
            'v1'    => $data['v1'],
            'v2'    => $data['v2'] ?? null,
            'v3'    => $data['v3'] ?? null,
            'v4'    => $data['v4'] ?? null,
            'v5'    => $data['v5'] ?? null,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $sheet = $event->getReader()->getActiveSheet();

                $headers = collect(
                    $sheet->rangeToArray(
                        'A1:' . $sheet->getHighestColumn() . '1'
                    )[0]
                )
                    ->filter()
                    ->map(fn($header) => Str::slug((string) $header, '_'))
                    ->all();

                $missing = array_diff(
                    self::REQUIRED_HEADERS,
                    $headers
                );

                if ($missing) {
                    throw new \RuntimeException('Header Excel tidak lengkap: ' . implode(', ', $missing));
                }
            },
        ];
    }
}
