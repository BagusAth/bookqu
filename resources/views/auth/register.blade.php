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
                    <a href="/" class="text-2xl font-extrabold text-blue-600">BookQu</a>
                    <h1 class="mt-4 text-2xl font-bold text-slate-900">Daftar Akun Baru</h1>
                    <p class="mt-2 text-sm text-slate-600">Mulai gratis dan kelola booking bisnis Anda sekarang.</p>
                </div>

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                        <input 
                            id="name" 
                            type="text" 
                            name="name" 
                            placeholder="Nama Anda"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            placeholder="nama@email.com"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                        <input 
                            id="password" 
                            type="password" 
                            name="password" 
                            placeholder="Minimal 8 karakter"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">Konfirmasi Password</label>
                        <input 
                            id="password_confirmation" 
                            type="password" 
                            name="password_confirmation" 
                            placeholder="Ulangi password"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                    </div>

                    <label class="flex items-center">
                        <input type="checkbox" name="terms" class="rounded border-slate-300" required />
                        <span class="ml-2 text-sm text-slate-600">
                            Saya setuju dengan 
                            <a href="#" class="text-blue-600 hover:text-blue-700">Syarat & Ketentuan</a>
                        </span>
                    </label>

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
</body>
</html>
