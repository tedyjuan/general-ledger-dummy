{{-- resources/views/components/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="theme" :class="{ 'dark': darkMode }"
    :data-theme="darkMode ? 'dark' : 'light'" class="h-full">

@include('partials.head')

<body
    class="h-full antialiased text-slate-700 dark:text-slate-200 bg-[#F8FAF8] dark:bg-slate-950 transition-colors duration-200"
    x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">

    <div class="min-h-screen flex flex-col">

        <x-layout.sidebar />

        <div class="flex-1 flex flex-col transition-all duration-300"
            :class="{ 'lg:pl-64': sidebarOpen, 'lg:pl-20': !sidebarOpen }">

            <x-layout.navbar />

            <main class="flex-1 p-4 lg:p-8 max-w-7xl w-full mx-auto">
                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset

                {{ $slot }}
            </main>

            <x-layout.footer />
        </div>
    </div>

    @livewireScripts
</body>

</html>
