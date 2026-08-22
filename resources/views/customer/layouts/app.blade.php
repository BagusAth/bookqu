<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>@yield('title') - {{ $tenant->namabisnis ?? 'BookQu' }}</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
        @yield('head')
        @if(isset($tenant) && $tenant->theme_color)
            <style>
                :root {
                    --color-primary-600: {{ $tenant->theme_color }};
                    --color-primary-700: {{ $tenant->theme_color }};
                }
                .bg-primary-600 { background-color: var(--color-primary-600) !important; }
                .hover\:bg-primary-700:hover { background-color: var(--color-primary-700) !important; filter: brightness(0.9); }
                .text-primary-600 { color: var(--color-primary-600) !important; }
                .border-primary-600 { border-color: var(--color-primary-600) !important; }
                .ring-primary-600 { --tw-ring-color: var(--color-primary-600) !important; }
            </style>
        @endif
    </head>
    <body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">
        <header class="border-b border-[#E5E7EB] bg-white/95 backdrop-blur sticky top-0 z-50">
            <nav class="mx-auto flex w-full max-w-[1280px] items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <a href="{{ isset($tenant) ? '/' . $tenant->slug : '/' }}" class="flex items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
                    </a>
                </div>
            </nav>
        </header>

        <main class="flex-grow w-full">
            @yield('content')
        </main>

        <footer class="border-t border-[#E5E7EB] bg-[#EDEBFA] mt-auto">
            <div class="mx-auto w-full max-w-[1280px] px-6 py-8">
                <div class="flex flex-col md:flex-row items-center justify-between text-sm text-[#6B7280]">
                    <p>&copy; {{ date('Y') }} {{ $tenant->namabisnis ?? 'BookQu' }}. All rights reserved.</p>
                    <p>Powered by BookQu</p>
                </div>
            </div>
        </footer>

        @yield('scripts')
    </body>
</html>
