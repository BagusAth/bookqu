<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>BookQu - Home</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900">
        <main class="min-h-screen flex items-center justify-center px-6">
            <div class="max-w-md w-full bg-white border border-slate-200 rounded-xl p-8 shadow-sm text-center">
                <div class="mx-auto w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                    B
                </div>
                <h1 class="mt-4 text-2xl font-semibold">BookQu</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Sistem booking sederhana untuk bisnis penyewaan.
                </p>
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center justify-center mt-6 w-full px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors"
                >
                    Login
                </a>
            </div>
        </main>
    </body>
</html>
