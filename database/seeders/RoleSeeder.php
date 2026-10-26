<?php

namespace Database\Seeders;

use App\Imports\RoleImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/roles.xlsx');

        if (! file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");

            return;
        }

        try {
            Excel::import(new RoleImport, $path);
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
