<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - BookQu</title>
    <meta name="theme-color" content="#4F46E5">
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="text-center mb-8">
                    <a href="/" class="flex items-center justify-center">
                        <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-10 w-auto" />
                    </a>
                    <h1 class="mt-4 text-2xl font-bold text-slate-900">Masuk ke Akun Anda</h1>
                    <p class="mt-2 text-sm text-slate-600">Kelola booking bisnis Anda dengan mudah melalui dashboard BookQu.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="font-semibold">Login gagal</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-medium text-xs sm:text-sm">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4" x-data="{ loading: false, showPassword: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            placeholder="nama@email.com"
                            value="{{ old('email') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-[#4F46E5]' }}"
                            required
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                        <div class="relative">
                            <input 
                                id="password" 
                                :type="showPassword ? 'text' : 'password'" 
                                type="password" 
                                name="password" 
                                placeholder="Masukkan password"
                                class="w-full px-4 py-2 border rounded-lg pr-12 focus:ring-2 focus:border-transparent {{ $errors->has('password') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-[#4F46E5]' }}"
                                required
                            />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1" 
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Lihat password'">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#4F46E5] focus:ring-[#4F46E5]" @checked(old('remember')) />
                            <span class="ml-2 text-sm text-slate-600">Ingat saya</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#4F46E5] hover:text-[#4338CA]">Lupa password?</a>
                    </div>

                    <button type="submit" :disabled="loading" class="w-full bg-[#4F46E5] text-white py-2 rounded-lg font-semibold hover:bg-[#4338CA] transition shadow-sm shadow-[#4F46E5]/25 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg x-show="loading" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Memproses...' : 'Masuk'">Masuk</span>
                    </button>

                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-slate-500">atau</span>
                        </div>
                    </div>

                    <a href="{{ route('register') }}" class="block w-full text-center border border-slate-300 text-slate-700 py-2 rounded-lg font-semibold hover:bg-slate-50 transition">
                        Daftar Akun Baru
                    </a>
                </form>

                <div class="mt-6 text-center">
                    <a href="/" class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali ke Beranda</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
