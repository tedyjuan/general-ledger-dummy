<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Menampilkan pesan di terminal (opsional)
        $this->command->info('Clearing all caches...');

        // 2. Menjalankan perintah artisan optimize:clear
        // Ini akan menghapus cache config, route, view, dan cache aplikasi
        Artisan::call('optimize:clear');

        // Jika Anda HANYA ingin menghapus cache data (tanpa route/config dll), gunakan:
        // Artisan::call('cache:clear');

        // Khusus jika Anda menggunakan Spatie Permission atau Casbin 
        // yang memiliki cache spesifik, Anda juga bisa memanggilnya di sini:
        // Artisan::call('permission:cache-reset'); 

        $this->command->info('Running seeders...');
        $this->call([
            CompanySeeder::class,
            RoleSeeder::class,
            DepoSeeder::class,
            UserSeeder::class,
            DivisiSeeder::class,
            DepartementSeeder::class,
            SegmentSeeder::class,
            CasbinSeeder::class,
            Tbag1Seeder::class,
            Tbag2Seeder::class,
            Tbag3Seeder::class,
            CompanyUserSeeder::class,
            FiscalPeriodSeeder::class,
            JournalSourceSeeder::class,
            CoaSeeder::class,
            CostCenterSeeder::class,
        ]);
    }
}
