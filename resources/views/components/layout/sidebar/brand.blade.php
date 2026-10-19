{{-- resources/views/components/layout/sidebar/brand.blade.php --}}
<div
    class="h-16 flex items-center justify-between px-4 border-b border-emerald-100/60 dark:border-slate-800 bg-white dark:bg-slate-900">
    <a href="/" class="flex items-center gap-3 overflow-hidden">
        <div
            class="w-10 h-10 rounded-xl bg-emerald-600 dark:bg-emerald-500 flex items-center justify-center text-white shadow-sm shadow-emerald-600/30 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        </div>
        <div x-show="sidebarOpen" x-transition.opacity class="flex flex-col whitespace-nowrap">
            <span class="font-bold text-base tracking-tight text-slate-800 dark:text-white">GL Ledger</span>
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold">
                Accounting App
            </span>
        </div>
    </a>

    <button @click="mobileSidebarOpen = false"
        class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>
