<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\TrialBalanceAccountGroup1;
use App\Models\TrialBalanceAccountGroup2;
use App\Models\TrialBalanceAccountGroup3;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate([
            'name' => 'PT. Berkah Sentosa Abadi',
        ]);

        // 1. TBAG 1
        $tbag1_aktiva = TrialBalanceAccountGroup1::firstOrCreate(
            ['company_id' => $company->id, 'code' => '1'],
            ['description' => 'ASET / AKTIVA', 'account_type' => 'asset']
        );
        $tbag1_kewajiban = TrialBalanceAccountGroup1::firstOrCreate(
            ['company_id' => $company->id, 'code' => '2'],
            ['description' => 'KEWAJIBAN / LIABILITAS', 'account_type' => 'liability']
        );
        $tbag1_ekuitas = TrialBalanceAccountGroup1::firstOrCreate(
            ['company_id' => $company->id, 'code' => '3'],
            ['description' => 'EKUITAS / MODAL', 'account_type' => 'equity']
        );
        $tbag1_pendapatan = TrialBalanceAccountGroup1::firstOrCreate(
            ['company_id' => $company->id, 'code' => '4'],
            ['description' => 'PENDAPATAN', 'account_type' => 'income']
        );
        $tbag1_beban = TrialBalanceAccountGroup1::firstOrCreate(
            ['company_id' => $company->id, 'code' => '5'],
            ['description' => 'BEBAN', 'account_type' => 'expense']
        );

        // 2. TBAG 2
        $tbag2_lancar = TrialBalanceAccountGroup2::firstOrCreate(
            ['company_id' => $company->id, 'code' => '11'],
            ['description' => 'ASET LANCAR', 'tbag_1' => $tbag1_aktiva->id]
        );
        $tbag2_tetap = TrialBalanceAccountGroup2::firstOrCreate(
            ['company_id' => $company->id, 'code' => '12'],
            ['description' => 'ASET TETAP', 'tbag_1' => $tbag1_aktiva->id]
        );
        $tbag2_kewajiban_lancar = TrialBalanceAccountGroup2::firstOrCreate(
            ['company_id' => $company->id, 'code' => '21'],
            ['description' => 'KEWAJIBAN JANGKA PENDEK', 'tbag_1' => $tbag1_kewajiban->id]
        );

        // 3. TBAG 3
        $tbag3_kas = TrialBalanceAccountGroup3::firstOrCreate(
            ['company_id' => $company->id, 'code' => '111'],
            ['description' => 'KAS DAN BANK', 'tbag_2' => $tbag2_lancar->id]
        );
        $tbag3_piutang = TrialBalanceAccountGroup3::firstOrCreate(
            ['company_id' => $company->id, 'code' => '112'],
            ['description' => 'PIUTANG USAHA', 'tbag_2' => $tbag2_lancar->id]
        );
        $tbag3_persediaan = TrialBalanceAccountGroup3::firstOrCreate(
            ['company_id' => $company->id, 'code' => '113'],
            ['description' => 'PERSEDIAAN BARANG', 'tbag_2' => $tbag2_lancar->id]
        );

        // 4. Header Accounts
        $headerAset = ChartOfAccount::firstOrCreate(
            ['account_number' => 1000, 'company_id' => $company->id],
            [
                'name'             => 'Aset',
                'description'      => 'Kelompok Akun Aset / Aktiva',
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
            ]
        );

        $headerAsetLancar = ChartOfAccount::firstOrCreate(
            ['account_number' => 1100, 'company_id' => $company->id],
            [
                'name'             => 'Aset Lancar',
                'description'      => 'Sub-Kelompok Aset Lancar',
                'header_id'        => $headerAset->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_lancar->id,
            ]
        );

        $headerAsetTetap = ChartOfAccount::firstOrCreate(
            ['account_number' => 1200, 'company_id' => $company->id],
            [
                'name'             => 'Aset Tetap',
                'description'      => 'Sub-Kelompok Aset Tetap',
                'header_id'        => $headerAset->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_tetap->id,
            ]
        );

        $headerKewajiban = ChartOfAccount::firstOrCreate(
            ['account_number' => 2000, 'company_id' => $company->id],
            [
                'name'             => 'Kewajiban',
                'description'      => 'Kelompok Akun Kewajiban / Liabilitas',
                'account_type'     => 'liability',
                'account_method'   => 'credit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_kewajiban->id,
            ]
        );

        $headerEkuitas = ChartOfAccount::firstOrCreate(
            ['account_number' => 3000, 'company_id' => $company->id],
            [
                'name'             => 'Ekuitas',
                'description'      => 'Kelompok Akun Modal dan Ekuitas',
                'account_type'     => 'equity',
                'account_method'   => 'credit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_ekuitas->id,
            ]
        );

        $headerPendapatan = ChartOfAccount::firstOrCreate(
            ['account_number' => 4000, 'company_id' => $company->id],
            [
                'name'             => 'Pendapatan',
                'description'      => 'Kelompok Akun Pendapatan Usaha',
                'account_type'     => 'income',
                'account_method'   => 'credit',
                'account_group'    => 'sales',
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_pendapatan->id,
            ]
        );

        $headerBeban = ChartOfAccount::firstOrCreate(
            ['account_number' => 5000, 'company_id' => $company->id],
            [
                'name'             => 'Beban Operasional',
                'description'      => 'Kelompok Akun Biaya & Beban Operasional',
                'account_type'     => 'expense',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_beban->id,
            ]
        );

        // 5. Detail COA
        $accounts = [
            [
                'account_number'   => 1111,
                'name'             => 'Kas Utama Kantor',
                'description'      => 'Kas tunai kantor pusat',
                'header_id'        => $headerAsetLancar->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => 'kas',
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_lancar->id,
                'tbag_3'           => $tbag3_kas->id,
            ],
            [
                'account_number'   => 1112,
                'name'             => 'Kas Kecil (Petty Cash)',
                'description'      => 'Kas operasional harian cabang dan depo',
                'header_id'        => $headerAsetLancar->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => 'kas',
                'cost_center_type' => 'depo',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_lancar->id,
                'tbag_3'           => $tbag3_kas->id,
            ],
            [
                'account_number'   => 1120,
                'name'             => 'Bank BCA Giro Operasional',
                'description'      => 'Rekening giro BCA utama perusahaan',
                'header_id'        => $headerAsetLancar->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => 'bank',
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_lancar->id,
                'tbag_3'           => $tbag3_kas->id,
            ],
            [
                'account_number'   => 1121,
                'name'             => 'Bank Mandiri Giro Operasional',
                'description'      => 'Rekening giro Mandiri operasional',
                'header_id'        => $headerAsetLancar->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => 'bank',
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_lancar->id,
                'tbag_3'           => $tbag3_kas->id,
            ],
            [
                'account_number'   => 1130,
                'name'             => 'Piutang Dagang (Trade AR)',
                'description'      => 'Piutang usaha dari penjualan barang ke pelanggan',
                'header_id'        => $headerAsetLancar->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_lancar->id,
                'tbag_3'           => $tbag3_piutang->id,
            ],
            [
                'account_number'   => 1140,
                'name'             => 'Persediaan Barang Dagang',
                'description'      => 'Persediaan stok produk di gudang dan depo',
                'header_id'        => $headerAsetLancar->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => 'inventory',
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_lancar->id,
                'tbag_3'           => $tbag3_persediaan->id,
            ],
            [
                'account_number'   => 1210,
                'name'             => 'Bangunan & Gedung',
                'description'      => 'Gedung kantor dan gudang depo',
                'header_id'        => $headerAsetTetap->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'unit',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_tetap->id,
            ],
            [
                'account_number'   => 1220,
                'name'             => 'Kendaraan Truk & Operasional',
                'description'      => 'Armada pengiriman barang dan logistik',
                'header_id'        => $headerAsetTetap->id,
                'account_type'     => 'asset',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'unit',
                'tbag_1'           => $tbag1_aktiva->id,
                'tbag_2'           => $tbag2_tetap->id,
            ],
            [
                'account_number'   => 2110,
                'name'             => 'Utang Dagang (AP)',
                'description'      => 'Kewajiban kepada vendor dan pemasok barang',
                'header_id'        => $headerKewajiban->id,
                'account_type'     => 'liability',
                'account_method'   => 'credit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_kewajiban->id,
                'tbag_2'           => $tbag2_kewajiban_lancar->id,
            ],
            [
                'account_number'   => 2120,
                'name'             => 'Utang Gaji & Honor Karyawan',
                'description'      => 'Akrual beban gaji karyawan bulanan',
                'header_id'        => $headerKewajiban->id,
                'account_type'     => 'liability',
                'account_method'   => 'credit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_kewajiban->id,
                'tbag_2'           => $tbag2_kewajiban_lancar->id,
            ],
            [
                'account_number'   => 3100,
                'name'             => 'Modal Saham Perusahaan',
                'description'      => 'Modal awal yang disetor para pemegang saham',
                'header_id'        => $headerEkuitas->id,
                'account_type'     => 'equity',
                'account_method'   => 'credit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_ekuitas->id,
            ],
            [
                'account_number'   => 3200,
                'name'             => 'Laba Ditahan (Retained Earnings)',
                'description'      => 'Akumulasi laba yang ditahan dari tahun-tahun sebelumnya',
                'header_id'        => $headerEkuitas->id,
                'account_type'     => 'retained earning account',
                'account_method'   => 'credit',
                'account_group'    => null,
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_ekuitas->id,
            ],
            [
                'account_number'   => 4110,
                'name'             => 'Penjualan Produk Air Mineral',
                'description'      => 'Pendapatan hasil penjualan produk kemasan',
                'header_id'        => $headerPendapatan->id,
                'account_type'     => 'income',
                'account_method'   => 'credit',
                'account_group'    => 'sales',
                'cost_center_type' => 'all',
                'tbag_1'           => $tbag1_pendapatan->id,
            ],
            [
                'account_number'   => 4120,
                'name'             => 'Pendapatan Ongkos Kirim / Ekspedisi',
                'description'      => 'Jasa pengiriman produk ke toko/depo',
                'header_id'        => $headerPendapatan->id,
                'account_type'     => 'income',
                'account_method'   => 'credit',
                'account_group'    => 'sales',
                'cost_center_type' => 'depo',
                'tbag_1'           => $tbag1_pendapatan->id,
            ],
            [
                'account_number'   => 5110,
                'name'             => 'Beban Gaji & Upah Karyawan',
                'description'      => 'Gaji pokok, tunjangan, dan lembur operasional',
                'header_id'        => $headerBeban->id,
                'account_type'     => 'expense',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'depo',
                'tbag_1'           => $tbag1_beban->id,
            ],
            [
                'account_number'   => 5120,
                'name'             => 'Beban Bahan Bakar & Transportasi (BBM)',
                'description'      => 'BBM kendaraan logistik dan operasional depo',
                'header_id'        => $headerBeban->id,
                'account_type'     => 'expense',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'depo',
                'tbag_1'           => $tbag1_beban->id,
            ],
            [
                'account_number'   => 5130,
                'name'             => 'Beban Listrik, Air & Utilitas',
                'description'      => 'Tagihan listrik PLN, PDAM, dan internet depo',
                'header_id'        => $headerBeban->id,
                'account_type'     => 'expense',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'depo',
                'tbag_1'           => $tbag1_beban->id,
            ],
            [
                'account_number'   => 5140,
                'name'             => 'Beban Pemeliharaan & Servis Kendaraan',
                'description'      => 'Biaya servis rutin, oli, dan suku cadang armada',
                'header_id'        => $headerBeban->id,
                'account_type'     => 'expense',
                'account_method'   => 'debit',
                'account_group'    => null,
                'cost_center_type' => 'unit',
                'tbag_1'           => $tbag1_beban->id,
            ],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                [
                    'account_number' => $acc['account_number'],
                    'company_id'     => $company->id,
                ],
                $acc
            );
        }
    }
}
