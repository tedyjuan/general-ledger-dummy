<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
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
        ]);
    }
}
