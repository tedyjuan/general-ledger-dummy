{{-- resources/views/components/layout/footer.blade.php --}}
<footer
    class="bg-white/60 dark:bg-slate-900/60 border-t border-emerald-100/60 dark:border-slate-800 py-4 px-6 text-xs text-slate-500 dark:text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2 mt-auto">
    <div>
        &copy; {{ date('Y') }}
        <span class="font-semibold text-emerald-700 dark:text-emerald-400">General Ledger System</span>.
    </div>
    <div class="flex items-center gap-4 text-slate-400 dark:text-slate-500">
        <a href="#" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Bantuan</a>
        <a href="#" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Ketentuan</a>
    </div>
</footer>
