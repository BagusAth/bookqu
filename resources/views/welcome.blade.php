<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BookQu - Kelola Booking Bisnis Anda</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes float-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        @keyframes fade-up {
            0% { opacity: 0; transform: translateY(16px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-24 right-0 h-80 w-80 rounded-full bg-blue-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute top-64 -left-16 h-72 w-72 rounded-full bg-cyan-200/40 blur-3xl"></div>

        <!-- Navbar -->
        <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6 lg:px-8">
                <a href="/" class="text-lg font-extrabold text-blue-600">BookQu</a>
                <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
                    <a href="#features" class="transition hover:text-slate-900">Features</a>
                    <a href="#solutions" class="transition hover:text-slate-900">Solutions</a>
                    <a href="#pricing" class="transition hover:text-slate-900">Pricing</a>
                    <a href="#about" class="transition hover:text-slate-900">About</a>
                </nav>
                <div class="flex items-center gap-3">
                    <a href="/login" class="text-sm font-semibold text-slate-600 transition hover:text-slate-900">Login</a>
                    <a href="/dummy-register" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Get Started
                    </a>
                </div>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section class="px-6 pb-20 pt-16 lg:px-8">
                <div class="mx-auto max-w-6xl">
                    <div class="mx-auto max-w-3xl text-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-blue-600 shadow-sm">
                            BARU: INTEGRASI PEMBAYARAN OTOMATIS
                        </span>
                        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                            Kelola Booking Bisnis Anda Lebih Mudah
                        </h1>
                        <p class="mt-4 text-base text-slate-600 sm:text-lg">
                            Sistem reservasi all-in-one untuk salon, klinik, studio, dan jasa profesional lainnya.
                            Tingkatkan efisiensi dan kurangi no-show pelanggan.
                        </p>
                        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <a href="/dummy-register" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700">
                                Mulai Gratis 7 Hari
                            </a>
                            <a href="#features" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">
                                Lihat Demo
                            </a>
                        </div>
                    </div>

                    <div class="mt-12">
                        <div class="mx-auto max-w-5xl rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-900/90 to-slate-800 p-3 shadow-2xl">
                            <img
                                src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1600&q=80"
                                alt="Dashboard preview"
                                class="animate-[float-slow_8s_ease-in-out_infinite] rounded-xl border border-slate-200 bg-white shadow-xl"
                                style="animation: float-slow 8s ease-in-out infinite;"
                            >
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features -->
            <section id="features" class="bg-white py-20">
                <div class="mx-auto max-w-6xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-3xl font-semibold text-slate-900">Fitur Utama Untuk Bisnis Anda</h2>
                        <p class="mt-3 text-sm text-slate-600">
                            Dirancang untuk kecepatan dan kemudahan penggunaan, BookQu memberikan kontrol penuh atas operasional harian Anda.
                        </p>
                    </div>
                    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3" style="animation: fade-up 0.8s ease-out both;">
                        <div class="rounded-2xl border border-slate-200 bg-blue-50/60 p-6 shadow-sm">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/10 text-blue-700">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a1 1 0 001-1V7H4v13a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold">Booking Management</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Terima pesanan 24/7 secara otomatis. Biarkan pelanggan memilih jadwal tanpa perlu chat manual satu per satu.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/10 text-blue-700">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3M8 3H5a2 2 0 00-2 2v3m18-3a2 2 0 00-2-2h-3m-7 18h3m-3-8h8" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold">Real-time Schedule</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Sinkronisasi instan di semua perangkat. Hindari double-booking dengan kalender pintar kami.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-cyan-50/60 p-6 shadow-sm">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/10 text-blue-700">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M8 17V9m4 8V5m4 12v-6" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold">Analytics Dashboard</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Pantau performa bisnis, pendapatan harian, hingga staf paling produktif melalui data visual yang intuitif.
                            </p>
                        </div>
                    </div>

                    <div id="solutions" class="mt-12">
                        <div class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row">
                            <div>
                                <h3 class="text-lg font-semibold">Solusi Untuk Berbagai Industri</h3>
                                <p class="mt-1 text-sm text-slate-600">Salon & Spa, Studio Foto, Klinik Kesehatan, Lapangan Olahraga, dan banyak lagi.</p>
                            </div>
                            <a href="/dummy-register" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                Mulai Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing -->
            <section id="pricing" class="py-20">
                <div class="mx-auto max-w-6xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-3xl font-semibold text-slate-900">Pilih Paket Sesuai Kebutuhan</h2>
                        <p class="mt-3 text-sm text-slate-600">
                            Transparan tanpa biaya tersembunyi. Tingkatkan paket Anda seiring berkembangnya bisnis.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 lg:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-slate-600">Small</h3>
                            <div class="mt-2 text-3xl font-semibold">Rp49k <span class="text-sm font-medium text-slate-500">/bulan</span></div>
                            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Halaman booking dengan URL</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Maksimal 2 layanan/program</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Maksimal 50 booking/bulan</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Manajemen jadwal & slot</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Notifikasi email</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Dashboard sederhana</li>
                            </ul>
                            <a href="/dummy-register" class="mt-6 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">
                                Pilih Paket
                            </a>
                        </div>

                        <div class="relative rounded-2xl border-2 border-blue-600 bg-white p-6 shadow-lg">
                            <span class="absolute right-4 top-4 rounded-full bg-blue-600 px-2 py-1 text-xs font-semibold text-white">POPULER</span>
                            <h3 class="text-sm font-semibold text-slate-600">Medium</h3>
                            <div class="mt-2 text-3xl font-semibold">Rp129k <span class="text-sm font-medium text-slate-500">/bulan</span></div>
                            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Semua fitur small</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Maksimal 10 layanan/program</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Maksimal 300 booking/bulan</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Notifikasi email + whatsapp</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Dashboard lengkap</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Sistem review & rating</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Prioritas support</li>
                            </ul>
                            <a href="/dummy-register" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                Mulai Sekarang
                            </a>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-slate-600">Pro</h3>
                            <div class="mt-2 text-3xl font-semibold">Rp299k <span class="text-sm font-medium text-slate-500">/bulan</span></div>
                            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Semua fitur medium</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Unlimited layanan dan booking</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Bisa pakai domain sendiri</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Custom tampilan landing page</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Multi-admin</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Advanced analytics</li>
                                <li class="flex items-center gap-2"><span class="text-blue-600">●</span> Dedicated support</li>
                            </ul>
                            <a href="/dummy-register" class="mt-6 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">
                                Hubungi Sales
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="bg-blue-600">
                <div class="mx-auto max-w-6xl px-6 py-16 text-center text-white lg:px-8">
                    <h2 class="text-3xl font-semibold">Siap Mengembangkan Bisnis Anda?</h2>
                    <p class="mt-3 text-sm text-blue-100">
                        Bergabunglah dengan pemilik bisnis yang telah beralih ke BookQu dan hemat hingga 15 jam kerja setiap minggunya.
                    </p>
                    <a href="/dummy-register" class="mt-6 inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                        Daftar Sekarang - Gratis 7 Hari
                    </a>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer id="about" class="bg-slate-100">
            <div class="mx-auto max-w-6xl px-6 py-12 lg:px-8">
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <div class="text-lg font-extrabold text-blue-600">BookQu</div>
                        <p class="mt-3 text-sm text-slate-500">
                            Platform manajemen booking terintegrasi untuk membantu digitalisasi bisnis jasa Anda.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700">Produk</h4>
                        <ul class="mt-3 space-y-2 text-sm text-slate-500">
                            <li>Fitur Utama</li>
                            <li>Integrasi Pembayaran</li>
                            <li>Mobile App</li>
                            <li>API Dokumentasi</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700">Solusi</h4>
                        <ul class="mt-3 space-y-2 text-sm text-slate-500">
                            <li>Salon & Spa</li>
                            <li>Klinik Kesehatan</li>
                            <li>Studio Foto</li>
                            <li>Konsultan Jasa</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700">Kontak</h4>
                        <ul class="mt-3 space-y-2 text-sm text-slate-500">
                            <li>support@bookqu.com</li>
                            <li>+62 21 4567 8910</li>
                            <li>Sudirman CBD, Jakarta Selatan, Indonesia</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-6 text-xs text-slate-400 md:flex-row">
                    <span>Copyright (c) 2024 BookQu. Hak Cipta Dilindungi Undang-Undang.</span>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-slate-600">Kebijakan Privasi</a>
                        <a href="#" class="hover:text-slate-600">Syarat & Ketentuan</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
+</body>
+</html>
