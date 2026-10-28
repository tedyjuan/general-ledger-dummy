{{-- resources/views/components/layout/sidebar/submenu-link.blade.php --}}
@props(['href', 'active' => false, 'permission' => null, 'actions' => ['read']])

@php
    $user = auth()->user();
    $boleh = is_null($permission) || collect($actions)->contains(fn($a) => $user?->izin($permission, $a));
@endphp

@if ($boleh)
    <a href="{{ $href }}" wire:navigate
        class="gl-sidebar-submenu-link {{ $active ? 'gl-sidebar-submenu-link-active' : '' }}">
        {{ $slot }}
    </a>
@endif
