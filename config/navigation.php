<?php
// config/navigation.php
// PENTING: tambahkan di resources/css/app.css agar class icon terdeteksi Tailwind:
//   @source "../../config/navigation.php";

return [
    'menu' => [
        [
            'label' => 'Dashboard',
            'icon'  => 'icon-[tabler--layout-dashboard]',
            'href'  => '/dashboard',
            'match' => ['dashboard'],
        ],
        [
            'label'    => 'Master Data',
            'icon'     => 'icon-[tabler--database]',
            'children' => [
                ['label' => 'Depo',                  'href' => '/depo',      'match' => ['depo*']],
                ['label' => 'Tes',                   'href' => '/tes',       'match' => ['tes*']],
                ['label' => 'Chart of Accounts (COA)', 'href' => '/coa',     'match' => ['coa*']],
                ['label' => 'Pelanggan & Vendor',    'href' => '/kontak',    'match' => ['kontak*']],
                ['label' => 'Mata Uang (Currency)',  'href' => '/mata-uang', 'match' => ['mata-uang*']],
            ],
        ],
        [
            'label'    => 'Jurnal & Transaksi',
            'icon'     => 'icon-[tabler--file-text]',
            'children' => [
                ['label' => 'Jurnal Umum',        'href' => '/jurnal-umum',        'match' => ['jurnal-umum*']],
                ['label' => 'Jurnal Penyesuaian', 'href' => '/jurnal-penyesuaian', 'match' => ['jurnal-penyesuaian*']],
                ['label' => 'Kas & Bank',         'href' => '/kas-bank',           'match' => ['kas-bank*']],
            ],
        ],
        [
            'label'    => 'Laporan Keuangan',
            'icon'     => 'icon-[tabler--chart-bar]',
            'children' => [
                ['label' => 'Buku Besar',   'href' => '/buku-besar',   'match' => ['buku-besar*']],
                ['label' => 'Neraca Saldo', 'href' => '/neraca-saldo', 'match' => ['neraca-saldo*']],
                ['label' => 'Laba Rugi',    'href' => '/laba-rugi',    'match' => ['laba-rugi*']],
            ],
        ],
        [
            'label' => 'Pengaturan',
            'icon'  => 'icon-[tabler--settings]',
            'href'  => '/pengaturan',
            'match' => ['pengaturan*'],
        ],
    ],
];
