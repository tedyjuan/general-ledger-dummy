<?php

namespace Database\Seeders;

use App\Imports\JournalSourceImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class JournalSourceSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/journal_sources.xlsx');

        if (! file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");

            return;
        }

        try {
            Excel::import(new JournalSourceImport, $path);
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
}
