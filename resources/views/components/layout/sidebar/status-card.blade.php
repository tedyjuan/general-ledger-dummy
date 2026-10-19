{{-- resources/views/components/layout/sidebar/status-card.blade.php --}}
@props(['periode' => 'Oktober 2026', 'status' => 'Open', 'info' => 'Semua jurnal terverifikasi'])

<div x-show="sidebarOpen"
    class="p-3 m-3 rounded-2xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50 text-xs">
    <div class="flex items-center justify-between mb-1">
        <span class="text-emerald-800 dark:text-emerald-300 font-medium">Periode Aktif</span>
        <span
            class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-600 text-white dark:bg-emerald-500/20 dark:text-emerald-300 font-semibold">{{ $status }}</span>
    </div>
    <div class="font-bold text-slate-800 dark:text-white">{{ $periode }}</div>
    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $info }}</div>
</div>
