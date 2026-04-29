<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Admin' }} - BookQu</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-800">
        <div class="min-h-screen flex">
            <x-admin.sidebar :menu-items="$menuItems ?? null" :active-menu="$activeMenu ?? null" />

            <main class="flex-1 overflow-y-auto bg-white p-8">
                @yield('content')
            </main>
        </div>
    </body>
</html>
