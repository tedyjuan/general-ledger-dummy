<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CompanySwitcher extends Component
{
    /** Daftar company (di-cache, jarang berubah) */
    public static function companies(): array
    {
        return Cache::remember('companies.switcher', 600, fn() => DB::table('companies')
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn($c) => ['code' => $c->code, 'name' => $c->name])
            ->all());
    }

    public function switch(string $code): void
    {
        $allowed = collect(self::companies())->pluck('code')->all();

        // Wajib divalidasi, jangan percaya nilai dari browser
        abort_unless(in_array($code, $allowed, true), 403);

        session(['company_code' => $code]);

        // Muat ulang halaman yang sedang dibuka
        $this->redirect(url()->previous('/'), navigate: true);
    }

    public function render()
    {
        // Di sinilah $companies dan $active dikirim ke view
        return view('components.layout.navbar.company-switcher', [
            'companies' => self::companies(),
            'active'    => session('company_code'),
        ]);
    }
}
