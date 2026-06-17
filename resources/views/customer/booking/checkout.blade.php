<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $tenant->namabisnis }} - Checkout</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/booking-checkout.css') }}" />
        
        <!-- Midtrans Snap JS -->
        <script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
    </head>
    <body class="booking-page">
        <div
            id="checkout-root"
            data-tenant-slug="{{ $tenant->slug }}"
            data-snap-url="{{ $snapUrl }}"
            data-client-key="{{ $clientKey }}"
            x-data="bookingCheckout"
        >
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
                    id="checkout-form"
                    class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]"
                    method="POST"
                    action="{{ route('customer.booking.payment', $tenant->slug) }}"
                    @submit.prevent="submitCheckout"
                >
                    @csrf
                    <input type="hidden" name="schedule_id" value="{{ $scheduleId }}" />

                    <section>
                        <div class="booking-step">
                            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">
                                <span>FINAL STEP</span>
                                <span class="text-[#4F46E5]">CHECKOUT & PAYMENT</span>
                            </div>
                            <div class="mt-3 h-[3px] w-full rounded-full bg-[#E5E7EB]">
                                <div class="h-[3px] rounded-full bg-[#4F46E5]" style="width: 100%"></div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <h1 class="text-2xl font-bold text-[#111827] sm:text-3xl">Review &amp; Complete Your Booking</h1>
                            <p class="mt-2 text-sm text-[#6B7280] sm:text-base">Please provide your details before proceeding to payment.</p>
                        </div>

                        <div class="mt-8">
                            <div class="rounded-2xl border border-[#E5E7EB] bg-[#F9FAFB] p-5">
                                <h3 class="text-sm font-bold text-[#111827] mb-4">Booking Details</h3>
                                
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs text-[#6B7280]">Program</p>
                                        <p class="text-sm font-medium text-[#111827] mt-1">{{ $service->namalayanan }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#6B7280]">Location</p>
                                        <p class="text-sm font-medium text-[#111827] mt-1">{{ $tenant->namabisnis }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#6B7280]">Date</p>
                                        <p class="text-sm font-medium text-[#111827] mt-1">{{ $selectedDateLabel }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#6B7280]">Time &amp; Duration</p>
                                        <p class="text-sm font-medium text-[#111827] mt-1">{{ $selectedTime }} ({{ $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }})</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="serverError" x-cloak class="mt-6 rounded-xl border border-[#FCA5A5] bg-[#FEF2F2] px-4 py-3 text-sm text-[#B91C1C]" x-text="serverError"></div>

                        <div class="mt-8">
                            <h3 class="text-lg font-bold text-[#111827] mb-6">Your Information</h3>
                            
                            <div class="grid gap-x-6 sm:grid-cols-2">
                                <div class="checkout-form-group sm:col-span-2">
                                    <label class="checkout-label" for="nama">Full Name *</label>
                                    <input 
                                        type="text" 
                                        id="nama" 
                                        x-model="nama" 
                                        class="checkout-input" 
                                        :class="{'checkout-input--error': errors.nama}"
                                        placeholder="Enter your full name" 
                                    />
                                    <p class="checkout-error-msg" x-show="errors.nama" x-text="errors.nama"></p>
                                </div>
                                
                                <div class="checkout-form-group">
                                    <label class="checkout-label" for="email">Email Address *</label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        x-model="email" 
                                        class="checkout-input" 
                                        :class="{'checkout-input--error': errors.email}"
                                        placeholder="your@email.com" 
                                    />
                                    <p class="checkout-error-msg" x-show="errors.email" x-text="errors.email"></p>
                                </div>
                                
                                <div class="checkout-form-group">
                                    <label class="checkout-label" for="nomorhp">Phone Number *</label>
                                    <input 
                                        type="tel" 
                                        id="nomorhp" 
                                        x-model="nomorhp" 
                                        class="checkout-input" 
                                        :class="{'checkout-input--error': errors.nomorhp}"
                                        placeholder="081234567890" 
                                    />
                                    <p class="checkout-error-msg" x-show="errors.nomorhp" x-text="errors.nomorhp"></p>
                                </div>
                                
                                <div class="checkout-form-group sm:col-span-2">
                                    <label class="checkout-label" for="catatan">Order Notes (Optional)</label>
                                    <textarea 
                                        id="catatan" 
                                        x-model="catatan" 
                                        class="checkout-input" 
                                        rows="3" 
                                        placeholder="Any special requests or notes for the staff..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <x-customer.booking-policy />
                        </div>
                    </section>

                    <aside class="lg:sticky lg:top-24">
                        <div class="booking-summary rounded-2xl border border-[#E5E7EB] bg-white p-6 booking-shadow">
                            <div>
                                <h2 class="text-base font-semibold text-[#111827]">Order Summary</h2>
                                <p class="mt-1 text-sm text-[#6B7280]">Finalize your payment</p>
                            </div>

                            <div class="mt-6 space-y-4">
                                <div class="flex justify-between text-sm text-[#111827]">
                                    <span class="text-[#6B7280]">Subtotal</span>
                                    <span class="font-medium">{{ $priceLabel }}</span>
                                </div>
                                <div class="flex justify-between text-sm text-[#111827]">
                                    <span class="text-[#6B7280]">Tax</span>
                                    <span class="font-medium text-[#4F46E5]">Included</span>
                                </div>
                                
                                <div class="border-t border-[#E5E7EB] pt-4 mt-4 flex justify-between items-center">
                                    <span class="font-bold text-[#111827]">Total</span>
                                    <span class="text-xl font-bold text-[#111827]">{{ $priceLabel }}</span>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="mt-6 w-full rounded-xl bg-[#4F46E5] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#4338CA] focus:outline-none focus:ring-4 focus:ring-[#4F46E5]/30 flex justify-center items-center"
                                :class="{'btn-loading': isSubmitting}"
                                :disabled="isSubmitting"
                            >
                                Proceed to Payment
                            </button>

                            <div class="mt-4 flex items-center justify-center gap-2 text-[#6B7280]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span class="text-xs font-semibold uppercase tracking-[0.1em]">Secure checkout enabled</span>
                            </div>
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
                                Platform manajemen booking terjangkau di Indonesia untuk membantu bisnis jasa dan profesional
                                tampil digital.
                            </p>
                        </div>
                    </div>
                    <div class="mt-8 flex flex-col gap-3 border-t border-[#E5E7EB] pt-6 text-xs text-[#6B7280] md:flex-row md:items-center md:justify-between">
                        <span>&copy; 2026 BookQu. Hak Cipta Dilindungi Undang-Undang.</span>
                    </div>
                </div>
            </footer>
        </div>

        <script defer src="{{ asset('js/booking-checkout.js') }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
