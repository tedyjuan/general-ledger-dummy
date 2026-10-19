{{-- resources/views/components/layout/sidebar.blade.php --}}
@php($menu = config('navigation.menu'))

{{-- Overlay mobile --}}
<div x-show="mobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="gl-sidebar-overlay" @click="mobileSidebarOpen = false" x-cloak>
</div>

<aside
    class="fixed top-0 bottom-0 left-0 z-50 flex flex-col bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-r border-emerald-100/80 dark:border-slate-800 shadow-xs transition-all duration-300 ease-in-out"
    :class="{
        'w-64': sidebarOpen,
        'w-20': !sidebarOpen,
        'translate-x-0': mobileSidebarOpen,
        '-translate-x-full lg:translate-x-0': !mobileSidebarOpen
    }">

    <x-layout.sidebar.brand />

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4">
        @foreach ($menu as $item)
            @if (isset($item['children']))
                @php($patterns = collect($item['children'])->pluck('match')->flatten()->all())
                <x-layout.sidebar.dropdown :label="$item['label']" :icon="$item['icon']" :active="request()->is(...$patterns)">
                    @foreach ($item['children'] as $child)
                        <x-layout.sidebar.submenu-link :href="$child['href']" :active="request()->is(...$child['match'])">
                            {{ $child['label'] }}
                        </x-layout.sidebar.submenu-link>
                    @endforeach
                </x-layout.sidebar.dropdown>
            @else
                <x-layout.sidebar.link :href="$item['href']" :icon="$item['icon']" :label="$item['label']" :active="request()->is(...$item['match'])" />
            @endif
        @endforeach
    </nav>

    <x-layout.sidebar.status-card />
</aside>
