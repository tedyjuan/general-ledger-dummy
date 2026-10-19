{{-- resources/views/components/layout/navbar/notifications.blade.php --}}
{{-- Nanti bisa diganti Livewire component: <livewire:notifications /> --}}
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

    <div x-show="notifOpen" @click.outside="notifOpen = false" x-transition
        class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 py-2 z-50"
        x-cloak>
        <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <span class="font-semibold text-xs text-slate-700 dark:text-slate-200">Notifikasi</span>
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">Tandai Dibaca</span>
        </div>
        <div class="max-h-60 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800">
            <a href="#"
                class="block px-4 py-2.5 hover:bg-emerald-50/50 dark:hover:bg-slate-800/60 transition-colors">
                <p class="text-xs font-medium text-slate-800 dark:text-slate-200">Jurnal Penyesuaian Disetujui</p>
                <p class="text-[10px] text-slate-400 mt-0.5">2 menit yang lalu • Ref: #JV-202610-004</p>
            </a>
        </div>
    </div>
</div>
