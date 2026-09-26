<x-landing.layout title="Kebijakan Privasi - BookQu">
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
                <span class="text-slate-700 font-semibold">Kebijakan Privasi</span>
            </nav>

            {{-- Hero Header --}}
            <div class="relative overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 via-white to-slate-50 p-6 sm:p-10 shadow-sm mb-10">
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-white/80 px-3.5 py-1.5 text-xs font-semibold text-[#4F46E5] shadow-xs backdrop-blur-sm mb-4">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <span>Legal &amp; Perlindungan Data</span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        Kebijakan Privasi <span class="text-[#4F46E5]">BookQu</span>
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
                            BookQu menghargai privasi pengguna dan berkomitmen untuk melindungi informasi yang diberikan saat menggunakan layanan BookQu.
                        </p>
                        <p class="mt-2 text-slate-500">
                            Kebijakan Privasi ini menjelaskan bagaimana BookQu mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pengguna saat mengakses platform dan layanan kami.
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
                document.querySelectorAll('.policy-section').forEach(section => observer.observe(section));
            ">
                
                {{-- Sticky Table of Contents (Desktop Sidebar) --}}
                <aside class="hidden lg:col-span-4 lg:block">
                    <div class="sticky top-24 space-y-6">
                        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                                <svg class="h-4 w-4 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                                <span>Daftar Isi</span>
                            </h2>
                            <nav class="space-y-1 text-sm font-medium">
                                <a href="#section-1" 
                                   :class="activeSection === 'section-1' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">01.</span>
                                    <span class="truncate">Informasi yang Dikumpulkan</span>
                                </a>
                                <a href="#section-2" 
                                   :class="activeSection === 'section-2' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">02.</span>
                                    <span class="truncate">Penggunaan Informasi</span>
                                </a>
                                <a href="#section-3" 
                                   :class="activeSection === 'section-3' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">03.</span>
                                    <span class="truncate">Informasi Pembayaran</span>
                                </a>
                                <a href="#section-4" 
                                   :class="activeSection === 'section-4' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">04.</span>
                                    <span class="truncate">Berbagi Informasi</span>
                                </a>
                                <a href="#section-5" 
                                   :class="activeSection === 'section-5' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">05.</span>
                                    <span class="truncate">Keamanan Data</span>
                                </a>
                                <a href="#section-6" 
                                   :class="activeSection === 'section-6' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">06.</span>
                                    <span class="truncate">Hak Pengguna</span>
                                </a>
                                <a href="#section-7" 
                                   :class="activeSection === 'section-7' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">07.</span>
                                    <span class="truncate">Perubahan Kebijakan</span>
                                </a>
                                <a href="#section-8" 
                                   :class="activeSection === 'section-8' ? 'bg-indigo-50 text-[#4F46E5] font-semibold border-l-2 border-[#4F46E5]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                                   class="group flex items-center gap-2.5 rounded-r-lg px-3 py-2 transition-all">
                                    <span class="text-xs opacity-70">08.</span>
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
                                    <h4 class="text-sm font-semibold text-slate-800">Pertanyaan Privasi?</h4>
                                    <p class="text-xs text-slate-500">Hubungi tim kami langsung</p>
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
                    <section id="section-1" class="policy-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                01
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Informasi yang Kami Kumpulkan
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-4">
                            BookQu dapat mengumpulkan beberapa informasi yang diperlukan untuk menyediakan layanan, termasuk:
                        </p>
                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Nama pengguna atau pelanggan</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Nomor telepon dan alamat email</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Informasi bisnis</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Informasi booking atau reservasi</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Data layanan yang dipilih</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Informasi pembayaran yang diperlukan untuk memproses transaksi</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Informasi akun dan aktivitas penggunaan platform</span>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs sm:text-sm text-slate-700">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Informasi teknis seperti perangkat, browser, dan alamat IP</span>
                            </div>
                        </div>
                        <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/60 p-3.5 text-xs sm:text-sm text-emerald-800 flex items-center gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>BookQu hanya mengumpulkan informasi yang relevan dengan penyediaan dan pengembangan layanan.</span>
                        </div>
                    </section>

                    {{-- Section 2 --}}
                    <section id="section-2" class="policy-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                02
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Penggunaan Informasi
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-4">
                            Informasi yang dikumpulkan dapat digunakan untuk:
                        </p>
                        <ul class="space-y-2.5 text-sm sm:text-base text-slate-600">
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Memproses dan mengelola booking</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Mengirimkan konfirmasi atau pengingat booking</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Membantu pemilik bisnis mengelola pelanggan dan jadwal</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Memproses pembayaran melalui penyedia pembayaran yang tersedia</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Meningkatkan fitur dan kualitas layanan BookQu</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Menjaga keamanan platform</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Menghubungi pengguna terkait layanan atau transaksi</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[#4F46E5]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#4F46E5]"></span>
                                </div>
                                <span>Memenuhi kewajiban hukum yang berlaku</span>
                            </li>
                        </ul>
                    </section>

                    {{-- Section 3 --}}
                    <section id="section-3" class="policy-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                03
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Informasi Pembayaran
                            </h2>
                        </div>
                        <div class="space-y-4 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                BookQu dapat menggunakan pihak ketiga sebagai penyedia layanan pembayaran.
                            </p>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5">
                                <div class="flex items-start gap-3.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-[#4F46E5]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <div class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                        <p class="font-semibold text-slate-800 mb-1">Keamanan Data Pembayaran</p>
                                        <p>
                                            BookQu tidak menyimpan informasi kartu pembayaran secara langsung apabila informasi tersebut diproses oleh penyedia pembayaran pihak ketiga. Pemrosesan data pembayaran mengikuti kebijakan dan ketentuan penyedia pembayaran yang digunakan.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Section 4 --}}
                    <section id="section-4" class="policy-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                04
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Berbagi Informasi
                            </h2>
                        </div>
                        
                        <div class="mb-4 inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs sm:text-sm font-semibold text-emerald-800">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>BookQu tidak menjual informasi pribadi pengguna kepada pihak lain.</span>
                        </div>

                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-3">
                            Informasi dapat dibagikan kepada pihak ketiga apabila diperlukan untuk:
                        </p>

                        <ul class="space-y-2 text-sm sm:text-base text-slate-600 mb-4 pl-1">
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span>Menyediakan layanan BookQu</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span>Memproses pembayaran</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span>Menyediakan infrastruktur teknologi</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span>Memenuhi kewajiban hukum atau permintaan pihak berwenang</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[#4F46E5] mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span>Melindungi keamanan pengguna dan platform</span>
                            </li>
                        </ul>

                        <p class="text-xs sm:text-sm text-slate-500 italic border-l-2 border-slate-200 pl-3">
                            Pihak ketiga tersebut hanya memperoleh informasi yang diperlukan untuk menjalankan fungsi terkait.
                        </p>
                    </section>

                    {{-- Section 5 --}}
                    <section id="section-5" class="policy-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                05
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Keamanan Data
                            </h2>
                        </div>
                        <div class="space-y-3 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                BookQu berupaya menerapkan langkah-langkah teknis dan organisasi yang wajar untuk melindungi informasi pengguna dari akses, perubahan, penggunaan, atau pengungkapan yang tidak sah.
                            </p>
                            <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5 text-xs sm:text-sm text-amber-900 flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>Namun, tidak ada sistem elektronik yang dapat dijamin sepenuhnya bebas dari risiko keamanan.</span>
                            </div>
                        </div>
                    </section>

                    {{-- Section 6 --}}
                    <section id="section-6" class="policy-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                06
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Hak Pengguna
                            </h2>
                        </div>
                        <div class="space-y-3 text-sm sm:text-base leading-relaxed text-slate-600">
                            <p>
                                Pengguna dapat meminta informasi mengenai data pribadi yang dimiliki BookQu dan, sepanjang diperbolehkan oleh hukum yang berlaku, dapat meminta koreksi atau penghapusan data tersebut.
                            </p>
                            <p class="font-medium text-slate-700">
                                Permintaan dapat disampaikan melalui kontak resmi BookQu.
                            </p>
                        </div>
                    </section>

                    {{-- Section 7 --}}
                    <section id="section-7" class="policy-section scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                07
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Perubahan Kebijakan Privasi
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600">
                            BookQu dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan akan ditampilkan pada halaman ini beserta tanggal pembaruannya.
                        </p>
                    </section>

                    {{-- Section 8 --}}
                    <section id="section-8" class="policy-section scroll-mt-24 rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/50 via-white to-white p-6 sm:p-8 shadow-xs transition">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-[#4F46E5]">
                                08
                            </span>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Kontak
                            </h2>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed text-slate-600 mb-6">
                            Jika memiliki pertanyaan mengenai Kebijakan Privasi atau penggunaan data pribadi, silakan menghubungi BookQu melalui:
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
