{{-- resources/views/components/layout/navbar/user-menu.blade.php --}}
@props(['name' => 'Akuntan Utama', 'role' => 'Chief Accountant'])

<div class="relative" x-data="{ userMenuOpen: false }">
    <button @click="userMenuOpen = !userMenuOpen"
        class="flex items-center gap-2 p-1 rounded-xl hover:bg-emerald-50 dark:hover:bg-slate-800 transition-colors">
        <img class="w-8 h-8 rounded-xl object-cover ring-2 ring-emerald-500/20"
            src="https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=059669&color=fff"
            alt="User Avatar">
        <div class="hidden sm:flex flex-col text-left">
            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight">{{ $name }}</span>
            <span class="text-[10px] text-slate-400">{{ $role }}</span>
        </div>
        <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
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
