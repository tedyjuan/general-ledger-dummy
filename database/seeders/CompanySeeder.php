<?php

namespace Database\Seeders;

use App\Imports\CompaniesImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/companies.xlsx');

        if (! file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");

            return;
        }

        Excel::import(new CompaniesImport, $path);
    }
}
