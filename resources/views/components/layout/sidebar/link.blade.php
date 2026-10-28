{{-- resources/views/components/layout/sidebar/link.blade.php --}}
@props(['href', 'icon', 'label', 'active' => false, 'permission' => null, 'actions' => ['read']])

@php
    $user = auth()->user();
    // Tampil jika tidak ada permission (menu publik) atau user punya minimal 1 aksi
    $boleh = is_null($permission) || collect($actions)->contains(fn($a) => $user?->izin($permission, $a));
@endphp

@if ($boleh)
    <a href="{{ $href }}" wire:navigate class="gl-sidebar-link {{ $active ? 'gl-sidebar-link-active' : '' }}"
        :class="{ 'gl-sidebar-collapsed': !sidebarOpen }" :title="!sidebarOpen ? @js($label) : ''">

        <span class="{{ $icon }} gl-sidebar-icon"></span>
        <span x-show="sidebarOpen" class="gl-sidebar-label">{{ $label }}</span>
    </a>
@endif
