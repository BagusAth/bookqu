<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $tenant->namabisnis }} - Program Selection</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
    </head>
    <body class="booking-page">
        <div id="booking-program-root" data-tenant-slug="{{ $tenant->slug }}" x-data="bookingProgram()">
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
                    action="{{ route('customer.booking.select-program', $tenant->slug) }}"
                    x-ref="confirmForm"
                >
                    @csrf
                    <input type="hidden" name="service_id" x-model="selectedServiceId" />
                    <section>
                        <div class="booking-step">
                            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">
                                <span>STEP 1 OF 3</span>
                                <span class="text-[#4F46E5]">Program Selection</span>
                            </div>
                            <div class="mt-3 h-[3px] w-full rounded-full bg-[#E5E7EB]">
                                <div class="h-[3px] w-1/3 rounded-full bg-[#4F46E5]"></div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <h1 class="text-2xl font-bold text-[#111827] sm:text-3xl">Available Programs</h1>
                            <p class="mt-2 text-sm text-[#6B7280] sm:text-base">
                                Choose the perfect space or service for your needs.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-6 sm:grid-cols-2">
                            @forelse ($services as $service)
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
                                <article
                                    class="booking-card group flex h-full flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition"
                                    :class="selectedServiceId === {{ $service->id }} ? 'booking-card--selected' : ''"
                                >
                                    <div class="relative">
                                        <div class="booking-card__media aspect-[16/9] w-full overflow-hidden bg-[#EEF2FF]">
                                            @if ($imageUrl)
                                                <img
                                                    src="{{ $imageUrl }}"
                                                    alt="{{ $service->namalayanan }}"
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                />
                                            @else
                                                <div class="booking-card__placeholder flex h-full w-full items-center justify-center bg-gradient-to-br from-[#EEF2FF] via-white to-[#E0E7FF]">
                                                    <svg viewBox="0 0 48 48" class="h-12 w-12 text-[#4F46E5]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <rect x="6" y="10" width="36" height="28" rx="6" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 28l7-7 6 6 7-7" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        @if ($service->is_popular)
                                            <span class="booking-badge absolute left-4 top-4 rounded-full bg-[#4F46E5] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-white">
                                                POPULAR
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex h-full flex-col p-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <h3 class="text-lg font-semibold text-[#111827]">{{ $service->namalayanan }}</h3>
                                            <div class="text-right">
                                                <p class="text-sm font-semibold text-[#4F46E5]">Rp {{ $priceLabel }}</p>
                                                <p class="text-xs text-[#6B7280]">/ {{ $priceUnit }}</p>
                                            </div>
                                        </div>
                                        <p class="mt-3 text-sm text-[#6B7280]">{{ $service->deskripsi }}</p>
                                        <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-[#6B7280]">
                                            <div class="inline-flex items-center gap-2 rounded-full border border-[#E5E7EB] bg-[#F9FAFB] px-3 py-1">
                                                <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#4F46E5]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <circle cx="12" cy="12" r="8" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2 2" />
                                                </svg>
                                                <span>{{ $service->durasi }} {{ $durationUnit }}</span>
                                            </div>
                                            @if ($service->kapasitas)
                                                <div class="inline-flex items-center gap-2 rounded-full border border-[#E5E7EB] bg-[#F9FAFB] px-3 py-1">
                                                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#4F46E5]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 10-8 0" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                                                    </svg>
                                                    <span>{{ $service->kapasitas }} People</span>
                                                </div>
                                            @endif
                                        </div>
                                        <button
                                            type="button"
                                            class="booking-select-btn mt-5 w-full rounded-xl border px-4 py-2 text-sm font-semibold transition"
                                            :class="selectedServiceId === {{ $service->id }} ? 'booking-select-btn--active' : ''"
                                            @click="selectServiceById({{ $service->id }})"
                                            :aria-pressed="selectedServiceId === {{ $service->id }}"
                                        >
                                            Select Program
                                        </button>
                                    </div>
                                </article>
                            @empty
                                <div class="col-span-full rounded-2xl border border-[#E5E7EB] bg-white p-6 text-center text-sm text-[#6B7280]">
                                    No programs are available yet. Please check back soon.
                                </div>
                            @endforelse

                            @php
                                $promoOffset = $services->count() % 2 === 1 ? 'sm:col-start-2' : '';
                            @endphp
                            <article class="booking-gradient-card {{ $promoOffset }} flex h-full flex-col justify-between rounded-2xl px-6 py-8 text-white">
                                <div class="space-y-4 text-center">
                                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15">
                                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 9h16v11H4z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 9l8-5 8 5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 22V12m6 10V12" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-semibold">All-Access Pass</h3>
                                        <p class="mt-2 text-sm text-white/80">
                                            Unlock everything with our monthly subscription. Unlimited studio and field time.
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-3xl font-bold">Rp299K</span>
                                        <span class="text-sm font-semibold text-white/70">/mo</span>
                                    </div>
                                </div>
                                <button type="button" class="mt-6 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#4F46E5]">
                                    Upgrade Now
                                </button>
                            </article>
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
                                        <p class="text-xs text-[#6B7280]" x-text="selectedService ? selectedService.name : 'No program selected yet'">
                                            No program selected yet
                                        </p>
                                        <p
                                            class="text-xs text-[#6B7280]"
                                            x-text="selectedService ? `${selectedService.price_label} / ${selectedService.price_unit}` : 'Select a program to see pricing'"
                                        >
                                            Select a program to see pricing
                                        </p>
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
                                        <p class="text-xs text-[#6B7280]">Choose next</p>
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
                                        <p class="text-xs text-[#6B7280]">Auto-calculated</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] p-4">
                                <div class="flex items-center justify-between text-sm font-semibold text-[#111827]">
                                    <span>Total</span>
                                    <span x-text="totalLabel">Rp0.00</span>
                                </div>
                                <p class="mt-1 text-xs text-[#6B7280]">Tax included</p>
                            </div>

                            <button
                                type="button"
                                class="mt-4 w-full rounded-xl px-4 py-3 text-sm font-semibold transition"
                                :class="selectedService
                                    ? 'bg-[#4F46E5] text-white hover:bg-[#4338CA]'
                                    : 'cursor-not-allowed bg-[#E5E7EB] text-[#9CA3AF]'
                                "
                                @click="handleConfirm()"
                                :disabled="!selectedService"
                            >
                                Confirm Selection
                            </button>
                        </div>
                    </aside>
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

        <script type="application/json" id="booking-services-data">@json($servicesPayload)</script>
        <script defer src="{{ asset('js/booking-program.js') }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
