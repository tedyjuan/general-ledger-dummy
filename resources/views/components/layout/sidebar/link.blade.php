{{-- resources/views/components/layout/sidebar/link.blade.php --}}
@props(['href', 'icon', 'label', 'active' => false])

<a href="{{ $href }}" wire:navigate
    class="gl-sidebar-link {{ $active ? 'gl-sidebar-link-active' : '' }}"
    :class="{ 'gl-sidebar-collapsed': !sidebarOpen }" :title="!sidebarOpen ? @js($label) : ''">
    <span class="{{ $icon }} gl-sidebar-icon"></span>
    <span x-show="sidebarOpen" class="gl-sidebar-label">{{ $label }}</span>
</a>
