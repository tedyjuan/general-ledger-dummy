<!DOCTYPE html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
    darkMode: false,

    init() {
        try {
            this.darkMode = localStorage.getItem('darkMode') === 'true';
        } catch (e) {
            this.darkMode = false;
        }

        this.applyTheme();
    },

    toggleDarkMode() {
        this.darkMode = !this.darkMode;

        try {
            localStorage.setItem('darkMode', String(this.darkMode));
        } catch (e) {
            // Tema tetap bisa berubah selama halaman terbuka.
        }

        this.applyTheme();
    },

    applyTheme() {
        document.documentElement.classList.toggle('dark', this.darkMode);

        document.documentElement.setAttribute(
            'data-theme',
            this.darkMode ? 'dark' : 'light'
        );
    }
}" :class="{ 'dark': darkMode }"
    :data-theme="darkMode ? 'dark' : 'light'" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.ico">
    <title>{{ $title ?? 'General Ledger System' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>


<body
    class="h-full antialiased text-slate-700 dark:text-slate-200 bg-[#F8FAF8] dark:bg-slate-950 transition-colors duration-200"
    x-data="{
        sidebarOpen: true,
        mobileSidebarOpen: false
    }">

    <div class="min-h-screen flex flex-col">

        <!-- ================= SIDEBAR MOBILE (OVERLAY) ================= -->
        <div x-show="mobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="gl-sidebar-overlay" @click="mobileSidebarOpen = false" x-cloak>
        </div>

        <!-- ================= SIDEBAR CONTAINER (PUTIH / DARK) ================= -->
        <aside
            class="fixed top-0 bottom-0 left-0 z-50 flex flex-col bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-r border-emerald-100/80 dark:border-slate-800 shadow-xs transition-all duration-300 ease-in-out"
            :class="{
                'w-64': sidebarOpen,
                'w-20': !sidebarOpen,
                'translate-x-0': mobileSidebarOpen,
                '-translate-x-full lg:translate-x-0': !mobileSidebarOpen
            }">

            <!-- Logo & Brand Header -->
            <div
                class="h-16 flex items-center justify-between px-4 border-b border-emerald-100/60 dark:border-slate-800 bg-white dark:bg-slate-900">
                <a href="/" class="flex items-center gap-3 overflow-hidden">
                    <div
                        class="w-10 h-10 rounded-xl bg-emerald-600 dark:bg-emerald-500 flex items-center justify-center text-white shadow-sm shadow-emerald-600/30 shrink-0">
                        <!-- Icon Ledger -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div x-show="sidebarOpen" x-transition.opacity class="flex flex-col whitespace-nowrap">
                        <span class="font-bold text-base tracking-tight text-slate-800 dark:text-white">GL Ledger</span>
                        <span
                            class="text-[10px] text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold">Accounting
                            App</span>
                    </div>
                </a>

                <!-- Mobile Close Button -->
                <button @click="mobileSidebarOpen = false"
                    class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>


            <!-- Navigation Menu -->
            <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4">

                {{-- Dashboard --}}
                <a href="/dashboard"
                    class="gl-sidebar-link {{ request()->is('dashboard') ? 'gl-sidebar-link-active' : '' }}"
                    :class="{ 'gl-sidebar-collapsed': !sidebarOpen }" :title="!sidebarOpen ? 'Dashboard' : ''">
                    <span class="icon-[tabler--layout-dashboard] gl-sidebar-icon"></span>
                    <span x-show="sidebarOpen" class="gl-sidebar-label">Dashboard</span>
                </a>

                {{-- Master Data --}}
                <div x-data="{
                    open: {{ request()->is('depo*', 'tes*', 'coa*', 'kontak*', 'mata-uang*') ? 'true' : 'false' }},
                
                    toggle() {
                        // Sidebar mini: besarkan dulu & buka submenu
                        if (!this.sidebarOpen) {
                            this.sidebarOpen = true;
                            this.open = true;
                            return;
                        }
                        this.open = !this.open;
                    }
                }">
                    <button type="button" @click="toggle()" class="gl-sidebar-dropdown"
                        :class="{ 'gl-sidebar-collapsed': !sidebarOpen }" :title="!sidebarOpen ? 'Master Data' : ''">
                        <span class="icon-[tabler--database] gl-sidebar-icon"></span>
                        <span x-show="sidebarOpen" class="gl-sidebar-label">Master Data</span>
                        <span x-show="sidebarOpen" class="icon-[tabler--chevron-down] gl-sidebar-chevron"
                            :class="{ 'rotate-180': open }"></span>
                    </button>

                    <div x-show="open && sidebarOpen" x-collapse class="gl-sidebar-submenu">
                        <a href="/depo"
                            class="gl-sidebar-submenu-link {{ request()->is('depo*') ? 'gl-sidebar-submenu-link-active' : '' }}">Depo</a>
                        <a href="/tes"
                            class="gl-sidebar-submenu-link {{ request()->is('tes*') ? 'gl-sidebar-submenu-link-active' : '' }}">Tes</a>
                        <a href="/coa"
                            class="gl-sidebar-submenu-link {{ request()->is('coa*') ? 'gl-sidebar-submenu-link-active' : '' }}">Chart
                            of Accounts (COA)</a>
                        <a href="/kontak"
                            class="gl-sidebar-submenu-link {{ request()->is('kontak*') ? 'gl-sidebar-submenu-link-active' : '' }}">Pelanggan
                            &amp; Vendor</a>
                        <a href="/mata-uang"
                            class="gl-sidebar-submenu-link {{ request()->is('mata-uang*') ? 'gl-sidebar-submenu-link-active' : '' }}">Mata
                            Uang (Currency)</a>
                    </div>
                </div>

                {{-- Jurnal & Transaksi --}}
                <div x-data="{
                    open: {{ request()->is('jurnal-umum*', 'jurnal-penyesuaian*', 'kas-bank*') ? 'true' : 'false' }},
                
                    toggle() {
                        if (!this.sidebarOpen) {
                            this.sidebarOpen = true;
                            this.open = true;
                            return;
                        }
                        this.open = !this.open;
                    }
                }">

                    <button type="button" @click="toggle()" class="gl-sidebar-dropdown"
                        :class="{ 'gl-sidebar-collapsed': !sidebarOpen }"
                        :title="!sidebarOpen ? 'Jurnal & Transaksi' : ''">
                        <span class="icon-[tabler--file-text] gl-sidebar-icon"></span>
                        <span x-show="sidebarOpen" class="gl-sidebar-label">Jurnal &amp; Transaksi</span>
                        <span x-show="sidebarOpen" class="icon-[tabler--chevron-down] gl-sidebar-chevron"
                            :class="{ 'rotate-180': open }"></span>
                    </button>

                    <div x-show="open && sidebarOpen" x-collapse class="gl-sidebar-submenu">
                        <a href="/jurnal-umum"
                            class="gl-sidebar-submenu-link {{ request()->is('jurnal-umum*') ? 'gl-sidebar-submenu-link-active' : '' }}">Jurnal
                            Umum</a>
                        <a href="/jurnal-penyesuaian"
                            class="gl-sidebar-submenu-link {{ request()->is('jurnal-penyesuaian*') ? 'gl-sidebar-submenu-link-active' : '' }}">Jurnal
                            Penyesuaian</a>
                        <a href="/kas-bank"
                            class="gl-sidebar-submenu-link {{ request()->is('kas-bank*') ? 'gl-sidebar-submenu-link-active' : '' }}">Kas
                            &amp; Bank</a>
                    </div>
                </div>

                {{-- Laporan Keuangan --}}
                <div x-data="{
                    open: {{ request()->is('buku-besar*', 'neraca-saldo*', 'laba-rugi*') ? 'true' : 'false' }},
                
                    toggle() {
                        if (!this.sidebarOpen) {
                            this.sidebarOpen = true;
                            this.open = true;
                            return;
                        }
                        this.open = !this.open;
                    }
                }">

                    <button type="button" @click="toggle()" class="gl-sidebar-dropdown"
                        :class="{ 'gl-sidebar-collapsed': !sidebarOpen }"
                        :title="!sidebarOpen ? 'Laporan Keuangan' : ''">
                        <span class="icon-[tabler--chart-bar] gl-sidebar-icon"></span>
                        <span x-show="sidebarOpen" class="gl-sidebar-label">Laporan Keuangan</span>
                        <span x-show="sidebarOpen" class="icon-[tabler--chevron-down] gl-sidebar-chevron"
                            :class="{ 'rotate-180': open }"></span>
                    </button>

                    <div x-show="open && sidebarOpen" x-collapse class="gl-sidebar-submenu">
                        <a href="/buku-besar"
                            class="gl-sidebar-submenu-link {{ request()->is('buku-besar*') ? 'gl-sidebar-submenu-link-active' : '' }}">Buku
                            Besar</a>
                        <a href="/neraca-saldo"
                            class="gl-sidebar-submenu-link {{ request()->is('neraca-saldo*') ? 'gl-sidebar-submenu-link-active' : '' }}">Neraca
                            Saldo</a>
                        <a href="/laba-rugi"
                            class="gl-sidebar-submenu-link {{ request()->is('laba-rugi*') ? 'gl-sidebar-submenu-link-active' : '' }}">Laba
                            Rugi</a>
                    </div>
                </div>

                {{-- Pengaturan --}}
                <a href="/pengaturan"
                    class="gl-sidebar-link {{ request()->is('pengaturan*') ? 'gl-sidebar-link-active' : '' }}"
                    :class="{ 'gl-sidebar-collapsed': !sidebarOpen }" :title="!sidebarOpen ? 'Pengaturan' : ''">
                    <span class="icon-[tabler--settings] gl-sidebar-icon"></span>
                    <span x-show="sidebarOpen" class="gl-sidebar-label">Pengaturan</span>
                </a>

            </nav>
            <!-- End Navigation Menu -->


            <!-- Status Card Widget (Soft Green Accent) -->
            <div x-show="sidebarOpen"
                class="p-3 m-3 rounded-2xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50 text-xs">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-emerald-800 dark:text-emerald-300 font-medium">Periode Aktif</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-600 text-white dark:bg-emerald-500/20 dark:text-emerald-300 font-semibold">Open</span>
                </div>
                <div class="font-bold text-slate-800 dark:text-white">Oktober 2026</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Semua jurnal terverifikasi</div>
            </div>
        </aside>

        <!-- ================= MAIN WRAPPER (TOPBAR + CONTENT + FOOTER) ================= -->
        <div class="flex-1 flex flex-col transition-all duration-300"
            :class="{ 'lg:pl-64': sidebarOpen, 'lg:pl-20': !sidebarOpen }">

            <!-- ================= TOPBAR NAVBAR ================= -->
            <header
                class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-emerald-100/60 dark:border-slate-800 h-16 flex items-center justify-between px-4 lg:px-8">

                <!-- Left: Sidebar Toggle & Search -->
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen"
                        class="hidden lg:flex p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-emerald-50 dark:hover:bg-slate-800 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </button>

                    <button @click="mobileSidebarOpen = true"
                        class="lg:hidden p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-emerald-50 dark:hover:bg-slate-800 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Search Input -->
                    <div class="relative hidden sm:block w-64 lg:w-80">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" placeholder="Cari akun, jurnal, atau ref no..."
                            class="w-full pl-9 pr-4 py-1.5 text-xs bg-emerald-50/40 dark:bg-slate-800 border border-emerald-100 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 text-slate-700 dark:text-slate-200 placeholder-slate-400 transition-all">
                    </div>
                </div>

                <!-- Right: Dark Mode, Quick Action, Notifications, User Profile -->
                <div class="flex items-center gap-2 sm:gap-3">

                    <!-- DARK MODE TOGGLE BUTTON -->

                    <button type="button" @click="toggleDarkMode()"
                        class="p-2 rounded-xl text-slate-500 dark:text-slate-400
                                hover:bg-emerald-50 dark:hover:bg-slate-800
                                hover:text-emerald-700 dark:hover:text-emerald-400
                                transition-colors"
                        :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                        title="Beralih Mode Gelap/Terang">

                        <svg x-show="darkMode" x-cloak class="w-5 h-5 text-amber-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>

                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>


                    <!-- Notification Button -->
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button @click="notifOpen = !notifOpen"
                            class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-emerald-50 dark:hover:bg-slate-800 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"></span>
                        </button>

                        <!-- Notification Dropdown -->
                        <div x-show="notifOpen" @click.outside="notifOpen = false" x-transition
                            class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 py-2 z-50"
                            x-cloak>
                            <div
                                class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                <span
                                    class="font-semibold text-xs text-slate-700 dark:text-slate-200">Notifikasi</span>
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">Tandai
                                    Dibaca</span>
                            </div>
                            <div class="max-h-60 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800">
                                <a href="#"
                                    class="block px-4 py-2.5 hover:bg-emerald-50/50 dark:hover:bg-slate-800/60 transition-colors">
                                    <p class="text-xs font-medium text-slate-800 dark:text-slate-200">Jurnal
                                        Penyesuaian Disetujui</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">2 menit yang lalu • Ref:
                                        #JV-202610-004</p>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center gap-2 p-1 rounded-xl hover:bg-emerald-50 dark:hover:bg-slate-800 transition-colors">
                            <img class="w-8 h-8 rounded-xl object-cover ring-2 ring-emerald-500/20"
                                src="https://ui-avatars.com/api/?name=Akuntan+Utama&background=059669&color=fff"
                                alt="User Avatar">
                            <div class="hidden sm:flex flex-col text-left">
                                <span
                                    class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight">Akuntan
                                    Utama</span>
                                <span class="text-[10px] text-slate-400">Chief Accountant</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="userMenuOpen" @click.outside="userMenuOpen = false" x-transition
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 py-1 z-50 text-xs"
                            x-cloak>
                            <a href="#"
                                class="block px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-emerald-50 dark:hover:bg-slate-800 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">Profil
                                Saya</a>
                            <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-4 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">Keluar
                                    (Logout)</button>
                            </form>
                        </div>
                    </div>

                </div>
            </header>

            <!-- ================= MAIN CONTENT AREA ================= -->
            <main class="flex-1 p-4 lg:p-8 max-w-7xl w-full mx-auto">
                @if (isset($header))
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endif

                {{ $slot }}
            </main>

            <!-- ================= FOOTER ================= -->
            <footer
                class="bg-white/60 dark:bg-slate-900/60 border-t border-emerald-100/60 dark:border-slate-800 py-4 px-6 text-xs text-slate-500 dark:text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2 mt-auto">
                <div>
                    &copy; {{ date('Y') }} <span
                        class="font-semibold text-emerald-700 dark:text-emerald-400">General Ledger System</span>.
                </div>
                <div class="flex items-center gap-4 text-slate-400 dark:text-slate-500">
                    <a href="#"
                        class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Bantuan</a>
                    <a href="#"
                        class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Ketentuan</a>
                </div>
            </footer>

        </div>
    </div>

    @livewireScripts
</body>

</html>
