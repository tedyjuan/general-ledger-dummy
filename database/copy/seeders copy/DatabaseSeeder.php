<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = \App\Models\Company::firstOrCreate([
            'name' => 'PT. Berkah Sentosa Abadi',
        ]);

        $depos = [
            [
                'company_id' => $company->id,
                'depo_code' => '336',
                'name' => 'Serang',
                'alias' => 'SRG',
                'area_code' => '01',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-012.000',
                'city' => 'Serang',
                'address' => 'Jl. Raya Serang No. 45',
                'postal_code' => '42111',
                'phone_no' => '0254-123456',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '320',
                'name' => 'Kelapa Dua',
                'alias' => 'KLD',
                'area_code' => '02',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-013.000',
                'city' => 'Tangerang',
                'address' => 'Jl. Boulevard Kelapa Dua No. 12',
                'postal_code' => '15810',
                'phone_no' => '021-5467890',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '001',
                'name' => 'Kantor Pusat Jakarta',
                'alias' => 'PST',
                'area_code' => '00',
                'status_depo' => 'pusat',
                'npwp' => '01.234.567.8-011.000',
                'city' => 'Jakarta Selatan',
                'address' => 'Jl. Jend. Sudirman Kav. 25',
                'postal_code' => '12920',
                'phone_no' => '021-5251234',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '315',
                'name' => 'Cikupa',
                'alias' => 'CKP',
                'area_code' => '03',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-014.000',
                'city' => 'Tangerang',
                'address' => 'Kawasan Industri Cikupa Mas',
                'postal_code' => '15710',
                'phone_no' => '021-5961234',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '340',
                'name' => 'Cilegon',
                'alias' => 'CLG',
                'area_code' => '04',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-015.000',
                'city' => 'Cilegon',
                'address' => 'Jl. Raya Cilegon No. 88',
                'postal_code' => '42414',
                'phone_no' => '0254-381234',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '350',
                'name' => 'Pandeglang',
                'alias' => 'PDG',
                'area_code' => '05',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-016.000',
                'city' => 'Pandeglang',
                'address' => 'Jl. Ahmad Yani No. 10',
                'postal_code' => '42211',
                'phone_no' => '0253-201234',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '360',
                'name' => 'Rangkasbitung',
                'alias' => 'RKB',
                'area_code' => '06',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-017.000',
                'city' => 'Lebak',
                'address' => 'Jl. Multatuli No. 5',
                'postal_code' => '42311',
                'phone_no' => '0252-202345',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '370',
                'name' => 'Bogor Barat',
                'alias' => 'BGR',
                'area_code' => '07',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-018.000',
                'city' => 'Bogor',
                'address' => 'Jl. KH. Sholeh Iskandar No. 15',
                'postal_code' => '16164',
                'phone_no' => '0251-8312345',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '380',
                'name' => 'Depok Sawangan',
                'alias' => 'SWG',
                'area_code' => '08',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-019.000',
                'city' => 'Depok',
                'address' => 'Jl. Raya Sawangan No. 99',
                'postal_code' => '16511',
                'phone_no' => '021-7788990',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '390',
                'name' => 'Bekasi Timur',
                'alias' => 'BKS',
                'area_code' => '09',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-020.000',
                'city' => 'Bekasi',
                'address' => 'Jl. Cut Mutia No. 22',
                'postal_code' => '17113',
                'phone_no' => '021-8822334',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '400',
                'name' => 'Karawang Barat',
                'alias' => 'KRW',
                'area_code' => '10',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-021.000',
                'city' => 'Karawang',
                'address' => 'Kawasan KIIC Lot B-1',
                'postal_code' => '41361',
                'phone_no' => '0267-640123',
                'fiscal_year' => 2026,
            ],
            [
                'company_id' => $company->id,
                'depo_code' => '410',
                'name' => 'Sukabumi Kota',
                'alias' => 'SKB',
                'area_code' => '11',
                'status_depo' => 'depo',
                'npwp' => '01.234.567.8-022.000',
                'city' => 'Sukabumi',
                'address' => 'Jl. Bhayangkara No. 40',
                'postal_code' => '43121',
                'phone_no' => '0266-221234',
                'fiscal_year' => 2026,
            ],
        ];

        foreach ($depos as $data) {
            \App\Models\Depo::create($data);
        }
    }
}
