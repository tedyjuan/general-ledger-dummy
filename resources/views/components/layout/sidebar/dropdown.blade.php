{{-- resources/views/components/layout/sidebar/dropdown.blade.php --}}
{{-- permissions = array berisi ['key' => ..., 'actions' => [...]] milik semua child --}}
@props(['label', 'icon', 'active' => false, 'visible' => true, 'permissions' => null])

@php
    $tampil = $visible;

    // Jika daftar permission child dikirim, dropdown tampil hanya kalau
    // minimal 1 child diizinkan
    if ($tampil && is_array($permissions)) {
        $user = auth()->user();
        $tampil = collect($permissions)->contains(
            fn($p) => collect($p['actions'])->contains(fn($a) => $user?->izin($p['key'], $a)),
        );
    }
@endphp

@if ($tampil)
    <div x-data="{
        open: @js((bool) $active),
    
        toggle() {
            if (!this.sidebarOpen) {
                this.sidebarOpen = true;
                this.open = true;
                return;
            }
    
            this.open = !this.open;
        }
    }">
        <button type="button" @click="toggle()" class="gl-sidebar-dropdown"
            :class="{ 'gl-sidebar-collapsed': !sidebarOpen }" :title="!sidebarOpen ? @js($label) : ''">

            <span class="{{ $icon }} gl-sidebar-icon"></span>
            <span x-show="sidebarOpen" class="gl-sidebar-label">{{ $label }}</span>

            <span x-show="sidebarOpen" class="icon-[tabler--chevron-down] gl-sidebar-chevron"
                :class="{ 'rotate-180': open }">
            </span>
        </button>

        <div x-show="open && sidebarOpen" x-collapse class="gl-sidebar-submenu">
            {{ $slot }}
        </div>
    </div>
@endif
