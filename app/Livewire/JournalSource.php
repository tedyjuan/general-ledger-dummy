<?php

namespace App\Livewire;

use App\Livewire\Concerns\ActiveCompany;
use App\Livewire\Concerns\Otorisasi;
use App\Livewire\Concerns\WithToast;
use App\Models\Depo;
use App\Models\JournalSource as JournalSourceModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class JournalSource extends Component
{
    use WithPagination, WithToast, Otorisasi, ActiveCompany;

    // Harus sama dengan key menu di Casbin / config/navigation.php
    private const RESOURCE = 'journal-sources';

    private const SORTABLE = ['id', 'code', 'depo_code', 'description'];

    /* STATE TABEL */
    public string $search            = '';
    public string $filterCode        = '';
    public string $filterDepo        = '';
    public string $filterDescription = '';
    public int    $perPage           = 10;
    public string $sortField         = 'id';
    public string $sortDirection     = 'desc';

    /* STATE MODAL FORM (dikendalikan Livewire) */
    #[Locked]
    public bool $showForm = false;

    #[Locked]
    public ?int $selectedId = null;

    // Company saat form dibuka; dipakai untuk menolak simpan jika company sudah berganti
    #[Locked]
    public string $formCompany = '';

    /* STATE FORM (company_code selalu dari session) */
    public string $depo_code   = '';
    public string $code        = '';
    public string $description = '';

    protected array $messages = [
        'code.size'            => 'Kode Journal Source harus tepat 2 karakter.',
        'depo_code.required'   => 'Kode Depo wajib diisi.',
        'depo_code.exists'     => 'Depo tidak ditemukan pada perusahaan ini.',
        'code.required'        => 'Kode Journal Source wajib diisi.',
        'code.unique'          => 'Kode Journal Source sudah digunakan pada perusahaan ini.',
        'description.required' => 'Deskripsi wajib diisi.',
    ];

    /* HOOK TABEL */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }
    public function updatingFilterCode(): void
    {
        $this->resetPage();
    }
    public function updatingFilterDepo(): void
    {
        $this->resetPage();
    }
    public function updatingFilterDescription(): void
    {
        $this->resetPage();
    }
    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function refreshData(): void
    {
        $this->reset(['search', 'filterCode', 'filterDepo', 'filterDescription']);
        $this->sortField     = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();

        $this->toast('Data Journal Source berhasil dimuat ulang.', 'info');
    }

    /* TAMBAH / EDIT */
    public function create(): void
    {
        $this->izinkan(self::RESOURCE, 'create');
        $this->ensureSameCompany();

        // Reset dulu, baru buka modal
        $this->resetForm();
        $this->resetErrorBag();

        $this->formCompany = $this->tabCompany;
        $this->showForm    = true;
    }

    public function edit(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'update');
        $this->ensureSameCompany();

        $this->resetForm();
        $this->resetErrorBag();

        // Global scope: ID milik company lain otomatis 404
        $source = JournalSourceModel::findOrFail($id);

        $this->selectedId  = $source->id;
        $this->depo_code   = Str::upper($source->depo_code ?? '');
        $this->code        = Str::upper($source->code ?? '');
        $this->description = $source->description ?? '';

        $this->formCompany = $this->tabCompany;
        $this->showForm    = true;
    }

    public function save(): void
    {
        $this->izinkan(self::RESOURCE, $this->selectedId ? 'update' : 'create');
        $this->ensureSameCompany();

        // Company diganti (misal dari tab lain) saat form masih terbuka
        if ($this->formCompany !== $this->tabCompany) {
            $this->closeModal();
            $this->toast('Company berubah, form direset. Silakan isi ulang.', 'warning');

            return;
        }

        $company           = $this->tabCompany;
        $this->depo_code   = Str::upper(trim($this->depo_code));
        $this->code        = Str::upper(trim($this->code));
        $this->description = trim($this->description);

        // Rule langsung ke database (tidak lewat global scope),
        // jadi where company_code tetap wajib
        $uniqueKode = Rule::unique('journal_sources', 'code')
            ->where(fn($q) => $q
                ->where('company_code', $company)
                ->where('depo_code', $this->depo_code))
            ->ignore($this->selectedId);

        try {
            $validated = $this->validate([
                'depo_code'   => [
                    'required',
                    'string',
                    'max:20',
                    Rule::exists('depos', 'code')->where(fn($q) => $q->where('company_code', $company)),
                ],
                'code'        => ['required', 'string', 'size:2', $uniqueKode('code')],
                'description' => ['required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            $this->toast('Data belum valid, periksa kembali isian form.', 'error');
            throw $e;
        }

        // Company selalu dari session, bukan dari form
        $validated['company_code'] = $company;

        try {
            if ($this->selectedId) {
                $source = JournalSourceModel::findOrFail($this->selectedId);
                $source->update($validated);
                $this->toast("Journal Source '{$source->code}' berhasil diperbarui.");
            } else {
                $source = JournalSourceModel::create($validated);
                $this->toast("Journal Source '{$source->code}' berhasil ditambahkan.");
            }
        } catch (QueryException $e) {
            report($e);
            $this->toast('Gagal menyimpan data. Silakan coba lagi.', 'error');

            return;
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
        $this->resetForm();
    }

    /* HAPUS (modal konfirmasi tetap Alpine) */
    public function delete(int $id): void
    {
        $this->izinkan(self::RESOURCE, 'delete');
        $this->ensureSameCompany();

        try {
            // Global scope: ID milik company lain otomatis 404
            $source = JournalSourceModel::findOrFail($id);
            $code   = $source->code;

            $source->delete();

            $this->toast("Journal Source '{$code}' berhasil dihapus.");
        } catch (QueryException $e) {
            report($e);
            $this->toast('Journal Source tidak bisa dihapus karena masih dipakai data lain.', 'error');
        }

        $this->dispatch('delete-close');
    }

    private function resetForm(): void
    {
        $this->selectedId  = null;
        $this->formCompany = '';
        $this->depo_code   = '';
        $this->code        = '';
        $this->description = '';
    }

    /* RENDER */
    public function render()
    {
        $this->izinkan(self::RESOURCE, 'read');
        $this->ensureSameCompany();

        // Tidak ada where company_code: sudah ditangani global scope di model
        $sources = JournalSourceModel::query()
            ->search($this->search)
            ->likeIf('code', $this->filterCode)
            ->likeIf('depo_code', $this->filterDepo)
            ->likeIf('description', $this->filterDescription)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        // Depo hanya dimuat saat modal terbuka, selalu dari company aktif
        $depos = $this->showForm
            ? Depo::query()
            ->where('company_code', $this->tabCompany)
            ->orderBy('code')
            ->get(['code', 'name'])
            : collect();

        return view('livewire.master.journal-source', [
            'sources'       => $sources,
            'depos'         => $depos,
            'activeCompany' => $this->tabCompany,
        ]);
    }
}
