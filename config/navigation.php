<?php
// config/navigation.php
// PENTING: tambahkan di resources/css/app.css agar class icon terdeteksi Tailwind:
//   @source "../../config/navigation.php";

return [
    'menu' => [

        // Menu tunggal: 'key' wajib ada. 'actions' boleh dihilangkan (default ['read'])
        [
            'label' => 'Dashboard',
            'icon'  => 'icon-[tabler--layout-dashboard]',
            'href'  => '/dashboard',
            'match' => ['dashboard'],
            'key'   => 'dashboard',
        ],

        // Dropdown: TIDAK perlu key sendiri.
        // Otomatis hilang kalau semua child tidak diizinkan.
        [
            'label'    => 'Master Data',
            'icon'     => 'icon-[tabler--database]',
            'children' => [
                ['label' => 'Depo',         'href' => '/depo',          'match' => ['depo*'],          'key' => 'depo'],
                ['label' => 'Divisi',       'href' => '/divisi',        'match' => ['divisi*'],        'key' => 'divisi'],
                ['label' => 'Segment',      'href' => '/segment',       'match' => ['segment*'],       'key' => 'segment'],
                ['label' => 'Departement',  'href' => '/departement',   'match' => ['departement*'],   'key' => 'departement'],
                ['label' => 'Trial Balance', 'href' => '/trial-balance', 'match' => ['trial-balance*'], 'key' => 'trial-balance'],
                ['label' => 'Tes',          'href' => '/tes',           'match' => ['tes*'],           'key' => 'tes'],
            ],
        ],

        [
            'label'    => 'Jurnal & Transaksi',
            'icon'     => 'icon-[tabler--file-text]',
            'children' => [
                ['label' => 'Jurnal Umum',        'href' => '/jurnal-umum',        'match' => ['jurnal-umum*'],        'key' => 'jurnal-umum'],
                ['label' => 'Jurnal Penyesuaian', 'href' => '/jurnal-penyesuaian', 'match' => ['jurnal-penyesuaian*'], 'key' => 'jurnal-penyesuaian'],
                ['label' => 'Kas & Bank',         'href' => '/kas-bank',           'match' => ['kas-bank*'],           'key' => 'kas-bank'],
            ],
        ],

        [
            'label'    => 'Laporan Keuangan',
            'icon'     => 'icon-[tabler--chart-bar]',
            'children' => [
                ['label' => 'Buku Besar',   'href' => '/buku-besar',   'match' => ['buku-besar*'],   'key' => 'buku-besar'],
                ['label' => 'Neraca Saldo', 'href' => '/neraca-saldo', 'match' => ['neraca-saldo*'], 'key' => 'neraca-saldo'],
                ['label' => 'Laba Rugi',    'href' => '/laba-rugi',    'match' => ['laba-rugi*'],    'key' => 'laba-rugi'],
            ],
        ],

        [
            'label'    => 'Pengaturan',
            'icon'     => 'icon-[tabler--settings]',
            'children' => [
                [
                    'label'   => 'Akses per Menu',
                    'href'    => '/akses-menu',
                    'match'   => ['akses-menu*'],
                    'key'     => 'permissions',
                    'actions' => ['manage'],   // route memakai manage, bukan read
                ],
            ],
        ],
    ],
];
