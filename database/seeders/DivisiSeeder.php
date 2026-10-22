<?php

namespace Database\Seeders;

use App\Imports\DivisiImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class DivisiSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/divisions.xlsx');

        if (! file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");

            return;
        }

        try {
            Excel::import(new DivisiImport, $path);
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
