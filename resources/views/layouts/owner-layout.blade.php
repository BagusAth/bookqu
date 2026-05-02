<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BookQu Owner Dashboard - Kelola bisnis booking Anda">

    <title>@yield('title', 'Dashboard') — BookQu</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bq-background font-sans text-bq-text antialiased" x-data="{ sidebaropen: false }">

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
            class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm lg:hidden"
            @click="sidebaropen = false"
        ></div>

        <!-- Sidebar -->
        @include('components.owner.sidebar')

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64">
            <!-- Mobile Header -->
            <div class="sticky top-0 z-30 flex items-center gap-3 border-b border-bq-border bg-bq-surface/80 px-4 py-3 backdrop-blur-md lg:hidden">
                <button @click="sidebaropen = true" class="rounded-lg p-2 text-bq-text-muted hover:bg-bq-primary-light hover:text-bq-primary transition-colors" id="btn-mobile-menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="text-sm font-semibold text-bq-primary">BookQu</span>
            </div>

            <div class="p-4 sm:p-6 lg:p-8">
                @yield('content')
            </div>
        </main>
    </div>

</body>
</html>
