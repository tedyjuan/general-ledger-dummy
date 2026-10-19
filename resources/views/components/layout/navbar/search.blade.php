{{-- resources/views/components/layout/navbar/search.blade.php --}}
<div class="relative hidden sm:block w-64 lg:w-80">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </span>
    <input type="text" placeholder="Cari akun, jurnal, atau ref no..."
        class="w-full pl-9 pr-4 py-1.5 text-xs bg-emerald-50/40 dark:bg-slate-800 border border-emerald-100 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 text-slate-700 dark:text-slate-200 placeholder-slate-400 transition-all">
</div>
