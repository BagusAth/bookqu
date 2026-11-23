<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - BookQu</title>
    <meta name="theme-color" content="#4F46E5">
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 sm:p-10">
            <div class="text-center mb-8">
                <a href="/" class="inline-flex items-center justify-center transition-transform hover:scale-105">
                    <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-10 w-auto" />
                </a>
                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Lupa Password?</h1>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                    Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Gagal mengirim tautan</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5 text-xs sm:text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium text-xs sm:text-sm">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Akun</label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        placeholder="nama@email.com"
                        value="{{ old('email') }}"
                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:border-transparent transition-all {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-[#4F46E5]' }}"
                        required
                        autofocus
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <button 
                    type="submit" 
                    :disabled="loading"
                    class="w-full bg-[#4F46E5] text-white py-2.5 px-4 rounded-xl font-semibold hover:bg-[#4338CA] transition shadow-md shadow-[#4F46E5]/25 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Mengirim Tautan...' : 'Kirim Tautan Atur Ulang'">Kirim Tautan Atur Ulang</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-[#4F46E5] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Halaman Login</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
