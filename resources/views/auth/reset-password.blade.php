<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Ulang Password - BookQu</title>
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
                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Atur Ulang Password</h1>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                    Silakan masukkan kata sandi baru untuk akun BookQu Anda.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Terjadi kesalahan</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5 text-xs sm:text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4" x-data="{ loading: false, showPass: false, showConfirm: false }" @submit="loading = true">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $token ?? $request->route('token') ?? old('token') }}">

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Akun</label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        placeholder="nama@email.com"
                        value="{{ old('email', $email ?? '') }}"
                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:border-transparent transition-all {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-[#4F46E5]' }}"
                        required
                        autofocus
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                    <div class="relative">
                        <input 
                            id="password" 
                            :type="showPass ? 'text' : 'password'" 
                            type="password"
                            name="password" 
                            placeholder="Minimal 8 karakter"
                            class="w-full px-4 py-2.5 border rounded-xl pr-12 focus:ring-2 focus:border-transparent transition-all {{ $errors->has('password') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-[#4F46E5]' }}"
                            required
                        />
                        <button 
                            type="button" 
                            @click="showPass = !showPass" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1"
                            :aria-label="showPass ? 'Sembunyikan password' : 'Lihat password'">
                            <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <input 
                            id="password_confirmation" 
                            :type="showConfirm ? 'text' : 'password'" 
                            type="password"
                            name="password_confirmation" 
                            placeholder="Ulangi password baru"
                            class="w-full px-4 py-2.5 border rounded-xl pr-12 focus:ring-2 focus:border-transparent transition-all {{ $errors->has('password_confirmation') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-[#4F46E5]' }}"
                            required
                        />
                        <button 
                            type="button" 
                            @click="showConfirm = !showConfirm" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1"
                            :aria-label="showConfirm ? 'Sembunyikan konfirmasi password' : 'Lihat konfirmasi password'">
                            <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
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
                    <span x-text="loading ? 'Menyimpan Password...' : 'Simpan Password Baru'">Simpan Password Baru</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-[#4F46E5] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Batal dan Kembali ke Login</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
