<?php

namespace App\Imports;

use App\Models\Coa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;

class CoaImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    WithEvents
{
    // company_code tidak di sini: wajib hanya jika $companyCode tidak diberikan
    public const REQUIRED_HEADERS = [
        'company_code',
        'account_number',
        'header_code',
        'reference_code',
        'name',
        'description',
        'tbag_1',
        'tbag_2',
        'tbag_3',
        'account_type',
        'account_method',
        'account_group',
        'cost_center_type',
    ];

    private const BATCH = 500;

    // teks yang dianggap kosong (hasil export DBeaver / SQL)
    private const NULL_TOKENS = ['[NULL]', 'NULL', '(NULL)', '<NULL>'];

    /** Hasil import, bisa dibaca setelah Excel::import() */
    public int $total = 0;

    /**
     * @param string|null $companyCode  Jika diisi, SEMUA baris masuk ke company ini
     *                                  (kolom company_code di file diabaikan).
     *                                  Jika null, kolom company_code di file wajib ada.
     */
    public function __construct(private ?string $companyCode = null)
    {
        $this->companyCode = $companyCode !== null ? trim($companyCode) : null;
    }

    /* NORMALISASI */
    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || in_array(strtoupper($value), self::NULL_TOKENS, true)) {
            return null;
        }

        return $value;
    }

    // 1001, "1001", "1001.0" (float dari Excel) -> 1001 ; selain itu null
    private function toInt(mixed $value): ?int
    {
        $value = $this->clean($value);

        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function lower(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value !== null ? Str::lower($value) : null;
    }

    private function normalize(array $row): array
    {
        $name = $this->clean($row['name'] ?? null);

        return [
            // company dari constructor selalu menang
            'company_code' => $this->clean($row['company_code'] ?? null),
            'account_number'   => $this->toInt($row['account_number'] ?? null),
            'header_code'      => $this->toInt($row['header_code'] ?? null),
            'reference_code'   => $this->toInt($row['reference_code'] ?? null),
            'name'             => $name,
            // description NOT NULL: kalau kosong pakai nama
            'description'      => $this->clean($row['description'] ?? null) ?? $name,
            'tbag_1'           => $this->clean($row['tbag_1'] ?? null),
            'tbag_2'           => $this->clean($row['tbag_2'] ?? null),
            'tbag_3'           => $this->clean($row['tbag_3'] ?? null),
            'account_type'     => $this->lower($row['account_type'] ?? null),
            'account_method'   => $this->lower($row['account_method'] ?? null),
            'account_group'    => $this->lower($row['account_group'] ?? null),
            'cost_center_type' => $this->lower($row['cost_center_type'] ?? null),
        ];
    }

    private function levelOf(array $r): int
    {
        return match (true) {
            $r['header_code'] === null    => 1, // header
            $r['reference_code'] === null => 2, // ledger
            default                       => 3, // subledger
        };
    }

    /* IMPORT */
    public function collection(Collection $rows): void
    {
        $data = $rows->map(fn($row) => $this->normalize($row->toArray()))->values();

        if ($data->isEmpty()) {
            return;
        }

        $this->assertHierarchy($data);
        $this->assertGroups($data);

        $now = now();

        DB::transaction(function () use ($data, $now) {
            // header dulu, lalu ledger, lalu subledger (FK menolak induk yang belum ada)
            foreach ([1, 2, 3] as $level) {
                $data->filter(fn($r) => $this->levelOf($r) === $level)
                    ->sortBy([['company_code', 'asc'], ['account_number', 'asc']])
                    ->chunk(self::BATCH)
                    ->each(function (Collection $chunk) use ($now) {
                        $payload = $chunk->map(fn($r) => $r + [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->values()->all();

                        // tanpa global scope: company ditentukan eksplisit dari data
                        Coa::withoutGlobalScopes()->upsert(
                            $payload,
                            ['company_code', 'account_number'],
                            [
                                'header_code',
                                'reference_code',
                                'name',
                                'description',
                                'tbag_1',
                                'tbag_2',
                                'tbag_3',
                                'account_type',
                                'account_method',
                                'account_group',
                                'cost_center_type',
                                'updated_at',
                            ]
                        );
                    });
            }
        });

        $this->total = $data->count();
    }

    /**
     * Cek hierarki sebelum menyentuh database, supaya pesan errornya jelas.
     */
    private function assertHierarchy(Collection $data): void
    {
        $errors = [];

        // peta "company|nomor" => header_code, dari database
        $headerOf = Coa::withoutGlobalScopes()
            ->whereIn('company_code', $data->pluck('company_code')->unique()->all())
            ->get(['company_code', 'account_number', 'header_code'])
            ->mapWithKeys(fn($c) => [
                $c->company_code . '|' . $c->account_number => $c->header_code,
            ])
            ->all();

        $seen = [];
        foreach ($data as $r) {
            $key = $r['company_code'] . '|' . $r['account_number'];

            if (isset($seen[$key])) {
                $errors[] = "Nomor akun {$r['account_number']} ({$r['company_code']}) dobel di file.";
            }
            $seen[$key] = true;

            // data file menimpa data database
            $headerOf[$key] = $r['header_code'];
        }

        foreach ($data as $r) {
            $no = "{$r['account_number']} ({$r['company_code']})";

            if ($r['header_code'] !== null) {
                $hk = $r['company_code'] . '|' . $r['header_code'];

                if (! array_key_exists($hk, $headerOf)) {
                    $errors[] = "Akun {$no}: header {$r['header_code']} tidak ditemukan.";
                } elseif ($headerOf[$hk] !== null) {
                    $errors[] = "Akun {$no}: {$r['header_code']} bukan akun level header.";
                }
            }

            if ($r['reference_code'] !== null) {
                $lk = $r['company_code'] . '|' . $r['reference_code'];

                if (! array_key_exists($lk, $headerOf)) {
                    $errors[] = "Akun {$no}: ledger {$r['reference_code']} tidak ditemukan.";
                } elseif ($headerOf[$lk] === null || $headerOf[$lk] !== $r['header_code']) {
                    $errors[] = "Akun {$no}: ledger {$r['reference_code']} bukan milik header {$r['header_code']}.";
                }
            }

            if ($r['account_number'] === $r['header_code'] || $r['account_number'] === $r['reference_code']) {
                $errors[] = "Akun {$no}: tidak boleh menunjuk dirinya sendiri.";
            }
        }

        if ($errors) {
            $shown = array_slice($errors, 0, 10);
            $more  = count($errors) - count($shown);

            throw new \RuntimeException(
                "Hierarki COA tidak valid:\n- " . implode("\n- ", $shown)
                    . ($more > 0 ? "\n...dan {$more} error lainnya." : '')
            );
        }
    }

    /* VALIDASI PER BARIS */
    public function prepareForValidation(array $data, int $index): array
    {
        return $this->normalize($data);
    }

    public function rules(): array
    {
        return [
            'company_code' => array_filter([
                'required',
                'exists:companies,code',
                $this->companyCode !== null ? Rule::in([$this->companyCode]) : null,
            ]),
            'account_number'   => ['required', 'integer', 'min:1'],
            'header_code'      => ['nullable', 'integer', 'required_with:reference_code'],
            'reference_code'   => ['nullable', 'integer'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['required', 'string', 'max:255'],
            'tbag_1'           => ['nullable', 'string', 'max:20', 'required_with:tbag_2'],
            'tbag_2'           => ['nullable', 'string', 'max:20', 'required_with:tbag_3'],
            'tbag_3'           => ['nullable', 'string', 'max:20'],
            'account_type'     => ['nullable', Rule::in(Coa::ACCOUNT_TYPES), 'required_with:tbag_1'],
            'account_method'   => ['nullable', Rule::in(Coa::ACCOUNT_METHODS)],
            'account_group'    => ['nullable', Rule::in(Coa::ACCOUNT_GROUPS)],
            'cost_center_type' => ['nullable', Rule::in(Coa::COST_CENTER_TYPES)],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'company_code.required'      => 'Kolom company_code wajib diisi.',
            'company_code.in'            => 'Baris ini milik company :input, tetapi import dibatasi untuk company yang sedang aktif.',
            'company_code.exists'        => 'Kode perusahaan :input tidak ada di tabel companies.',
            'account_number.required'    => 'Nomor akun wajib diisi.',
            'account_number.integer'     => 'Nomor akun harus berupa angka.',
            'header_code.required_with'  => 'header_code wajib diisi jika reference_code terisi.',
            'tbag_1.required_with'       => 'tbag_1 wajib diisi jika tbag_2 terisi.',
            'tbag_2.required_with'       => 'tbag_2 wajib diisi jika tbag_3 terisi.',
            'account_type.in'            => 'account_type :input tidak valid.',
            'account_type.required_with' => 'account_type wajib diisi jika tbag_1 terisi.',
        ];
    }

    /* CEK HEADER EXCEL */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $sheet = $event->getReader()->getActiveSheet();
                $headers = collect($sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0])
                    ->filter()
                    ->map(fn($h) => Str::slug((string) $h, '_'))
                    ->all();

                $required = $this->companyCode === null
                    ? array_merge(['company_code'], self::REQUIRED_HEADERS)
                    : self::REQUIRED_HEADERS;

                $missing = array_diff(self::REQUIRED_HEADERS, $headers);

                if ($missing) {
                    throw new \RuntimeException(
                        'Header Excel tidak lengkap. Kolom hilang: ' . implode(', ', $missing)
                    );
                }
            },
        ];
    }

    private function assertGroups(Collection $data): void
    {
        $companies = $data->pluck('company_code')->unique()->all();
        $k = fn(...$p) => Str::lower(implode('|', $p)); // MySQL membandingkan tanpa beda huruf

        $g1 = DB::table('trial_balance_account_group_1')
            ->whereIn('company_code', $companies)
            ->get(['company_code', 'code', 'account_type'])
            ->mapWithKeys(fn($g) => [$k($g->company_code, $g->code, $g->account_type) => true])
            ->all();

        $g2 = DB::table('trial_balance_account_group_2')
            ->whereIn('company_code', $companies)
            ->get(['company_code', 'code', 'tbag_1_code', 'tbag_1_account_type'])
            ->mapWithKeys(fn($g) => [$k($g->company_code, $g->code, $g->tbag_1_code, $g->tbag_1_account_type) => true])
            ->all();

        $g3 = DB::table('trial_balance_account_group_3')
            ->whereIn('company_code', $companies)
            ->get(['company_code', 'code', 'tbag_2_code', 'tbag_1_code', 'tbag_1_account_type'])
            ->mapWithKeys(fn($g) => [$k($g->company_code, $g->code, $g->tbag_2_code, $g->tbag_1_code, $g->tbag_1_account_type) => true])
            ->all();

        $errors = [];

        foreach ($data as $r) {
            $t = $r['account_type'];

            if ($r['tbag_1'] && ! isset($g1[$k($r['company_code'], $r['tbag_1'], $t)])) {
                $errors[] = "[{$r['company_code']}] Group 1 '{$r['tbag_1']}' bertipe '{$t}' tidak ada (akun {$r['account_number']}).";
            } elseif ($r['tbag_2'] && ! isset($g2[$k($r['company_code'], $r['tbag_2'], $r['tbag_1'], $t)])) {
                $errors[] = "[{$r['company_code']}] Group 2 '{$r['tbag_2']}' di bawah '{$r['tbag_1']}' bertipe '{$t}' tidak ada (akun {$r['account_number']}).";
            } elseif ($r['tbag_3'] && ! isset($g3[$k($r['company_code'], $r['tbag_3'], $r['tbag_2'], $r['tbag_1'], $t)])) {
                $errors[] = "[{$r['company_code']}] Group 3 '{$r['tbag_3']}' tidak ada (akun {$r['account_number']}).";
            }
        }

        if ($errors) {
            throw new \RuntimeException(
                "Trial balance group tidak cocok:\n- " . implode("\n- ", array_slice($errors, 0, 30))
                    . (count($errors) > 30 ? "\n...dan " . (count($errors) - 30) . ' error lainnya.' : '')
            );
        }
    }
}
