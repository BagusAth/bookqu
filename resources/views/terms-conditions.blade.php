<x-landing.layout title="Syarat & Ketentuan - BookQu">
    <x-landing.navbar />

    <main class="min-h-screen pb-20 pt-8 lg:pt-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            
            {{-- Breadcrumb --}}
            <nav class="mb-6 flex items-center gap-2 text-xs font-medium text-slate-500 sm:text-sm">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1 transition-colors hover:text-[#4F46E5]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Beranda</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-semibold">Syarat &amp; Ketentuan</span>
            </nav>

            {{-- Hero Header --}}
            <div class="relative overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 via-white to-slate-50 p-6 sm:p-10 shadow-sm mb-10">
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-white/80 px-3.5 py-1.5 text-xs font-semibold text-[#4F46E5] shadow-xs backdrop-blur-sm mb-4">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <span>Ketentuan Layanan Resmi</span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        Syarat &amp; Ketentuan <span class="text-[#4F46E5]">BookQu</span>
                    </h1>

                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-500">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Terakhir diperbarui: 24 September 2026
                        </span>
                    </div>

                    <div class="mt-6 rounded-2xl border border-indigo-100/70 bg-white/70 p-4 sm:p-5 text-sm sm:text-base leading-relaxed text-slate-600 shadow-xs backdrop-blur-xs">
                        <p class="font-medium text-slate-700">
                            Selamat datang di BookQu.
                        </p>
                        <p class="mt-2 text-slate-600">
                            Syarat &amp; Ketentuan ini mengatur penggunaan platform BookQu oleh pemilik bisnis, staf, pelanggan, dan pengguna lainnya.
                        </p>
                        <p class="mt-2 text-slate-500">
                            Dengan menggunakan layanan BookQu, pengguna dianggap telah membaca dan menyetujui Syarat &amp; Ketentuan ini.
                        </p>
                    </div>
                </div>

                {{-- Background decorative shapes --}}
                <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-indigo-200/40 blur-2xl"></div>
                <div class="pointer-events-none absolute bottom-0 right-10 h-40 w-40 rounded-full bg-violet-200/30 blur-xl"></div>
            </div>

            {{-- Main Content Grid --}}
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12" x-data="{ activeSection: 'section-1' }" x-init="
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            activeSection = entry.target.id;
                        }
                    });
                }, { rootMargin: '-20% 0px -70% 0px' });
                document.querySelectorAll('.terms-section').forEach(section => observer.observe(section));
            ">
                
                {{-- Sticky Table of Contents (Desktop Sidebar) --}}
                <aside class="hidden lg:col-span-4 lg:block">
                    <div class="sticky top-24 space-y-6">
                        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs max-h-[calc(100vh-8rem)] overflow-y-auto">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                                <svg class="h-4 w-4 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                                <span>Daftar Isi</span>
                            </h2>
                            <nav class="space-y-1 text-sm font-medium">
                                <a href="#section-1" 
                                   :class="activeSection === 'section-1' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">01.</span>
                                    <span class="truncate">Tentang BookQu</span>
                                </a>
                                <a href="#section-2" 
                                   :class="activeSection === 'section-2' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">02.</span>
                                    <span class="truncate">Akun Pengguna</span>
                                </a>
                                <a href="#section-3" 
                                   :class="activeSection === 'section-3' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">03.</span>
                                    <span class="truncate">Penggunaan Layanan</span>
                                </a>
                                <a href="#section-4" 
                                   :class="activeSection === 'section-4' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">04.</span>
                                    <span class="truncate">Booking &amp; Transaksi</span>
                                </a>
                                <a href="#section-5" 
                                   :class="activeSection === 'section-5' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">05.</span>
                                    <span class="truncate">Pembayaran</span>
                                </a>
                                <a href="#section-6" 
                                   :class="activeSection === 'section-6' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">06.</span>
                                    <span class="truncate">Paket &amp; Biaya Layanan</span>
                                </a>
                                <a href="#section-7" 
                                   :class="activeSection === 'section-7' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">07.</span>
                                    <span class="truncate">Konten &amp; Data Pengguna</span>
                                </a>
                                <a href="#section-8" 
                                   :class="activeSection === 'section-8' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">08.</span>
                                    <span class="truncate">Hak Kekayaan Intelektual</span>
                                </a>
                                <a href="#section-9" 
                                   :class="activeSection === 'section-9' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">09.</span>
                                    <span class="truncate">Ketersediaan Layanan</span>
                                </a>
                                <a href="#section-10" 
                                   :class="activeSection === 'section-10' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">10.</span>
                                    <span class="truncate">Batasan Tanggung Jawab</span>
                                </a>
                                <a href="#section-11" 
                                   :class="activeSection === 'section-11' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">11.</span>
                                    <span class="truncate">Penghentian Akun</span>
                                </a>
                                <a href="#section-12" 
                                   :class="activeSection === 'section-12' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">12.</span>
                                    <span class="truncate">Perubahan Ketentuan</span>
                                </a>
                                <a href="#section-13" 
                                   :class="activeSection === 'section-13' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-1.5 transition-all">
                                    <span class="text-xs opacity-70">13.</span>
                                    <span class="truncate">Kontak Resmi</span>
                                </a>
                            </nav>
                        </div>

                        {{-- Quick Help Box --}}
                        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/70 to-white p-5 shadow-xs">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#4F46E5] text-white">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Ada Pertanyaan?</h4>
                                    <p class="text-xs text-slate-500">Hubungi tim BookQu</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="mailto:bookquind@gmail.com" class="block w-full text-center rounded-xl bg-white border border-indigo-200 px-3 py-2 text-xs font-semibold text-[#4F46E5] shadow-xs transition hover:bg-indigo-50">
                                    bookquind@gmail.com
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Content Sections --}}
                <div class="space-y-8 lg:col-span-8">
                    
                    {{-- Section 1 --}}
                    <section id="section-1" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                01
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                1. Tentang BookQu
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600">
                            BookQu adalah platform SaaS yang membantu bisnis berbasis jasa mengelola booking, jadwal, pelanggan, pembayaran, dan aktivitas operasional melalui satu platform.
                        </p>
                    </section>

                    {{-- Section 2 --}}
                    <section id="section-2" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                02
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                2. Akun Pengguna
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-4">
                            Pengguna bertanggung jawab untuk:
                        </p>
                        <ul class="space-y-2.5 text-sm sm:text-base text-slate-600 mb-5">
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Memberikan informasi yang benar dan akurat</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Menjaga keamanan akun dan kredensial login</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Tidak memberikan akses akun kepada pihak yang tidak berwenang</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Segera menghubungi BookQu apabila terjadi penggunaan akun yang tidak sah</span>
                            </li>
                        </ul>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3.5 text-xs sm:text-sm text-slate-700 font-medium">
                            Pengguna bertanggung jawab atas aktivitas yang dilakukan melalui akunnya.
                        </div>
                    </section>

                    {{-- Section 3 --}}
                    <section id="section-3" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                03
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                3. Penggunaan Layanan
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-4">
                            Pengguna setuju untuk menggunakan BookQu secara sah dan tidak menggunakan platform untuk:
                        </p>
                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 mb-5">
                            <div class="flex items-start gap-2.5 rounded-xl border border-rose-100 bg-rose-50/60 p-3 text-xs sm:text-sm text-rose-900">
                                <svg class="h-4 w-4 shrink-0 text-rose-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Melakukan aktivitas ilegal</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-rose-100 bg-rose-50/60 p-3 text-xs sm:text-sm text-rose-900">
                                <svg class="h-4 w-4 shrink-0 text-rose-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Menipu atau merugikan pihak lain</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-rose-100 bg-rose-50/60 p-3 text-xs sm:text-sm text-rose-900">
                                <svg class="h-4 w-4 shrink-0 text-rose-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Mengganggu keamanan atau operasional platform</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-rose-100 bg-rose-50/60 p-3 text-xs sm:text-sm text-rose-900">
                                <svg class="h-4 w-4 shrink-0 text-rose-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Mengakses data pengguna lain tanpa izin</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-rose-100 bg-rose-50/60 p-3 text-xs sm:text-sm text-rose-900 sm:col-span-2">
                                <svg class="h-4 w-4 shrink-0 text-rose-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Mengunggah konten yang melanggar hukum atau hak pihak lain</span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-amber-200/70 bg-amber-50/60 p-3.5 text-xs sm:text-sm text-amber-900 flex items-center gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>BookQu berhak membatasi atau menghentikan akses terhadap akun yang terbukti melakukan pelanggaran.</span>
                        </div>
                    </section>

                    {{-- Section 4 --}}
                    <section id="section-4" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                04
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                4. Booking dan Transaksi
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-4">
                            Pemilik bisnis bertanggung jawab atas:
                        </p>
                        <ul class="space-y-2.5 text-sm sm:text-base text-slate-600 mb-5">
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Informasi layanan yang ditampilkan</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Harga layanan</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Ketersediaan jadwal</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Kebijakan pembatalan</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Pelaksanaan layanan kepada pelanggan</span>
                            </li>
                        </ul>
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3.5 text-xs sm:text-sm text-indigo-950 flex items-start gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>BookQu menyediakan teknologi untuk membantu proses booking, tetapi tidak menjadi pihak yang menyediakan layanan utama antara bisnis dan pelanggan.</span>
                        </div>
                    </section>

                    {{-- Section 5 --}}
                    <section id="section-5" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                05
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                5. Pembayaran
                            </h2>
                        </div>
                        <div class="space-y-4 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                Pembayaran yang dilakukan melalui BookQu dapat diproses oleh penyedia pembayaran pihak ketiga.
                            </p>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 text-xs sm:text-sm text-slate-700 leading-relaxed">
                                Biaya transaksi, refund, settlement, dan ketentuan pembayaran mengikuti ketentuan penyedia pembayaran yang digunakan serta paket atau layanan BookQu yang berlaku.
                            </div>
                        </div>
                    </section>

                    {{-- Section 6 --}}
                    <section id="section-6" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                06
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                6. Paket dan Biaya Layanan
                            </h2>
                        </div>
                        <div class="space-y-3 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                BookQu dapat menyediakan beberapa paket layanan berbayar.
                            </p>
                            <p>
                                Harga, fitur, batas penggunaan, dan ketentuan masing-masing paket dapat berubah dari waktu ke waktu.
                            </p>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3.5 text-xs sm:text-sm text-slate-700 font-medium">
                                Setiap perubahan harga atau fitur akan diinformasikan melalui kanal resmi BookQu.
                            </div>
                        </div>
                    </section>

                    {{-- Section 7 --}}
                    <section id="section-7" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                07
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                7. Konten dan Data Pengguna
                            </h2>
                        </div>
                        <div class="space-y-3 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                Pengguna tetap bertanggung jawab atas data, informasi, gambar, deskripsi layanan, dan konten lain yang dimasukkan ke dalam platform.
                            </p>
                            <p>
                                Pengguna memberikan BookQu izin yang diperlukan untuk menyimpan dan memproses data tersebut sejauh diperlukan untuk menyediakan layanan.
                            </p>
                        </div>
                    </section>

                    {{-- Section 8 --}}
                    <section id="section-8" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                08
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                8. Hak Kekayaan Intelektual
                            </h2>
                        </div>
                        <div class="space-y-3 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                Seluruh elemen BookQu, termasuk nama, logo, desain, kode program, tampilan, fitur, dan materi yang dikembangkan oleh BookQu merupakan milik BookQu atau pihak yang memberikan lisensi kepada BookQu.
                            </p>
                            <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5 text-xs sm:text-sm text-amber-900 flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>Pengguna tidak diperbolehkan menyalin, memodifikasi, menjual kembali, atau menggunakan bagian dari platform BookQu tanpa izin tertulis.</span>
                            </div>
                        </div>
                    </section>

                    {{-- Section 9 --}}
                    <section id="section-9" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                09
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                9. Ketersediaan Layanan
                            </h2>
                        </div>
                        <div class="space-y-3 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                BookQu berupaya menjaga agar platform dapat digunakan dengan baik.
                            </p>
                            <p class="text-slate-500 text-xs sm:text-sm">
                                Namun, BookQu tidak menjamin layanan akan selalu tersedia tanpa gangguan. Gangguan dapat terjadi karena pemeliharaan, masalah infrastruktur, jaringan, penyedia pihak ketiga, atau keadaan lain di luar kendali BookQu.
                            </p>
                        </div>
                    </section>

                    {{-- Section 10 --}}
                    <section id="section-10" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                10
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                10. Batasan Tanggung Jawab
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-3">
                            BookQu menyediakan platform sebagai sarana teknologi untuk membantu pengelolaan booking dan operasional bisnis.
                        </p>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-4">
                            BookQu tidak bertanggung jawab atas:
                        </p>
                        <ul class="space-y-2 text-sm sm:text-base text-slate-600 pl-1">
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-slate-400 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Perselisihan antara pemilik bisnis dan pelanggan</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-slate-400 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Kualitas layanan yang diberikan oleh bisnis</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-slate-400 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Kesalahan informasi yang dimasukkan oleh pengguna</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-slate-400 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Kerugian akibat penggunaan platform yang tidak sesuai</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-slate-400 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Gangguan yang disebabkan oleh layanan pihak ketiga di luar kendali BookQu</span>
                            </li>
                        </ul>
                    </section>

                    {{-- Section 11 --}}
                    <section id="section-11" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                11
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                11. Penghentian Akun
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-4">
                            BookQu dapat menangguhkan atau menghentikan akun apabila pengguna:
                        </p>
                        <ul class="space-y-2.5 text-sm sm:text-base text-slate-600 mb-5">
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                </div>
                                <span>Melanggar Syarat &amp; Ketentuan</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                </div>
                                <span>Menggunakan platform untuk aktivitas ilegal</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                </div>
                                <span>Menyalahgunakan layanan</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                </div>
                                <span>Melakukan tindakan yang dapat mengganggu keamanan atau pengguna lain</span>
                            </li>
                        </ul>
                        <p class="text-xs sm:text-sm text-slate-600 font-medium">
                            Pengguna juga dapat mengajukan penghentian akun melalui kontak resmi BookQu.
                        </p>
                    </section>

                    {{-- Section 12 --}}
                    <section id="section-12" class="terms-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                12
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                12. Perubahan Syarat &amp; Ketentuan
                            </h2>
                        </div>
                        <div class="space-y-3 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                BookQu dapat memperbarui Syarat &amp; Ketentuan ini apabila terdapat perubahan layanan, fitur, harga, atau ketentuan hukum yang berlaku.
                            </p>
                            <p class="font-medium text-slate-700">
                                Versi terbaru akan tersedia pada halaman ini.
                            </p>
                        </div>
                    </section>

                    {{-- Section 13 --}}
                    <section id="section-13" class="terms-section scroll-mt-24 rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/50 via-white to-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                13
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                13. Kontak
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-6">
                            Untuk pertanyaan mengenai layanan, akun, pembayaran, atau Syarat &amp; Ketentuan BookQu:
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- Email Card --}}
                            <div class="group relative rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all hover:border-[#4F46E5] hover:shadow-md">
                                <div class="flex items-center gap-3.5 mb-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-[#4F46E5] transition-colors group-hover:bg-[#4F46E5] group-hover:text-white">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">Email Resmi</p>
                                        <a href="mailto:bookquind@gmail.com" class="text-sm sm:text-base font-semibold text-slate-900 hover:text-[#4F46E5] transition-colors">
                                            bookquind@gmail.com
                                        </a>
                                    </div>
                                </div>
                                <a href="mailto:bookquind@gmail.com" class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-[#4F46E5] hover:underline">
                                    <span>Kirim pesan email</span>
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>

                            {{-- Website Card --}}
                            <div class="group relative rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all hover:border-[#4F46E5] hover:shadow-md">
                                <div class="flex items-center gap-3.5 mb-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-[#4F46E5] transition-colors group-hover:bg-[#4F46E5] group-hover:text-white">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">Situs Web</p>
                                        <a href="https://bookqu.my.id" target="_blank" rel="noopener noreferrer" class="text-sm sm:text-base font-semibold text-slate-900 hover:text-[#4F46E5] transition-colors">
                                            https://bookqu.my.id
                                        </a>
                                    </div>
                                </div>
                                <a href="https://bookqu.my.id" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-[#4F46E5] hover:underline">
                                    <span>Buka situs web</span>
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- Back to Top / Home Navigation Helper --}}
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200 text-sm">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-[#4F46E5] transition-colors font-medium">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Kembali ke Beranda</span>
                        </a>

                        <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-slate-800 transition-colors">
                            <span>Kembali ke Atas</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <x-landing.footer />
</x-landing.layout>
