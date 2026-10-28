<div class="grid min-h-screen lg:grid-cols-2">

    {{-- PANEL KIRI: BRANDING --}}
    <div
        class="relative hidden overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">

        {{-- dekorasi --}}
        <div class="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-20 size-96 rounded-full bg-emerald-300/20 blur-3xl">
        </div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
            style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 24px 24px;">
        </div>

        {{-- logo --}}
        <div class="relative flex items-center gap-3">
            <div
                class="flex size-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/30 backdrop-blur">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.04v12.5m0-12.5C10.83 5.07 9.25 4.5 7.5 4.5S4.17 5.07 3 6.04v12.5c1.17-.97 2.75-1.54 4.5-1.54s3.33.57 4.5 1.54m0-12.5c1.17-.97 2.75-1.54 4.5-1.54s3.33.57 4.5 1.54v12.5c-1.17-.97-2.75-1.54-4.5-1.54s-3.33.57-4.5 1.54" />
                </svg>
            </div>
            <span class="text-lg font-semibold tracking-tight">General Ledger</span>
        </div>

        {{-- tagline --}}
        <div class="relative max-w-md">
            <h2 class="text-4xl font-bold leading-tight tracking-tight">
                Pembukuan yang rapi,<br>laporan yang akurat.
            </h2>
            <p class="mt-4 text-emerald-100/90">
                Kelola jurnal umum, trial balance, dan laporan keuangan semua perusahaan dalam satu tempat.
            </p>

            <ul class="mt-8 space-y-3 text-sm">
                @foreach (['Jurnal umum dan buku besar terintegrasi', 'Trial balance real-time per perusahaan', 'Akses sesuai peran pengguna'] as $item)
                    <li class="flex items-center gap-3">
                        <span
                            class="flex size-6 items-center justify-center rounded-full bg-white/20 ring-1 ring-white/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </span>
                        <span class="text-emerald-50">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="relative text-xs text-emerald-100/70">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Seluruh hak dilindungi.
        </p>
    </div>

    {{-- PANEL KANAN: FORM --}}
    <div class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md">

            {{-- logo versi mobile --}}
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.04v12.5m0-12.5C10.83 5.07 9.25 4.5 7.5 4.5S4.17 5.07 3 6.04v12.5c1.17-.97 2.75-1.54 4.5-1.54s3.33.57 4.5 1.54m0-12.5c1.17-.97 2.75-1.54 4.5-1.54s3.33.57 4.5 1.54v12.5c-1.17-.97-2.75-1.54-4.5-1.54s-3.33.57-4.5 1.54" />
                    </svg>
                </div>
                <span class="text-lg font-semibold tracking-tight text-slate-800 dark:text-white">General Ledger</span>
            </div>

            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Selamat datang kembali</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Masuk dengan username dan password Anda untuk melanjutkan.
                </p>
            </div>

            <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form wire:submit="login" class="card-body gap-5 p-6">

                    {{-- Username --}}
                    <div>
                        <label for="username"
                            class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Username
                        </label>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 start-3 z-10 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.12a7.5 7.5 0 0 1 15 0A17.9 17.9 0 0 1 12 21.75c-2.68 0-5.22-.59-7.5-1.63Z" />
                                </svg>
                            </span>
                            <input id="username" type="text" wire:model="username" placeholder="Masukkan username"
                                autocomplete="username" autofocus
                                class="input w-full ps-10 bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('username') is-invalid @enderror" />
                        </div>
                        @error('username')
                            <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div x-data="{ show: false }">
                        <label for="password"
                            class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Password
                        </label>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 start-3 z-10 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>
                            <input id="password" :type="show ? 'text' : 'password'" wire:model="password"
                                placeholder="Masukkan password" autocomplete="current-password"
                                class="input w-full ps-10 pe-11 bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white @error('password') is-invalid @enderror" />
                            <button type="button" @click="show = !show"
                                :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                                class="absolute inset-y-0 end-3 z-10 flex items-center text-slate-400 transition hover:text-emerald-600">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 8.2 7.36 5 12 5s8.58 3.2 9.96 6.68a1 1 0 0 1 0 .64C20.58 15.8 16.64 19 12 19s-8.58-3.2-9.96-6.68Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-5"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.24 4.24M9.88 5.09A9.77 9.77 0 0 1 12 5c4.64 0 8.58 3.2 9.96 6.68a1 1 0 0 1 0 .64 10.5 10.5 0 0 1-2.67 3.76M6.23 6.23A10.45 10.45 0 0 0 2.04 11.68a1 1 0 0 0 0 .64C3.42 15.8 7.36 19 12 19c1.6 0 3.1-.38 4.42-1.04" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit" wire:loading.attr="disabled" wire:target="login"
                        class="btn btn-block border-emerald-600 bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 hover:border-emerald-700 hover:bg-emerald-700">
                        <span wire:loading wire:target="login" class="loading loading-spinner loading-sm"></span>
                        <span wire:loading.remove wire:target="login">Masuk</span>
                        <span wire:loading wire:target="login">Memproses...</span>
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400 dark:text-slate-500 lg:hidden">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>
    </div>
</div>
