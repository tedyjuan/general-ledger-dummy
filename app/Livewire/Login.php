<?php

namespace App\Livewire;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Login')]
class Login extends Component
{
    /** Batas percobaan login gagal sebelum diblokir sementara */
    private const BATAS_PERCOBAAN = 5;

    #[Validate('required|string', message: 'Username wajib diisi.')]
    public string $username = '';

    #[Validate('required|string', message: 'Password wajib diisi.')]
    public string $password = '';

    public function login()
    {
        $this->validate();
        $this->pastikanBelumTerblokir();

        $kredensial = [
            'username' => $this->usernameBersih(),
            'password' => $this->password,
        ];

        if (! Auth::attempt($kredensial)) {
            RateLimiter::hit($this->kunciPembatas());
            $this->reset('password');

            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        RateLimiter::clear($this->kunciPembatas());
        session()->regenerate();

        return $this->redirectIntended(default: '/dashboard');
    }

    /**
     * Hentikan proses jika percobaan login sudah melebihi batas.
     */
    protected function pastikanBelumTerblokir(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciPembatas(), self::BATAS_PERCOBAAN)) {
            return;
        }

        event(new Lockout(request()));

        $detik = RateLimiter::availableIn($this->kunciPembatas());

        throw ValidationException::withMessages([
            'username' => "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik.",
        ]);
    }

    /**
     * Kunci unik pembatas percobaan: kombinasi username dan alamat IP.
     */
    protected function kunciPembatas(): string
    {
        return Str::transliterate($this->usernameBersih() . '|' . request()->ip());
    }

    /**
     * Username dalam huruf kecil tanpa spasi di awal/akhir.
     */
    protected function usernameBersih(): string
    {
        return Str::lower(trim($this->username));
    }

    public function render()
    {
        return view('livewire.login');
    }
}
