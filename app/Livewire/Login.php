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
    #[Validate('required|string', message: 'Username wajib diisi.')]
    public string $username = '';

    #[Validate('required|string', message: 'Password wajib diisi.')]
    public string $password = '';

    public function login()
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $credentials = [
            'username' => Str::lower(trim($this->username)),
            'password' => $this->password,
        ];

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($this->throttleKey());
            $this->reset('password');

            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        return $this->redirectIntended(default: '/dashboard');
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower(trim($this->username)) . '|' . request()->ip());
    }

    public function render()
    {
        return view('livewire.login');
    }
}
