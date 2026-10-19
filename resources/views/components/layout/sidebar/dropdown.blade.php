{{-- resources/views/components/layout/sidebar/dropdown.blade.php --}}
@props(['label', 'icon', 'active' => false])

<div x-data="{
    open: @js((bool) $active),

    toggle() {
        // Sidebar mini: besarkan dulu & buka submenu
        if (!this.sidebarOpen) {
            this.sidebarOpen = true;
            this.open = true;
            return;
        }
        this.open = !this.open;
    }
}">
    <button type="button" @click="toggle()" class="gl-sidebar-dropdown" :class="{ 'gl-sidebar-collapsed': !sidebarOpen }"
        :title="!sidebarOpen ? @js($label) : ''">
        <span class="{{ $icon }} gl-sidebar-icon"></span>
        <span x-show="sidebarOpen" class="gl-sidebar-label">{{ $label }}</span>
        <span x-show="sidebarOpen" class="icon-[tabler--chevron-down] gl-sidebar-chevron"
            :class="{ 'rotate-180': open }"></span>
    </button>

    <div x-show="open && sidebarOpen" x-collapse class="gl-sidebar-submenu">
        {{ $slot }}
    </div>
</div>
