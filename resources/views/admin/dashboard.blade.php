<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Dashboard Admin - BookQu</title>
        @vite('resources/css/app.css')
    </head>
    <body class="bg-slate-50 text-slate-900">
        <main class="mx-auto max-w-4xl px-6 py-16">
            <h1 class="text-2xl font-semibold">Dashboard Admin</h1>
            <p class="mt-2 text-sm text-slate-600">Halaman dummy untuk admin.</p>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="/" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white">
                    Kembali ke Landing Page
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-rose-500 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-600">
                        Logout
                    </button>
                </form>
            </div>
        </main>
    </body>
</html>
