{{--
    Dropdown akun COA yang bisa dicari (Alpine.js).
    Variabel: $path (nama properti Livewire, mis. 'coa_code' atau 'mappings.0.coa_code'),
              $options (array ['v' => nomor, 'n' => nama]), $invalid (bool)
--}}
<div class="relative" @click.outside="open = false" @keydown.escape.stop="open = false" x-data="{
    open: false,
    q: '',
    path: @js($path),
    opts: @js($options),
    get value() { return String($wire.$get(this.path) ?? '') },
    get current() { return this.opts.find(o => o.v === this.value) },
    get list() {
        const s = this.q.trim().toLowerCase();
        return this.opts.filter(o => !s || (o.v + ' ' + o.n).toLowerCase().includes(s)).slice(0, 60);
    },
    toggle() {
        this.open = !this.open;
        if (this.open) this.$nextTick(() => this.$refs.cari.focus());
    },
    pick(o) {
        $wire.$set(this.path, o.v, false);
        this.open = false;
        this.q = '';
    },
    clear() { $wire.$set(this.path, '', false) },
}">
    <button type="button" @click="toggle()"
        class="input flex w-full items-center justify-between gap-2 bg-white text-start dark:border-slate-700 dark:bg-slate-900 dark:text-white {{ $invalid ? 'border-red-400' : '' }}">
        <span class="min-w-0 truncate" :class="current ? '' : 'text-slate-400'">
            <template x-if="current">
                <span><span class="font-mono font-semibold text-emerald-700 dark:text-emerald-400"
                        x-text="current.v"></span>
                    <span class="text-slate-500 dark:text-slate-400" x-text="' · ' + current.n"></span></span>
            </template>
            <template x-if="!current"><span>Pilih akun COA...</span></template>
        </span>
        <span class="flex shrink-0 items-center gap-1">
            <span x-show="current" @click.stop="clear()" role="button" aria-label="Kosongkan"
                class="icon-[tabler--x] size-4 text-slate-400 hover:text-red-500"></span>
            <span class="icon-[tabler--chevron-down] size-4 text-slate-400"></span>
        </span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
        class="absolute z-30 mt-1 w-full min-w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900">
        <div class="relative border-b border-slate-100 p-2 dark:border-slate-800">
            <span
                class="icon-[tabler--search] pointer-events-none absolute start-4 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
            <input type="text" x-ref="cari" x-model="q" placeholder="Cari nomor atau nama akun..."
                class="input input-sm w-full bg-white ps-8 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
        </div>
        <ul class="max-h-56 overflow-y-auto py-1 text-sm">
            <template x-for="o in list" :key="o.v">
                <li>
                    <button type="button" @click="pick(o)"
                        class="flex w-full items-center gap-2 px-3 py-1.5 text-start hover:bg-emerald-50 dark:hover:bg-slate-800"
                        :class="o.v === value ? 'bg-emerald-50/70 dark:bg-slate-800' : ''">
                        <span class="font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-400"
                            x-text="o.v"></span>
                        <span class="truncate text-slate-700 dark:text-slate-200" x-text="o.n"></span>
                        <span x-show="o.v === value"
                            class="icon-[tabler--check] ms-auto size-4 text-emerald-600"></span>
                    </button>
                </li>
            </template>
            <li x-show="list.length === 0" class="px-3 py-4 text-center text-xs text-slate-400">
                <span x-show="opts.length === 0">Belum ada akun ledger / subledger di perusahaan ini.</span>
                <span x-show="opts.length > 0">Akun tidak ditemukan.</span>
            </li>
        </ul>
    </div>
</div>
