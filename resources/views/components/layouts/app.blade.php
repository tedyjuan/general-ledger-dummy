{{-- resources/views/components/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="theme" :class="{ 'dark': darkMode }"
    :data-theme="darkMode ? 'dark' : 'light'" class="min-h-screen">

@include('partials.head')

<body
    class="min-h-screen antialiased text-slate-700 dark:text-slate-200 bg-[#F8FAF8] dark:bg-slate-950 transition-colors duration-200"
    x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">

    <x-layout.sidebar />

    <div class="flex min-h-screen min-w-0 flex-col transition-all duration-300"
        :class="{ 'lg:pl-64': sidebarOpen, 'lg:pl-20': !sidebarOpen }">

        <x-layout.navbar />

        <main class="mx-auto w-full max-w-7xl flex-1 p-4 pb-12 lg:p-8 lg:pb-16">
            @isset($header)
                <div class="mb-6">{{ $header }}</div>
            @endisset

            {{ $slot }}
        </main>

        <x-layout.footer />
    </div>

    {{-- Toast global: satu kali di layout, dipakai semua halaman --}}
    <x-toast />
    @livewireScripts
</body>

</html>
