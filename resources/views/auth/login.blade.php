<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login - BookQu</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900">
        <main class="min-h-screen flex items-center justify-center px-6">
            <div class="max-w-md w-full bg-white border border-slate-200 rounded-xl p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">
                        B
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold">Login BookQu</h1>
                        <p class="text-xs text-slate-500">Masuk ke dashboard admin</p>
                    </div>
                </div>

                <form method="post" action="{{ url('/login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-medium text-slate-700" for="email">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            value="{{ old('email') }}"
                            required
                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200"
                        />
                        @error('email')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700" for="password">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                    >
                        Login
                    </button>
                </form>

                <a href="{{ url('/') }}" class="mt-6 block text-center text-xs text-slate-500 hover:text-slate-700">
                    Kembali ke halaman utama
                </a>
            </div>
        </main>
    </body>
</html>
