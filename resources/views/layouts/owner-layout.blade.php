<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BookQu Owner Dashboard - Kelola bisnis booking Anda">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4F46E5">

    <title>@yield('title', 'Dashboard') — BookQu</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Chart.js (Deferred to prevent render blocking) -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    <!-- Shared owner blocks register their Alpine behaviour before Alpine starts (BQ-129) -->
    <script defer src="{{ asset('js/owner-ui.js') }}"></script>

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

            <div class="p-3.5 sm:p-5 md:p-6 lg:p-8 flex-1">
                {{-- Flash messages, including `pesan`, show once in the toast (BQ-129) --}}
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Reusable UI Components -->
    @include('components.owner.toast')
    @include('components.owner.confirm-modal')
    {{-- The one booking panel and customer panel every page opens (owner-portal.md rule 3, AC-PNL-06) --}}
    <x-owner.booking-panel />
    <x-owner.customer-panel />
    @include('components.owner.session-expired')

</body>
</html>
