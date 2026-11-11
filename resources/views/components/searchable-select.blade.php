{{--
    Komponen: <x-searchable-select />
    Simpan di: resources/views/components/searchable-select.blade.php

    Props:
      options     : array [kode => alias]
      model       : nama property Livewire (mis. 'areaFilter')
      placeholder : teks saat kosong / opsi "semua"
      live        : true  -> sinkron langsung (wire:model.live)
                    false -> sinkron saat request berikutnya (wire:model)
      disabled    : nonaktifkan dropdown
--}}
@props([
    'options' => [],
    'model',
    'placeholder' => 'Pilih...',
    'live' => false,
    'disabled' => false,
])

@php
    $opts = collect($options)
        ->map(fn($alias, $code) => ['value' => (string) $code, 'label' => $code . ' — ' . $alias])
        ->values();
@endphp

<div data-disabled="{{ $disabled ? 'true' : 'false' }}" x-data="{
    open: false,
    q: '',
    hi: 0,
    up: false,
    left: 0,
    top: 0,
    bottom: 0,
    width: 0,
    maxH: 240,
    options: @js($opts),
    placeholder: @js($placeholder),
    value: $wire.$entangle('{{ $model }}', {{ $live ? 'true' : 'false' }}),

    get isDisabled() { return $root.dataset.disabled === 'true' },
    get selected() { return this.options.find(o => o.value === String(this.value ?? '')) },
    get filtered() {
        const s = this.q.trim().toLowerCase();
        return s ? this.options.filter(o => o.label.toLowerCase().includes(s)) : this.options;
    },
    // baris pertama = opsi 'semua' (value kosong) kalau tidak sedang mencari
    get rows() {
        return this.q.trim() === '' ?
            [{ value: '', label: this.placeholder }, ...this.filtered] :
            this.filtered;
    },

    place() {
        const r = this.$refs.btn.getBoundingClientRect();
        const below = window.innerHeight - r.bottom - 12;
        const above = r.top - 12;
        this.up = below < 240 && above > below;
        this.maxH = Math.max(140, Math.min(288, (this.up ? above : below) - 56));
        this.width = Math.max(r.width, 224);
        this.left = Math.min(Math.max(8, r.left), window.innerWidth - this.width - 8);
        this.top = r.bottom + 4;
        this.bottom = window.innerHeight - r.top + 4;
    },
    toggle() {
        if (this.isDisabled) return;
        this.open ? this.close() : this.show();
    },
    show() {
        this.q = '';
        this.place();
        this.open = true;
        const i = this.rows.findIndex(o => o.value === String(this.value ?? ''));
        this.hi = i < 0 ? 0 : i;
        this.$nextTick(() => { this.$refs.search.focus();
            this.reveal(); });
    },
    close() { this.open = false; },
    pick(v) { this.value = v;
        this.close();
        this.$refs.btn.focus(); },
    move(d) {
        const n = this.rows.length;
        if (!n) return;
        this.hi = (this.hi + d + n) % n;
        this.$nextTick(() => this.reveal());
    },
    reveal() {
        this.$refs.list?.querySelector('[data-hi=true]')?.scrollIntoView({ block: 'nearest' });
    },
    choose() { const r = this.rows[this.hi]; if (r) this.pick(r.value); },
}" @scroll.window.capture="open && place()"
    @resize.window="open && place()" @keydown.escape.stop="open && close()"
    @click.window="if (open && !$root.contains($event.target) && !$refs.panel.contains($event.target)) close()"
    class="relative w-full">

    {{-- Tombol pemicu --}}
    <button type="button" x-ref="btn" @click="toggle()" :disabled="isDisabled" :aria-expanded="open"
        aria-haspopup="listbox"
        class="flex h-8 w-full items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 text-left text-sm transition hover:border-emerald-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
        <span class="min-w-0 flex-1 truncate"
            :class="selected ? 'text-slate-800 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
            x-text="selected ? selected.label : placeholder"></span>

        {{-- Hapus pilihan --}}
        <span x-show="selected && !isDisabled" x-cloak @click.stop="pick('')" role="button" aria-label="Hapus pilihan"
            class="flex size-5 shrink-0 items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">
            <span class="icon-[tabler--x] size-3.5"></span>
        </span>
        <span class="icon-[tabler--selector] size-4 shrink-0 text-slate-400"></span>
    </button>

    {{-- Panel dropdown: dipindah ke <body> supaya selalu di atas tabel, card, dan modal --}}
    <template x-teleport="body">
        <div x-ref="panel" x-show="open" x-cloak x-transition.opacity.duration.100ms
            :style="`position:fixed; z-index:9999; left:${left}px; width:${width}px; ` + (up ? `bottom:${bottom}px` :
                `top:${top}px`)"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900"
            role="listbox">

            <div class="relative border-b border-slate-100 p-2 dark:border-slate-800">
                <span
                    class="icon-[tabler--search] pointer-events-none absolute start-4 top-1/2 size-4 -translate-y-1/2 text-slate-400"></span>
                <input type="text" x-ref="search" x-model="q" @input="hi = 0"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="choose()" placeholder="Ketik untuk mencari..." autocomplete="off"
                    class="h-8 w-full rounded-md border border-slate-200 bg-slate-50 ps-8 pe-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <ul x-ref="list" class="overflow-y-auto p-1" :style="`max-height:${maxH}px`">
                <template x-for="(o, i) in rows" :key="o.value === '' ? '__all' : o.value">
                    <li role="option" :aria-selected="String(value ?? '') === o.value" :data-hi="hi === i"
                        @click="pick(o.value)" @mousemove="hi = i"
                        class="flex cursor-pointer items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-sm"
                        :class="[
                            hi === i ? 'bg-emerald-50 dark:bg-slate-800' : '',
                            o.value === '' ? 'text-slate-500 dark:text-slate-400' :
                            'text-slate-700 dark:text-slate-200',
                            String(value ?? '') === o.value ? 'font-semibold text-emerald-700 dark:text-emerald-400' :
                            ''
                        ]">
                        <span class="truncate" x-text="o.label"></span>
                        <span x-show="String(value ?? '') === o.value"
                            class="icon-[tabler--check] size-4 shrink-0 text-emerald-600"></span>
                    </li>
                </template>
                <li x-show="rows.length === 0" class="px-3 py-6 text-center text-xs text-slate-400">
                    Tidak ada hasil untuk "<span x-text="q"></span>"
                </li>
            </ul>
        </div>
    </template>
</div>
