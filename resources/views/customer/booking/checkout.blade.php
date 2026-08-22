<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $tenant->namabisnis }} - Checkout</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
    </head>
    <body class="booking-page">
        <div id="booking-checkout-root" data-tenant-slug="{{ $tenant->slug }}">
            <header class="border-b border-[#E5E7EB] bg-white/95 backdrop-blur">
                <nav class="booking-shell mx-auto flex w-full max-w-[1280px] items-center justify-between px-6 py-4" x-data="{ navOpen: false }">
                    <div class="flex items-center gap-2">
                        <a href="/" class="flex items-center">
                            <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
                        </a>
                    </div>
                    <div class="hidden items-center gap-8 text-sm font-medium text-[#6B7280] lg:flex">
                        <a class="transition hover:text-[#111827]" href="#">Features</a>
                        <a class="transition hover:text-[#111827]" href="#">Solutions</a>
                        <a class="transition hover:text-[#111827]" href="#">Pricing</a>
                        <a class="transition hover:text-[#111827]" href="#">About</a>
                    </div>
                    <div class="hidden items-center gap-4 lg:flex">
                        <a class="text-sm font-semibold text-[#6B7280] transition hover:text-[#111827]" href="#">Login</a>
                        <a class="rounded-xl bg-[#4F46E5] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4338CA]" href="#">Get Started</a>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-[#E5E7EB] p-2 text-[#111827] transition hover:border-[#4F46E5] hover:text-[#4F46E5] lg:hidden"
                        @click="navOpen = !navOpen"
                        aria-label="Toggle navigation"
                    >
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div
                        class="absolute left-0 right-0 top-full border-t border-[#E5E7EB] bg-white px-6 py-4 shadow-sm lg:hidden"
                        x-cloak
                        x-show="navOpen"
                        x-transition
                    >
                        <div class="flex flex-col gap-4 text-sm font-medium text-[#6B7280]">
                            <a class="transition hover:text-[#111827]" href="#">Features</a>
                            <a class="transition hover:text-[#111827]" href="#">Solutions</a>
                            <a class="transition hover:text-[#111827]" href="#">Pricing</a>
                            <a class="transition hover:text-[#111827]" href="#">About</a>
                            <div class="flex items-center gap-3 pt-2">
                                <a class="flex-1 rounded-xl border border-[#E5E7EB] px-4 py-2 text-center text-sm font-semibold text-[#111827]" href="#">Login</a>
                                <a class="flex-1 rounded-xl bg-[#4F46E5] px-4 py-2 text-center text-sm font-semibold text-white" href="#">Get Started</a>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>

            <main class="booking-shell mx-auto w-full max-w-[1280px] px-6 pb-12 pt-10">
                <form
                    class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]"
                    method="POST"
                    action="{{ route('customer.booking.process-checkout', $tenant->slug) }}"
                >
                    @csrf
                    
                    <section>
                        <div class="mt-4">
                            <a
                                href="{{ route('customer.booking.time', $tenant->slug) }}"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-[#6B7280] transition hover:text-[#4F46E5]"
                            >
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Back to Time Selection
                            </a>
                            <h1 class="mt-3 text-2xl font-bold text-[#111827] sm:text-3xl">Isi Data Diri</h1>
                            <p class="mt-2 text-sm text-[#6B7280] sm:text-base">Lengkapi data di bawah ini untuk menyelesaikan booking.</p>
                        </div>

                        @if ($errors->any())
                            <div class="mt-4 rounded-xl border border-[#FCA5A5] bg-[#FEF2F2] px-4 py-3 text-sm text-[#B91C1C]">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mt-4 rounded-xl border border-[#FCA5A5] bg-[#FEF2F2] px-4 py-3 text-sm text-[#B91C1C]">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="mt-8 space-y-6 rounded-2xl border border-[#E5E7EB] bg-white p-6 booking-shadow">
                             <!-- Nama -->
                             <div>
                                <label for="namapelanggan" class="block text-sm font-medium text-[#111827] mb-1">
                                    Nama Lengkap <span class="text-[#EF4444]">*</span>
                                </label>
                                <input type="text" name="namapelanggan" id="namapelanggan" required
                                    class="w-full rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] px-4 py-2.5 text-[#111827] shadow-sm transition focus:border-[#4F46E5] focus:bg-white focus:ring-1 focus:ring-[#4F46E5]"
                                    value="{{ old('namapelanggan') }}"
                                    placeholder="Cth: Budi Santoso">
                             </div>
                             
                             <!-- Email -->
                             <div>
                                 <label for="email" class="block text-sm font-medium text-[#111827] mb-1">
                                     Alamat Email <span class="text-[#EF4444]">*</span>
                                 </label>
                                 <input type="email" name="email" id="email" required
                                     class="w-full rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] px-4 py-2.5 text-[#111827] shadow-sm transition focus:border-[#4F46E5] focus:bg-white focus:ring-1 focus:ring-[#4F46E5]"
                                     value="{{ old('email') }}"
                                     placeholder="Cth: budi@email.com">
                                 <p class="text-xs text-[#6B7280] mt-1">Invoice akan dikirim ke email ini.</p>
                             </div>

                             <!-- WhatsApp -->
                             <div>
                                 <label for="nomorhp" class="block text-sm font-medium text-[#111827] mb-1">
                                     Nomor WhatsApp <span class="text-[#EF4444]">*</span>
                                 </label>
                                 <input type="tel" name="nomorhp" id="nomorhp" required
                                     class="w-full rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] px-4 py-2.5 text-[#111827] shadow-sm transition focus:border-[#4F46E5] focus:bg-white focus:ring-1 focus:ring-[#4F46E5]"
                                     value="{{ old('nomorhp') }}"
                                     placeholder="Cth: 081234567890">
                             </div>

                             <!-- Catatan -->
                             <div>
                                 <label for="catatan" class="block text-sm font-medium text-[#111827] mb-1">
                                     Catatan Tambahan (Opsional)
                                 </label>
                                 <textarea name="catatan" id="catatan" rows="3"
                                     class="w-full rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] px-4 py-2.5 text-[#111827] shadow-sm transition focus:border-[#4F46E5] focus:bg-white focus:ring-1 focus:ring-[#4F46E5]"
                                     placeholder="Tambahkan catatan khusus untuk penyedia layanan jika ada...">{{ old('catatan') }}</textarea>
                             </div>
                        </div>
                    </section>
                    
                    <aside class="lg:sticky lg:top-24">
                        <div class="booking-summary rounded-2xl border border-[#E5E7EB] bg-white p-6 booking-shadow">
                            <div>
                                <h2 class="text-base font-semibold text-[#111827]">Booking Summary</h2>
                                <p class="mt-1 text-sm text-[#6B7280]">Review your choices</p>
                            </div>

                            <div class="mt-6 space-y-3">
                                <div class="flex items-center gap-3 rounded-xl border border-[#E5E7EB] bg-[#EEF2FF]/60 px-3 py-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#4F46E5]">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h10" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-[#4F46E5]">Selected Program</p>
                                        <p class="text-xs text-[#6B7280]">{{ $service->namalayanan }}</p>
                                        <p class="text-xs text-[#6B7280]">{{ $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 rounded-xl border border-[#E5E7EB] bg-white px-3 py-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="18" rx="4" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4M16 2v4M3 10h18" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-[#111827]">Date &amp; Time</p>
                                        <p class="text-xs text-[#6B7280]">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
                                        <p class="text-xs text-[#6B7280]">Pukul {{ $selectedTime }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 rounded-xl border border-[#E5E7EB] bg-white px-3 py-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v10H4z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h4" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-[#111827]">Price Summary</p>
                                        <p class="text-xs text-[#6B7280]">Rp {{ number_format($hargaAkhir, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($hargaAkhir != $service->harga)
                            <div class="mt-4 rounded-xl border border-[#FCA5A5] bg-[#FEF2F2] px-3 py-2 text-xs text-[#B91C1C]">
                                *Harga pada jadwal ini berbeda dengan harga standar layanan (Rp {{ number_format($service->harga, 0, ',', '.') }}).
                            </div>
                            @endif

                            <div class="mt-6 rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] p-4">
                                <div class="flex items-center justify-between text-sm font-semibold text-[#111827]">
                                    <span>Total Pembayaran</span>
                                    <span>Rp {{ number_format($hargaAkhir, 0, ',', '.') }}</span>
                                </div>
                                <p class="mt-1 text-xs text-[#6B7280]">Tax included</p>
                            </div>

                            <button
                                type="submit"
                                class="mt-4 w-full rounded-xl px-4 py-3 text-sm font-semibold transition bg-[#4F46E5] text-white hover:bg-[#4338CA]"
                            >
                                Lanjut Pembayaran
                            </button>

                            <a
                                href="{{ route('customer.booking.time', $tenant->slug) }}"
                                class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm font-medium text-[#6B7280] transition hover:border-[#4F46E5] hover:text-[#4F46E5]"
                            >
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Back to Time Selection
                            </a>

                            <p class="mt-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">Secure checkout enabled</p>
                        </div>
                    </aside>
                </form>
            </main>

            <footer class="border-t border-[#E5E7EB] bg-[#EDEBFA]">
                <div class="booking-shell mx-auto w-full max-w-[1280px] px-6 py-10">
                    <div class="grid gap-8 md:grid-cols-4">
                        <div class="md:col-span-1">
                            <a href="/" class="flex items-center mb-4">
                                <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
                            </a>
                            <p class="mt-3 text-sm text-[#6B7280]">
                                Platform manajemen booking terjangkau di Indonesia untuk membantu bisnis jasa dan profesional tampil digital.
                            </p>
                            <div class="mt-4 flex items-center gap-3 text-[#6B7280]">
                                <span class="h-8 w-8 rounded-full border border-[#E5E7EB] bg-white"></span>
                                <span class="h-8 w-8 rounded-full border border-[#E5E7EB] bg-white"></span>
                                <span class="h-8 w-8 rounded-full border border-[#E5E7EB] bg-white"></span>
                            </div>
                        </div>
                        <div>
                            <h5 class="text-sm font-semibold text-[#111827]">Produk</h5>
                            <ul class="mt-3 space-y-2 text-sm text-[#6B7280]">
                                <li>Fitur Utama</li>
                                <li>Integrasi Pembayaran</li>
                                <li>Mobile App</li>
                                <li>API Dokumentasi</li>
                            </ul>
                        </div>
                        <div>
                            <h5 class="text-sm font-semibold text-[#111827]">Solusi</h5>
                            <ul class="mt-3 space-y-2 text-sm text-[#6B7280]">
                                <li>Salon &amp; Spa</li>
                                <li>Klinik Kesehatan</li>
                                <li>Studio Foto</li>
                                <li>Konsultan Jasa</li>
                            </ul>
                        </div>
                        <div>
                            <h5 class="text-sm font-semibold text-[#111827]">Kontak</h5>
                            <ul class="mt-3 space-y-2 text-sm text-[#6B7280]">
                                <li>support@bookqu.com</li>
                                <li>+62 21 4567 8810</li>
                                <li>Sudirman CBD, Jakarta Selatan, Indonesia</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-8 flex flex-col gap-3 border-t border-[#E5E7EB] pt-6 text-xs text-[#6B7280] md:flex-row md:items-center md:justify-between">
                        <span>&copy; 2026 BookQu. Hak Cipta Dilindungi Undang-Undang.</span>
                        <span>Ketentuan Privasi | Syarat &amp; Ketentuan</span>
                    </div>
                </div>
            </footer>
        </div>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
