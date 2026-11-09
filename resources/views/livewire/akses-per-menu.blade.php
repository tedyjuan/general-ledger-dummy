<div class="card">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <div>
            <h5 class="card-title">Akses per Menu</h5>
            <p class="text-sm text-base-content/60">
                Pilih menu, lalu centang aksi yang boleh dilakukan tiap role.
            </p>
        </div>

        {{-- <select wire:model.live="menu" class="select max-w-xs" aria-label="Pilih menu">
            @foreach ($daftarMenu as $key => $def)
                <option value="{{ $key }}">
                    {{ $def['label'] }}
                </option>
            @endforeach
        </select> --}}

        <div x-data="{
            open: false,
            search: '',
            selected: @js($menu),
            menus: @js(
    collect($daftarMenu)
        ->map(
            fn($item, $key) => [
                'key' => (string) $key,
                'label' => $item['label'],
            ],
        )
        ->values(),
),
        
            get filteredMenus() {
                const keyword = this.search.trim().toLowerCase();
        
                if (!keyword) {
                    return this.menus;
                }
        
                return this.menus.filter(item =>
                    item.label.toLowerCase().includes(keyword) ||
                    item.key.toLowerCase().includes(keyword)
                );
            },
        
            get selectedMenu() {
                return this.menus.find(item => item.key === this.selected);
            },
        
            async pilihMenu(item) {
                if (this.selected === item.key) {
                    this.open = false;
                    this.search = '';
                    return;
                }
        
                this.selected = item.key;
                this.open = false;
                this.search = '';
        
                await $wire.set('menu', item.key);
            }
        }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false"
            x-on:menu-changed.window="selected = $event.detail.menu" class="relative w-full max-w-xs">
            {{-- Dropdown Trigger --}}
            <button type="button" class="select flex w-full items-center justify-between gap-2"
                x-on:click="open = !open; search = ''; $nextTick(() => open && $refs.search.focus())"
                x-bind:aria-expanded="open" aria-haspopup="listbox">
                <span class="truncate" x-text="selectedMenu?.label ?? 'Pilih menu'"></span>

                <svg class="size-4 shrink-0 transition-transform" x-bind:class="{ 'rotate-180': open }"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                </svg>
            </button>

            {{-- Dropdown Content --}}
            <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute left-0 top-full z-50 mt-2 w-full overflow-hidden rounded-box border border-base-content/10 bg-base-100 shadow-xl"
                x-on:click.stop>
                {{-- Search Input --}}
                <div class="border-b border-base-content/10 p-2">
                    <label class="input input-sm flex w-full items-center gap-2">
                        <svg class="size-4 shrink-0 text-base-content/50" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20 20-4-4" />
                        </svg>

                        <input x-ref="search" x-model="search" type="search"
                            class="w-full border-0 bg-transparent outline-none" placeholder="Cari menu..."
                            autocomplete="off">

                        <button x-show="search.length > 0" type="button"
                            class="text-base-content/50 hover:text-base-content"
                            x-on:click="search = ''; $refs.search.focus()" aria-label="Hapus pencarian">
                            &times;
                        </button>
                    </label>
                </div>

                {{-- Menu List --}}
                <ul class="max-h-72 overflow-y-auto p-1" role="listbox">
                    <template x-for="(item, index) in filteredMenus" :key="item.key">
                        <li role="option" x-bind:aria-selected="selected === item.key">
                            <button type="button"
                                class="flex w-full items-center justify-between gap-2 rounded-field px-3 py-2.5 text-left text-sm transition-colors hover:bg-base-200 focus:bg-base-200 focus:outline-none"
                                x-bind:class="{
                                    'bg-primary/10 text-primary font-medium': selected === item.key
                                }"
                                x-on:click="pilihMenu(item)">
                                <span class="truncate" x-text="item.label"></span>

                                <svg x-show="selected === item.key" class="size-4 shrink-0 text-primary"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                            </button>
                        </li>
                    </template>

                    {{-- Empty State --}}
                    <li x-show="filteredMenus.length === 0" class="px-3 py-6 text-center">
                        <div class="text-sm text-base-content/60">
                            Menu tidak ditemukan.
                        </div>
                    </li>
                </ul>

                {{-- Footer --}}
                <div class="border-t border-base-content/10 px-3 py-2">
                    <p class="text-xs text-base-content/50">
                        <span x-text="filteredMenus.length"></span>
                        dari
                        <span x-text="menus.length"></span>
                        menu
                    </p>
                </div>
            </div>
        </div>

    </div>

    <div class="card-body">

        @if ($tersimpan)
            <div class="alert alert-soft alert-success mb-4" role="alert">
                Akses untuk menu
                <b>{{ $daftarMenu[$menu]['label'] ?? $menu }}</b>
                berhasil disimpan.
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Role</th>

                        @foreach ($aksiMenu as $aksi)
                            <th class="text-center">
                                {{ ucfirst($aksi) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($perans as $peran)
                        @php
                            $terkunci = $peran->code === 'ICTT' && $menu === 'permissions';
                        @endphp

                        <tr wire:key="{{ $menu }}-{{ $peran->code }}">
                            <td>
                                <div class="font-medium">
                                    {{ $peran->name }}
                                </div>

                                <div class="text-xs text-base-content/60">
                                    {{ $peran->code }}
                                </div>
                            </td>

                            @foreach ($aksiMenu as $aksi)
                                <td class="text-center">
                                    <input type="checkbox" class="checkbox checkbox-primary"
                                        wire:model="matrix.{{ $peran->code }}.{{ $aksi }}"
                                        @disabled($terkunci)
                                        aria-label="{{ $peran->code }} - {{ $aksi }}">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <p class="text-sm text-base-content/60">
                Mencentang create, update, atau delete otomatis mengaktifkan read.
            </p>

            <button wire:click="simpan" wire:loading.attr="disabled" class="btn btn-primary">
                <span wire:loading wire:target="simpan" class="loading loading-spinner loading-sm"></span>

                Simpan
            </button>
        </div>

    </div>
</div>
