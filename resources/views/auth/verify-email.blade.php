<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email - BookQu</title>
    <meta name="theme-color" content="#4F46E5">
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 antialiased min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 sm:p-10"
             x-data="{
                 countdown: {{ session('status') ? 60 : 0 }},
                 loading: false,
                 init() {
                     const storedExpiry = localStorage.getItem('bq_verify_cooldown');
                     if (storedExpiry) {
                         const remaining = Math.round((parseInt(storedExpiry, 10) - Date.now()) / 1000);
                         if (remaining > 0) {
                             this.countdown = Math.max(this.countdown, remaining);
                         } else {
                             localStorage.removeItem('bq_verify_cooldown');
                         }
                     }
                     if (this.countdown > 0) {
                         this.startCooldown();
                     }
                 },
                 startCooldown() {
                     localStorage.setItem('bq_verify_cooldown', (Date.now() + this.countdown * 1000).toString());
                     const timer = setInterval(() => {
                         this.countdown--;
                         if (this.countdown <= 0) {
                             clearInterval(timer);
                             localStorage.removeItem('bq_verify_cooldown');
                         }
                     }, 1000);
                 },
                 handleSubmit() {
                     this.loading = true;
                     this.countdown = 60;
                     localStorage.setItem('bq_verify_cooldown', (Date.now() + 60000).toString());
                 }
             }">
            <div class="text-center mb-6">
                <a href="/" class="inline-flex items-center justify-center transition-transform hover:scale-105">
                    <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-10 w-auto" />
                </a>
                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Verifikasi Email</h1>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                    Kami sudah mengirim link aktivasi ke email Anda. Silakan cek kotak masuk (inbox) atau folder spam dan klik tautan tersebut.
                </p>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium text-xs sm:text-sm">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="space-y-4" @submit="handleSubmit">
                @csrf
                <button 
                    type="submit" 
                    :disabled="loading || countdown > 0"
                    class="w-full bg-[#4F46E5] text-white py-2.5 px-4 rounded-xl font-semibold hover:bg-[#4338CA] transition shadow-md shadow-[#4F46E5]/25 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="countdown > 0 ? `Kirim Ulang (${countdown}s)` : (loading ? 'Mengirim...' : 'Kirim Ulang Link Verifikasi')">
                        Kirim Ulang Link Verifikasi
                    </span>
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-slate-600 hover:text-rose-600 transition-colors cursor-pointer">
                        Keluar dari Akun
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
