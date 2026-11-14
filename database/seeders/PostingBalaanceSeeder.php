<?php

namespace Database\Seeders;

use App\Imports\PostingBalanaceImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class PostingBalaanceSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/posting_balances.xlsx');

        if (! file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");

            return;
        }

        try {
            Excel::import(new PostingBalanaceImport, $path);
        } catch (ValidationException $e) {
            foreach ($e->failures() as $failure) {
                $this->command->error(
                    "Baris {$failure->row()} [{$failure->attribute()}]: " . implode(', ', $failure->errors())
                );
            }
        } catch (\RuntimeException $e) {
            $this->command->error($e->getMessage());
        }
    }
    /** "1.234.567", "Rp 1,234,567", "-5000", 1234567.0 -> int. Kosong -> 0. */
    private function toInt(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $s = preg_replace('/[^0-9\-]/', '', (string) $value);

        if ($s === '' || $s === '-') {
            return 0;
        }

        return (int) $s;
    }
}
