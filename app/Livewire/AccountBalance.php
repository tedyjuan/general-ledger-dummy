<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Coa as CoaModel;
use App\Models\PostingBalance;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AccountBalance extends Component
{
    use WithToast, Otorisasi, ActiveCompany;

    // Harus sama dengan key menu di Casbin / config/navigation.php
    private const RESOURCE = 'account-balance';

    private const MAX_ACCOUNTS = 500;

    /* FILTER (belum berlaku sebelum tombol cari diklik) */
    public string $year             = '';
    public string $depo_code        = '';
    public string $cost_center_code = '';

    /* FILTER YANG SUDAH DITERAPKAN (hanya server yang boleh mengubah) */
    #[Locked]
    public bool $searched = false;

    #[Locked]
    public string $aYear = '';

    #[Locked]
    public string $aDepo = '';

    #[Locked]
    public string $aCc = '';

    // company saat pencarian dilakukan; kalau berbeda = company di-switch
    #[Locked]
    public string $listCompany = '';

    /* DAFTAR AKUN */
    public string $accountSearch = '';

    #[Locked]
    public string $selectedCoa = '';

    /* SALDO AKUN TERPILIH */
    #[Locked]
    public bool $editing = false;

    public string $opening = '0';

    // key 1..12
    public array $debit  = [];
    public array $credit = [];

    protected array $messages = [
        'year.required'             => 'Tahun wajib dipilih.',
        'year.regex'                => 'Format tahun tidak valid.',
        'depo_code.required'        => 'Depo wajib dipilih.',
        'depo_code.exists'          => 'Depo tidak valid untuk perusahaan ini.',
        'cost_center_code.required' => 'Cost center wajib dipilih.',
        'cost_center_code.exists'   => 'Cost center tidak valid untuk perusahaan ini.',
    ];

    public function mount(): void
    {
        $this->year = (string) Carbon::now()->year;

        for ($m = 1; $m <= 12; $m++) {
            $this->debit[$m]  = '0';
            $this->credit[$m] = '0';
        }
    }

    /* CARI */
    public function cari(): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        $this->validate([
            'year'             => ['required', 'regex:/^\d{4}$/'],
            'depo_code'        => ['required', Rule::exists('depos', 'code')->where('company_code', $company)],
            'cost_center_code' => ['required', Rule::exists('cost_centers', 'cost_center')->where('company_code', $company)],
        ]);

        $this->aYear       = $this->year;
        $this->aDepo       = $this->depo_code;
        $this->aCc         = $this->cost_center_code;
        $this->listCompany = $company;
        $this->searched    = true;

        $this->accountSearch = '';
        $this->selectedCoa   = '';
        $this->editing       = false;
        $this->opening       = '0';

        for ($m = 1; $m <= 12; $m++) {
            $this->debit[$m]  = '0';
            $this->credit[$m] = '0';
        }
    }
    public function resetCari(): void
    {
        $this->izinkan(self::RESOURCE, 'read');

        $this->searched         = false;
        $this->aYear            = '';
        $this->aDepo            = '';
        $this->aCc              = '';
        $this->listCompany      = '';
        $this->selectedCoa      = '';
        $this->editing          = false;
        $this->accountSearch    = '';
        $this->depo_code        = '';
        $this->cost_center_code = '';
        $this->opening          = '0';

        for ($m = 1; $m <= 12; $m++) {
            $this->debit[$m]  = '0';
            $this->credit[$m] = '0';
        }

        $this->resetErrorBag();
    }
    /* PILIH AKUN */
    public function selectAccount(string $coa): void
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        if (! $this->searched) {
            return;
        }

        $company = (string) $this->tabCompany;

        $ada = CoaModel::query()
            ->where('company_code', $company)
            ->whereNotNull('header_code')
            ->where('account_number', $coa)
            ->exists();

        if (! $ada) {
            $this->toast('Akun tidak valid untuk perusahaan ini.', 'error');

            return;
        }

        $this->selectedCoa = $coa;
        $this->editing     = false;

        $this->loadBalance($company);
    }

    private function loadBalance(string $company): void
    {
        $rows = PostingBalance::query()
            ->where('company_code', $company)
            ->where('depo_code', $this->aDepo)
            ->where('year', $this->aYear)
            ->where('cost_center_code', $this->aCc)
            ->where('coa_code', $this->selectedCoa)
            ->get()
            ->keyBy('period');

        // saldo awal tahun = opening_balance periode 01
        $pertama       = $rows->get('01');
        $this->opening = $pertama ? (string) $pertama->opening_balance : '0';

        for ($m = 1; $m <= 12; $m++) {
            $baris            = $rows->get(str_pad((string) $m, 2, '0', STR_PAD_LEFT));
            $this->debit[$m]  = $baris ? (string) $baris->debit : '0';
            $this->credit[$m] = $baris ? (string) $baris->credit : '0';
        }
    }

    /* EDIT */
    public function startEdit(): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        if (! $this->searched || $this->selectedCoa === '') {
            return;
        }

        $this->editing = true;
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->resetErrorBag();

        if ($this->searched && $this->selectedCoa !== '') {
            $this->loadBalance((string) $this->tabCompany);
        }

        $this->editing = false;
    }

    public function save(): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        if (! $this->searched || $this->selectedCoa === '' || $this->listCompany !== $company) {
            $this->toast('Pilih akun terlebih dahulu.', 'error');

            return;
        }

        // pastikan akun, depo, dan cost center masih valid untuk company ini
        $akunAda = CoaModel::query()
            ->where('company_code', $company)
            ->whereNotNull('header_code')
            ->where('account_number', $this->selectedCoa)
            ->exists();

        $depoAda = DB::table('depos')
            ->where('company_code', $company)
            ->where('code', $this->aDepo)
            ->exists();

        $ccAda = DB::table('cost_centers')
            ->where('company_code', $company)
            ->where('cost_center', $this->aCc)
            ->exists();

        if (! $akunAda || ! $depoAda || ! $ccAda) {
            $this->toast('Akun, depo, atau cost center tidak valid.', 'error');

            return;
        }

        $this->resetErrorBag();
        $salah = false;

        // Saldo awal: boleh minus. Buang spasi, titik, koma, dan "Rp"
        $opening = str_replace('.', '', trim($this->opening));
        if ($opening === '' || $opening === '-') {
            $opening = '0';
        }
        if (! preg_match('/^-?\d{1,15}$/', $opening)) {
            $this->addError('opening', 'Saldo awal harus berupa angka bulat.');
            $salah = true;
        }

        $running = $salah ? 0 : (int) $opening;
        $rows    = [];

        for ($m = 1; $m <= 12; $m++) {
            $d = preg_replace('/[\s.,]|Rp/i', '', (string) ($this->debit[$m] ?? ''));
            $c = preg_replace('/[\s.,]|Rp/i', '', (string) ($this->credit[$m] ?? ''));

            if ($d === '') {
                $d = '0';
            }
            if ($c === '') {
                $c = '0';
            }

            if (! preg_match('/^\d{1,15}$/', $d)) {
                $this->addError("debit.$m", 'Debit harus angka bulat, tidak boleh minus.');
                $salah = true;
            }
            if (! preg_match('/^\d{1,15}$/', $c)) {
                $this->addError("credit.$m", 'Kredit harus angka bulat, tidak boleh minus.');
                $salah = true;
            }

            if ($salah) {
                continue;
            }

            $rows[] = [
                'company_code'     => $company,
                'depo_code'        => $this->aDepo,
                'year'             => $this->aYear,
                'period'           => str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                'cost_center_code' => $this->aCc,
                'coa_code'         => $this->selectedCoa,
                'opening_balance'  => $running,
                'debit'            => (int) $d,
                'credit'           => (int) $c,
            ];

            // saldo awal bulan berikutnya = saldo awal + debit - kredit
            $running += ((int) $d - (int) $c);
        }

        if ($salah) {
            $this->toast('Data belum valid, periksa kembali isian.', 'error');

            return;
        }

        try {
            DB::transaction(function () use ($rows) {
                PostingBalance::query()->upsert(
                    $rows,
                    ['company_code', 'depo_code', 'year', 'period', 'cost_center_code', 'coa_code'],
                    ['opening_balance', 'debit', 'credit']
                );
            });
        } catch (QueryException $e) {
            Log::error($e->getMessage(), ['exception' => $e]);
            $this->toast('Gagal menyimpan saldo. Silakan coba lagi.', 'error');

            return;
        }

        $this->loadBalance($company);
        $this->editing = false;

        $this->toast("Saldo akun {$this->selectedCoa} tahun {$this->aYear} berhasil disimpan.");
    }

    /* RENDER */
    public function render()
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        $company = (string) $this->tabCompany;

        // Company di-switch: hasil pencarian & pilihan dibuang
        if ($this->searched && $this->listCompany !== $company) {
            $this->searched      = false;
            $this->selectedCoa   = '';
            $this->editing       = false;
            $this->depo_code     = '';
            $this->cost_center_code = '';
            $this->toast('Perusahaan diganti, silakan cari ulang.', 'info');
        }

        // Opsi dropdown filter
        $depoOptions = DB::table('depos')
            ->where('company_code', $company)
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn($d) => ['v' => (string) $d->code, 'n' => (string) $d->name])
            ->all();

        $ccOptions = DB::table('cost_centers')
            ->where('company_code', $company)
            ->orderBy('cost_center')
            ->get(['cost_center', 'name'])
            ->map(fn($c) => ['v' => (string) $c->cost_center, 'n' => (string) $c->name])
            ->all();

        $tahunNow = (int) Carbon::now()->year;
        $years    = [];
        for ($y = $tahunNow + 1; $y >= $tahunNow - 6; $y--) {
            $years[] = (string) $y;
        }

        $accounts   = new Collection();
        $hasBalance = [];
        $depoNama   = '';
        $ccNama     = '';

        if ($this->searched) {
            $query = CoaModel::query()
                ->where('company_code', $company)
                ->whereNotNull('header_code');

            if (trim($this->accountSearch) !== '') {
                $kata = '%' . trim($this->accountSearch) . '%';
                $query->where(function ($q) use ($kata) {
                    $q->where('account_number', 'like', $kata)
                        ->orWhere('name', 'like', $kata);
                });
            }

            $accounts = $query
                ->orderBy('account_number')
                ->limit(self::MAX_ACCOUNTS)
                ->get(['account_number', 'name']);

            // akun yang sudah punya data saldo pada filter ini
            $hasBalance = PostingBalance::query()
                ->where('company_code', $company)
                ->where('depo_code', $this->aDepo)
                ->where('year', $this->aYear)
                ->where('cost_center_code', $this->aCc)
                ->distinct()
                ->pluck('coa_code')
                ->flip()
                ->all();

            foreach ($depoOptions as $o) {
                if ($o['v'] === $this->aDepo) {
                    $depoNama = $o['n'];
                }
            }
            foreach ($ccOptions as $o) {
                if ($o['v'] === $this->aCc) {
                    $ccNama = $o['n'];
                }
            }
        }

        // Angka untuk tampilan (opening, total debit/kredit, closing)
        $openingNum = (int) preg_replace('/[\s.,]|Rp/i', '', $this->opening);
        $debitNum   = [];
        $creditNum  = [];
        $totalDebit  = 0;
        $totalCredit = 0;

        for ($m = 1; $m <= 12; $m++) {
            $debitNum[$m]  = (int) preg_replace('/[^0-9]/', '', (string) ($this->debit[$m] ?? '0'));
            $creditNum[$m] = (int) preg_replace('/[^0-9]/', '', (string) ($this->credit[$m] ?? '0'));
            $totalDebit   += $debitNum[$m];
            $totalCredit  += $creditNum[$m];
        }

        $closing = $openingNum + $totalDebit - $totalCredit;

        return View::make('livewire.task_setup.account-balance', [
            'activeCompany' => $company,
            'years'         => $years,
            'depoOptions'   => $depoOptions,
            'ccOptions'     => $ccOptions,
            'accounts'      => $accounts,
            'hasBalance'    => $hasBalance,
            'depoNama'      => $depoNama,
            'ccNama'        => $ccNama,
            'openingNum'    => $openingNum,
            'debitNum'      => $debitNum,
            'creditNum'     => $creditNum,
            'totalDebit'    => $totalDebit,
            'totalCredit'   => $totalCredit,
            'closing'       => $closing,
            'maxAccounts'   => self::MAX_ACCOUNTS,
        ]);
    }
}
