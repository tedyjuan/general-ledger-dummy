{{-- resources/views/components/layout/sidebar/submenu-link.blade.php --}}
@props(['href', 'active' => false])

<a href="{{ $href }}" wire:navigate
    class="gl-sidebar-submenu-link {{ $active ? 'gl-sidebar-submenu-link-active' : '' }}">{{ $slot }}</a>
