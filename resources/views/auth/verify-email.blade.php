<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email - BookQu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="text-center mb-6">
                    <a href="/" class="text-2xl font-extrabold text-blue-600">BookQu</a>
                    <h1 class="mt-4 text-2xl font-bold text-slate-900">Verifikasi Email</h1>
                    <p class="mt-2 text-sm text-slate-600">Kami sudah mengirim link aktivasi ke email Anda. Silakan cek inbox dan klik link tersebut.</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <p class="font-semibold">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Kirim Ulang Link Verifikasi
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('logout') }}" class="text-sm text-slate-600 hover:text-slate-900"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Keluar
                    </a>
                    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
