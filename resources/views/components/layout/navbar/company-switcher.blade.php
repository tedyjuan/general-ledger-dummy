<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = !open"
        class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-emerald-400">
        <span class="icon-[tabler--building] size-4"></span>
        <span>{{ $active ?? 'Pilih Company' }}</span>
        <span class="icon-[tabler--chevron-down] size-4 transition-transform" :class="open && 'rotate-180'"></span>
    </button>

    <div x-show="open" x-cloak x-transition @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-100 bg-white py-2 shadow-xl dark:border-slate-800 dark:bg-slate-900">
        <p class="px-4 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Company aktif</p>

        @foreach ($companies as $c)
            <button type="button" wire:key="company-{{ $c['code'] }}" wire:click="switch('{{ $c['code'] }}')"
                @click="open = false" wire:loading.attr="disabled" wire:target="switch"
                class="flex w-full items-center justify-between px-4 py-2 text-left text-sm transition-colors hover:bg-emerald-50/60 dark:hover:bg-slate-800/60
                    {{ $active === $c['code'] ? 'font-semibold text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-200' }}">
                <span>
                    {{ $c['code'] }}
                    @if ($c['name'] && $c['name'] !== $c['code'])
                        <span class="block text-[11px] font-normal text-slate-400">{{ $c['name'] }}</span>
                    @endif
                </span>
                @if ($active === $c['code'])
                    <span class="icon-[tabler--check] size-4"></span>
                @endif
            </button>
        @endforeach
    </div>
</div>
