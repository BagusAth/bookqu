<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BookQu Owner Dashboard - Kelola bisnis booking Anda">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — BookQu</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js (Deferred to prevent render blocking) -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f7f7fa] font-sans text-[#231a3d] antialiased" x-data="{ sidebaropen: false }">

    <div class="flex min-h-screen">
        <!-- Mobile Sidebar Overlay -->
        <div
            x-show="sidebaropen"
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-[#231a3d]/50 backdrop-blur-xs lg:hidden"
            @click="sidebaropen = false"
            x-cloak
            style="display: none;"
        ></div>

        <!-- Sidebar -->
        @include('components.owner.sidebar')

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64 flex flex-col min-w-0">
            <!-- Topbar (Desktop & Mobile) -->
            @include('components.owner.topbar')

            <div class="p-4 sm:p-6 lg:p-8 flex-1">
                @if (session('pesan'))
                    <div class="mb-5 rounded-2xl border border-[#ffb84d]/60 bg-[#fff8eb] px-4 py-3 text-xs font-bold text-[#875000] shadow-2xs flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#ffb84d] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('pesan') }}</span>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Reusable UI Components -->
    @include('components.owner.toast')
    @include('components.owner.confirm-modal')

</body>
</html>
