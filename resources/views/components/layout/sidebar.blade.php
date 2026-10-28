{{-- resources/views/components/layout/sidebar.blade.php --}}

{{-- Ambil daftar menu dari config/navigation.php.
     Pakai bentuk blok (bukan satu baris) supaya tidak bentrok dengan blok di dalam <nav>. --}}
@php
    $menu = config('navigation.menu');
@endphp

{{-- Overlay gelap di belakang sidebar saat dibuka di mobile.
     Muncul jika mobileSidebarOpen = true, klik overlay untuk menutup sidebar. --}}
<div x-show="mobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="gl-sidebar-overlay" @click="mobileSidebarOpen = false" x-cloak>
</div>

{{-- Wadah sidebar. Lebar dan posisinya dikontrol Alpine:
     - sidebarOpen true  -> lebar w-64 (penuh), false -> w-20 (mini, hanya ikon)
     - mobileSidebarOpen -> geser masuk di mobile; di desktop (lg) selalu tampil --}}
<aside
    class="fixed top-0 bottom-0 left-0 z-50 flex flex-col bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-r border-emerald-100/80 dark:border-slate-800 shadow-xs transition-all duration-300 ease-in-out"
    :class="{
        'w-64': sidebarOpen,
        'w-20': !sidebarOpen,
        'translate-x-0': mobileSidebarOpen,
        '-translate-x-full lg:translate-x-0': !mobileSidebarOpen
    }">

    {{-- Logo dan nama aplikasi di bagian atas --}}
    <x-layout.sidebar.brand />

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4">

        {{-- Loop semua item di config('navigation.menu') --}}
        @foreach ($menu as $item)
            {{-- KASUS 1: item punya 'children' -> tampilkan sebagai DROPDOWN --}}
            @if (isset($item['children']))
                @php
                    // Kumpulan pola URL semua child.
                    // Dipakai agar dropdown otomatis terbuka saat salah satu child sedang aktif.
                    $patterns = collect($item['children'])->pluck('match')->flatten()->all();

                    // Daftar key + actions milik semua child.
                    // Dikirim ke dropdown supaya dia bisa memutuskan hide/tampil:
                    // - filter(): buang child yang tidak punya 'key'
                    // - map(): ubah jadi ['key' => ..., 'actions' => [...]]
                    //   (jika 'actions' tidak ada di config, default ['read'])
                    $childPermissions = collect($item['children'])
                        ->filter(fn($c) => !empty($c['key']))
                        ->map(
                            fn($c) => [
                                'key' => $c['key'],
                                'actions' => $c['actions'] ?? ['read'],
                            ],
                        )
                        ->values()
                        ->all();
                @endphp

                {{-- Komponen dropdown.
                     :permissions -> dipakai untuk cek: tampil hanya jika minimal 1 child diizinkan
                     :active      -> true jika URL saat ini cocok dengan salah satu child --}}
                <x-layout.sidebar.dropdown :label="$item['label']" :icon="$item['icon']" :permissions="$childPermissions" :active="request()->is(...$patterns)">

                    {{-- Loop tiap child dan render sebagai submenu --}}
                    @foreach ($item['children'] as $child)
                        {{-- :permission -> key Casbin child (null jika tidak ada = selalu tampil)
                             :actions    -> aksi yang dicek, default ['read']
                             :active     -> true jika URL saat ini cocok dengan child ini --}}
                        <x-layout.sidebar.submenu-link :href="$child['href']" :permission="$child['key'] ?? null" :actions="$child['actions'] ?? ['read']"
                            :active="request()->is(...$child['match'])">
                            {{ $child['label'] }}
                        </x-layout.sidebar.submenu-link>
                    @endforeach
                </x-layout.sidebar.dropdown>

                {{-- KASUS 2: item tanpa 'children' -> tampilkan sebagai LINK TUNGGAL --}}
            @else
                {{-- Link tunggal akan hilang sendiri jika user tidak punya izin
                     (pengecekan dilakukan di dalam komponen link.blade.php) --}}
                <x-layout.sidebar.link :href="$item['href']" :icon="$item['icon']" :label="$item['label']" :permission="$item['key'] ?? null"
                    :actions="$item['actions'] ?? ['read']" :active="request()->is(...$item['match'])" />
            @endif
        @endforeach
    </nav>

    {{-- Kartu status periode di bagian bawah sidebar --}}
    <x-layout.sidebar.status-card />
</aside>
