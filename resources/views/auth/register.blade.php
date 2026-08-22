<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - BookQu</title>
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
                    <h1 class="mt-4 text-2xl font-bold text-slate-900">Daftar Akun Baru</h1>
                    <p class="mt-2 text-sm text-slate-600">Mulai gratis dan kelola booking bisnis Anda sekarang.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="font-semibold">Pendaftaran gagal</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                        <input 
                            id="name" 
                            type="text" 
                            name="name" 
                            placeholder="Nama Anda"
                            value="{{ old('name') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent {{ $errors->has('name') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                            required
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            placeholder="nama@email.com"
                            value="{{ old('email') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                            required
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nomorhp" class="block text-sm font-medium text-slate-700 mb-2">Nomor HP</label>
                        <input 
                            id="nomorhp" 
                            type="text" 
                            name="nomorhp" 
                            placeholder="08xxxxxxxxxx"
                            value="{{ old('nomorhp') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent {{ $errors->has('nomorhp') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                            required
                        />
                        @error('nomorhp')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nama_bisnis" class="block text-sm font-medium text-slate-700 mb-2">Nama Bisnis</label>
                        <input 
                            id="nama_bisnis" 
                            type="text" 
                            name="nama_bisnis" 
                            placeholder="Contoh: Studio Yoga Sehat"
                            value="{{ old('nama_bisnis') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent {{ $errors->has('nama_bisnis') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                            required
                        />
                        @error('nama_bisnis')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="jenis_bisnis" class="block text-sm font-medium text-slate-700 mb-2">Jenis Bisnis</label>
                        <input 
                            id="jenis_bisnis" 
                            type="text" 
                            name="jenis_bisnis" 
                            placeholder="Contoh: Fitness, Salon, Studio Musik"
                            value="{{ old('jenis_bisnis') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent {{ $errors->has('jenis_bisnis') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                            required
                        />
                        @error('jenis_bisnis')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="alamat" class="block text-sm font-medium text-slate-700 mb-2">Alamat Bisnis</label>
                        <input 
                            id="alamat" 
                            type="text" 
                            name="alamat" 
                            placeholder="Jl. Contoh No. 1, Kota"
                            value="{{ old('alamat') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent {{ $errors->has('alamat') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                            required
                        />
                        @error('alamat')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                        <div class="relative">
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                placeholder="Minimal 8 karakter"
                                class="w-full px-4 py-2 border rounded-lg pr-12 focus:ring-2 focus:border-transparent {{ $errors->has('password') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                                required
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 hover:text-slate-700" data-toggle="password" data-target="password">
                                Lihat
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">Konfirmasi Password</label>
                        <div class="relative">
                            <input 
                                id="password_confirmation" 
                                type="password" 
                                name="password_confirmation" 
                                placeholder="Ulangi password"
                                class="w-full px-4 py-2 border rounded-lg pr-12 focus:ring-2 focus:border-transparent {{ $errors->has('password_confirmation') ? 'border-rose-400 focus:ring-rose-500' : 'border-slate-300 focus:ring-blue-500' }}"
                                required
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 hover:text-slate-700" data-toggle="password" data-target="password_confirmation">
                                Lihat
                            </button>
                        </div>
                        @error('password_confirmation')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center">
                        <input type="checkbox" name="terms" class="rounded border-slate-300" @checked(old('terms')) required />
                        <span class="ml-2 text-sm text-slate-600">
                            Saya setuju dengan 
                            <a href="#" class="text-blue-600 hover:text-blue-700">Syarat & Ketentuan</a>
                        </span>
                    </label>
                    @error('terms')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Daftar Sekarang
                    </button>

                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-slate-500">atau</span>
                        </div>
                    </div>

                    <a href="{{ route('login') }}" class="block w-full text-center border border-slate-300 text-slate-700 py-2 rounded-lg font-semibold hover:bg-slate-50 transition">
                        Sudah Punya Akun? Masuk
                    </a>
                </form>

                <div class="mt-6 text-center">
                    <a href="/" class="text-sm text-slate-600 hover:text-slate-900">Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-toggle="password"]').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) {
                    return;
                }
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                button.textContent = isHidden ? 'Sembunyikan' : 'Lihat';
            });
        });
    </script>
</body>
</html>
