<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $tenant->namabisnis }} - Date Selection</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/booking-date.css') }}" />
    </head>
    <body class="booking-page">
        <div
            id="booking-date-root"
            data-tenant-slug="{{ $tenant->slug }}"
            data-min-date="{{ $minDate }}"
            data-max-date="{{ $maxDate }}"
            data-selected-date="{{ $selectedDate }}"
            data-simulate="{{ $simulate ? 'true' : 'false' }}"
            x-data="bookingDateSelection()"
        >
            <header class="border-b border-[#E5E7EB] bg-white/95 backdrop-blur">
                <nav class="booking-shell mx-auto flex w-full max-w-[1280px] items-center justify-between px-6 py-4" x-data="{ navOpen: false }">
                    <a href="/" class="text-lg font-extrabold text-[#4F46E5]">BookQu</a>
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
                    action="{{ route('customer.booking.select-date', $tenant->slug) }}"
                    x-ref="dateForm"
                >
                    @csrf
                    <input type="hidden" name="tanggal" x-model="selectedDate" />
                    @if ($simulate)
                        <input type="hidden" name="simulate" value="1" />
                    @endif

                    <section>
                        <x-customer.progress-header step="2" total="3" label="Date Selection" progress="66" />

                        <div class="mt-8">
                            <h1 class="text-2xl font-bold text-[#111827] sm:text-3xl">Select Date</h1>
                            <p class="mt-2 text-sm text-[#6B7280] sm:text-base">Choose your preferred booking date.</p>
                        </div>

                        @if ($errors->any())
                            <div class="mt-4 rounded-xl border border-[#FCA5A5] bg-[#FEF2F2] px-4 py-3 text-sm text-[#B91C1C]">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        @php
                            $priceUnit = $service->satuan_harga ?: 'sesi';
                            $durationUnit = $service->satuan_durasi ?: 'menit';
                            $priceLabel = number_format($service->harga, 0, ',', '.');
                            $imageUrl = null;

                            if (!empty($service->image_url)) {
                                $imageUrl = \Illuminate\Support\Str::startsWith($service->image_url, ['http://', 'https://'])
                                    ? $service->image_url
                                    : asset('storage/' . $service->image_url);
                            }
                        @endphp

                        <article class="booking-date-card mt-6">
                            <div class="booking-date-card__media">
                                @if ($imageUrl)
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $service->namalayanan }}"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="booking-card__placeholder flex h-full w-full items-center justify-center bg-gradient-to-br from-[#EEF2FF] via-white to-[#E0E7FF]">
                                        <svg viewBox="0 0 48 48" class="h-10 w-10 text-[#4F46E5]" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="6" y="10" width="36" height="28" rx="6" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 28l7-7 6 6 7-7" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <h2 class="text-lg font-semibold text-[#111827]">{{ $service->namalayanan }}</h2>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-[#4F46E5]">Rp {{ $priceLabel }}</p>
                                            <p class="text-xs text-[#6B7280]">/ {{ $priceUnit }}</p>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-[#6B7280]">{{ $service->deskripsi }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-[#6B7280]">
                                    <div class="inline-flex items-center gap-2 rounded-full border border-[#E5E7EB] bg-white px-3 py-1">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#4F46E5]" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <circle cx="12" cy="12" r="8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2 2" />
                                        </svg>
                                        <span>{{ $service->durasi }} {{ $durationUnit }}</span>
                                    </div>
                                    @if ($service->kapasitas)
                                        <div class="inline-flex items-center gap-2 rounded-full border border-[#E5E7EB] bg-white px-3 py-1">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#4F46E5]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 10-8 0" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                                            </svg>
                                            <span>{{ $service->kapasitas }} People</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>

                        <div class="mt-6">
                            <x-customer.booking-calendar />
                        </div>
                    </section>

                    <x-customer.booking-sidebar :service="$service" />
                </form>
            </main>

            <section class="booking-shell mx-auto w-full max-w-[1280px] px-6 pb-12">
                <div class="flex flex-col gap-4 rounded-2xl border border-[#E5E7EB] bg-white p-6 booking-shadow md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-4">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#EEF2FF] text-[#4F46E5]">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7 3.582 7 8 7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7V3" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-[#111827]">Need help choosing?</h3>
                            <p class="mt-1 text-sm text-[#6B7280]">Our team can guide you to the right choice.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-xl border border-[#E5E7EB] px-4 py-2 text-sm font-semibold text-[#111827] transition hover:border-[#4F46E5] hover:text-[#4F46E5]"
                    >
                        Contact Sales
                    </button>
                </div>
            </section>

            <footer class="border-t border-[#E5E7EB] bg-[#EDEBFA]">
                <div class="booking-shell mx-auto w-full max-w-[1280px] px-6 py-10">
                    <div class="grid gap-8 md:grid-cols-4">
                        <div>
                            <h4 class="text-lg font-semibold text-[#4F46E5]">BookQu</h4>
                            <p class="mt-3 text-sm text-[#6B7280]">
                                Platform manajemen booking terjangkau di Indonesia untuk membantu bisnis jasa dan profesional
                                tampil digital.
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

        <script type="application/json" id="booking-service-data">@json($servicePayload)</script>
        <script type="application/json" id="booking-availability-data">@json($availabilityPayload)</script>
        <script defer src="{{ asset('js/booking-date.js') }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
