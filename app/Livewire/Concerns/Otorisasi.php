<?php

namespace App\Livewire\Concerns;

trait Otorisasi
{
    protected function izinkan(string $key, string $aksi): void
    {
        abort_unless(
            auth()->user()?->izin($key, $aksi) ?? false,
            403,
            'Anda tidak memiliki otorisasi untuk mengakses halaman ini.'
        );
    }
}
