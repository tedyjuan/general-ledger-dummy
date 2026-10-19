{{-- resources/views/components/layout/navbar.blade.php --}}
<header
    class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-emerald-100/60 dark:border-slate-800 h-16 flex items-center justify-between px-4 lg:px-8">

    {{-- Kiri: toggle sidebar & search --}}
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen"
            class="hidden lg:flex p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-emerald-50 dark:hover:bg-slate-800 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
        </button>

        <button @click="mobileSidebarOpen = true"
            class="lg:hidden p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-emerald-50 dark:hover:bg-slate-800 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <x-layout.navbar.search />
    </div>

    {{-- Kanan --}}
    <div class="flex items-center gap-2 sm:gap-3">
        <x-layout.navbar.dark-mode-toggle />
        <x-layout.navbar.notifications />
        <x-layout.navbar.user-menu />
    </div>
</header>
