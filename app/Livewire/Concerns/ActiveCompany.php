<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Locked;

trait ActiveCompany
{
    // Company yang tampil di tab ini, dikunci agar tidak bisa diubah dari browser
    #[Locked]
    public string $tabCompany = '';

    public function mountActiveCompany(): void
    {
        $this->tabCompany = (string) session('company_code');
        abort_if($this->tabCompany === '', 403, 'Company belum dipilih.');
    }

    /**
     * Tab lama (TVIP) masih terbuka, tapi company diganti ke ASA di tab lain.
     * Aksi dari tab lama ditolak supaya tidak menyimpan ke company yang salah.
     */
    protected function ensureSameCompany(): void
    {
        abort_if(
            $this->tabCompany !== (string) session('company_code'),
            409,
            'Company aktif sudah berubah. Muat ulang halaman.'
        );
    }
}
