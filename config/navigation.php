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
            'key' => 'dashboard',
            'actions' => ['read']
        ],
        [
            'label'    => 'Master Data',
            'icon'     => 'icon-[tabler--database]',
            'children' => [
                ['label' => 'Depo',                     'href' => '/depo',                     'match' => ['depo*'],               'key' => 'depo'],
                ['label' => 'Divisi',                   'href' => '/divisi',                   'match' => ['divisi*'],             'key' => 'divisi'],
                ['label' => 'Segment',                  'href' => '/segment',                  'match' => ['segment*'],            'key' => 'segment'],
                ['label' => 'Departement',              'href' => '/departement',              'match' => ['departement*'],        'key' => 'departement'],
                ['label' => 'Trialbalance',             'href' => '/trial-balance',            'match' => ['trial-balance*'],      'key' => 'trial-balance'],
                ['label' => 'Tes',                      'href' => '/tes',                      'match' => ['tes*'],                'key' => 'tes'],
            ],
        ],
        [
            'label'    => 'Jurnal & Transaksi',
            'icon'     => 'icon-[tabler--file-text]',
            'children' => [
                ['label' => 'Jurnal Umum',        'href' => '/jurnal-umum',        'match' => ['jurnal-umum*'],    'key' => 'jurnal-umum'],
                ['label' => 'Jurnal Penyesuaian', 'href' => '/jurnal-penyesuaian', 'match' => ['jurnal-penyesuaian*'], 'key' => 'jurnal-penyesuaian'],
                ['label' => 'Kas & Bank',         'href' => '/kas-bank',           'match' => ['kas-bank*'],       'key' => 'kas-bank'],
            ],
        ],
        [
            'label'    => 'Laporan Keuangan',
            'icon'     => 'icon-[tabler--chart-bar]',
            'children' => [
                ['label' => 'Buku Besar',   'href' => '/buku-besar',   'match' => ['buku-besar*'],    'key' => 'buku-besar'],
                ['label' => 'Neraca Saldo', 'href' => '/neraca-saldo', 'match' => ['neraca-saldo*'],  'key' => 'neraca-saldo'],
                ['label' => 'Laba Rugi',    'href' => '/laba-rugi',    'match' => ['laba-rugi*'],     'key' => 'laba-rugi'],
            ],
        ],
        [
            'label' => 'Pengaturan',
            'icon'  => 'icon-[tabler--settings]',
            'children' => [
                [
                    'label' => 'Hak Akses',
                    'href' => '/permissions',
                    'match' => ['permissions*'],
                    'key' => 'permissions',
                    'actions' => ['manage']
                ],
            ],
        ],
    ],
];
