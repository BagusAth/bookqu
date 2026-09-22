@extends('customer.layouts.booking-shell')

@section('title', 'Data Pemesan')
@section('current_step', 4)
@section('back_url', route(\App\Support\CustomerBookingRoutes::name('customer.booking.time'), $tenant->slug))
@section('back_label', 'Pilih Waktu')

@section('content')
<div id="booking-checkout-root" data-tenant-slug="{{ $tenant->slug }}">
    <form
        id="booking-checkout-form"
        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]"
        method="POST"
        action="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.process-checkout'), $tenant->slug) }}"
    >
        @csrf

        {{-- Left Column: Form Fields --}}
        <section>
            <div class="mb-6">
                <a
                    href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.time'), $tenant->slug) }}"
                    class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-[#64748B] hover:text-[#4F46E5] transition-colors mb-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Pilih Waktu
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Data Pemesan<span class="sr-only"> Isi Data Diri</span></h1>
                <p class="mt-1 text-sm text-[#64748B]">
                    Pastikan nama, email, dan nomor WhatsApp Anda sudah benar.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
                    <p class="font-bold mb-1">Terdapat kesalahan pada input Anda:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl border border-[#E2E8F0] bg-white p-4 sm:p-7 shadow-xs space-y-5">
                <div class="border-b border-[#F1F5F9] pb-3">
                    <h2 class="text-base font-bold text-[#0F172A]">Informasi Pemesan</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Pastikan data yang dimasukkan aktif dan valid.</p>
                </div>

                {{-- Nama Lengkap --}}
                <div>
                    <label for="namapelanggan" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="namapelanggan"
                            id="namapelanggan"
                            required
                            class="w-full rounded-xl border @error('namapelanggan') border-red-400 bg-red-50/30 @else border-[#CBD5E1] bg-[#F8FAFC] @enderror px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                            value="{{ old('namapelanggan') }}"
                            placeholder="Contoh: Budi Santoso"
                        />
                    </div>
                    @error('namapelanggan')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                        Alamat Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        required
                        class="w-full rounded-xl border @error('email') border-red-400 bg-red-50/30 @else border-[#CBD5E1] bg-[#F8FAFC] @enderror px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                        value="{{ old('email') }}"
                        placeholder="Contoh: budi@gmail.com"
                    />
                    <p class="mt-1.5 text-xs text-[#64748B]">Bukti reservasi &amp; e-ticket invoice akan dikirimkan ke email ini.</p>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- WhatsApp / No. HP --}}
                <div>
                    <label for="nomorhp" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                        Nomor WhatsApp / HP <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="tel"
                        name="nomorhp"
                        id="nomorhp"
                        required
                        class="w-full rounded-xl border @error('nomorhp') border-red-400 bg-red-50/30 @else border-[#CBD5E1] bg-[#F8FAFC] @enderror px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                        value="{{ old('nomorhp') }}"
                        placeholder="Contoh: 081234567890"
                    />
                    <p class="mt-1.5 text-xs text-[#64748B]">Digunakan untuk pengingat jadwal dan konfirmasi langsung.</p>
                    @error('nomorhp')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Catatan Opsional --}}
                <div>
                    <label for="catatan" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                        Catatan Khusus <span class="text-xs font-normal text-[#94A3B8]">(Opsional)</span>
                    </label>
                    <textarea
                        name="catatan"
                        id="catatan"
                        rows="3"
                        class="w-full rounded-xl border border-[#CBD5E1] bg-[#F8FAFC] px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                        placeholder="Tuliskan catatan khusus atau permintaan tambahan untuk penyedia layanan jika ada..."
                    >{{ old('catatan') }}</textarea>
                </div>

                {{-- Remember Me / Auto-fill Checkbox --}}
                <div class="pt-1 border-t border-[#F1F5F9]">
                    <label class="flex items-start sm:items-center gap-2.5 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            id="remember-customer-data"
                            class="mt-0.5 sm:mt-0 h-4 w-4 rounded border-gray-300 text-[#4F46E5] focus:ring-[#EEF2FF] cursor-pointer"
                        />
                        <span class="text-xs font-semibold text-[#475569]">
                            Simpan data kontak di perangkat ini untuk pemesanan berikutnya.
                        </span>
                    </label>
                </div>
            </div>

            {{-- Mobile Reservation Review Card (Section 28) --}}
            <div class="block lg:hidden mt-6 rounded-2xl border border-[#E2E8F0] bg-white p-4 sm:p-5 shadow-xs">
                <div class="border-b border-[#F1F5F9] pb-3 mb-4">
                    <h2 class="text-base font-bold text-[#0F172A]">Detail Reservasi</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Tinjau kembali rincian pemesanan Anda sebelum membayar</p>
                </div>

                <div class="space-y-3.5">
                    {{-- Layanan --}}
                    <div class="flex items-start gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Layanan</p>
                            <p class="text-sm font-bold text-[#0F172A] truncate">{{ $service->namalayanan }}</p>
                            <p class="text-xs text-[#64748B] mt-0.5">{{ $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }}</p>
                        </div>
                    </div>

                    {{-- Tanggal & Sesi --}}
                    <div class="flex items-start gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Jadwal Terpilih</p>
                            <p class="text-sm font-bold text-[#0F172A]">
                                {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($selectedTimes as $time)
                                    @php
                                        $st = \Carbon\Carbon::createFromFormat('H:i', substr($time, 0, 5));
                                        $et = (clone $st)->addMinutes($service->durasi);
                                    @endphp
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-[#4F46E5]/10 px-2 py-1 text-xs font-bold text-[#4F46E5]">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ substr($time, 0, 5) }} – {{ $et->format('H:i') }} WIB
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-[#64748B] mt-1.5 font-medium">
                                {{ count($selectedTimes) }} sesi ({{ count($selectedTimes) * $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }})
                            </p>
                        </div>
                    </div>

                    {{-- Total Harga --}}
                    <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3.5 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-[#64748B]">Total Bayar</p>
                            <p class="text-[11px] text-[#94A3B8]">Termasuk biaya layanan</p>
                        </div>
                        <span class="text-lg font-extrabold text-[#4F46E5]">
                            Rp {{ number_format($hargaAkhir, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Policy --}}
            <div class="mt-6">
                <x-customer.booking-policy />
            </div>
        </section>

        {{-- Right Column: Sticky Booking Summary --}}
        <aside class="hidden lg:block lg:sticky lg:top-24 h-fit">
            <div class="rounded-2xl border border-[#E2E8F0] bg-white p-6 shadow-sm">
                <div class="border-b border-[#F1F5F9] pb-4">
                    <h2 class="text-base font-bold text-[#0F172A]">Ringkasan Booking</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Tinjau kembali rincian pemesanan Anda</p>
                </div>

                <div class="mt-5 space-y-4">
                    {{-- Layanan --}}
                    <div class="flex items-start gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Layanan</p>
                            <p class="text-sm font-bold text-[#0F172A] truncate">{{ $service->namalayanan }}</p>
                            <p class="text-xs text-[#64748B] mt-0.5">{{ $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }}</p>
                        </div>
                    </div>

                    {{-- Jadwal --}}
                    <div class="flex items-start gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Jadwal Terpilih</p>
                            <p class="text-sm font-bold text-[#0F172A] truncate">
                                {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
                            </p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @foreach ($selectedTimes as $time)
                                    @php
                                        $st = \Carbon\Carbon::createFromFormat('H:i', substr($time, 0, 5));
                                        $et = (clone $st)->addMinutes($service->durasi);
                                    @endphp
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-[#4F46E5]/10 px-2 py-1 text-xs font-bold text-[#4F46E5]">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ substr($time, 0, 5) }} – {{ $et->format('H:i') }} WIB
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-[#64748B] mt-1 font-medium">
                                {{ count($selectedTimes) }} sesi ({{ count($selectedTimes) * $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }})
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Price difference alert if price is overridden --}}
                @if($hargaAkhir != $service->harga)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-800">
                    *Harga pada jadwal ini berbeda dengan harga standar layanan (Rp {{ number_format($service->harga, 0, ',', '.') }}).
                </div>
                @endif

                {{-- Total Biaya --}}
                <div class="mt-5 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4">
                    <div class="flex items-center justify-between text-xs text-[#64748B] mb-1.5">
                        <span>Biaya Layanan{{ count($selectedTimes) > 1 ? ' (' . count($selectedTimes) . ' slot)' : '' }}</span>
                        <span class="font-medium text-[#0F172A]">Rp {{ number_format($hargaAkhir, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-[#E2E8F0] pt-2 mt-2 flex items-center justify-between">
                        <span class="text-sm font-bold text-[#0F172A]">Total Bayar</span>
                        <span id="desktop-total-display" class="text-xl font-extrabold text-[#4F46E5]">
                            Rp {{ number_format($hargaAkhir, 0, ',', '.') }}
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-[#94A3B8]">Sudah termasuk pajak &amp; biaya layanan</p>
                </div>

                {{-- Submit CTA --}}
                <button
                    type="submit"
                    id="submit-checkout-btn"
                    class="mt-5 w-full flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] py-3.5 px-4 text-sm font-bold text-white shadow-md shadow-[#4F46E5]/20 transition-all hover:bg-[#4338CA] hover:shadow-lg hover:shadow-[#4F46E5]/30 cursor-pointer active:scale-98"
                >
                    <span>Lanjut ke Pembayaran</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </button>

                <a
                    href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.time'), $tenant->slug) }}"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-[#E2E8F0] bg-white py-2.5 px-4 text-xs font-semibold text-[#64748B] transition hover:border-[#CBD5E1] hover:bg-[#F8FAFC] hover:text-[#0F172A]"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Pilih Waktu
                </a>

                <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-[#64748B]">
                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span class="font-medium">Pembayaran aman &amp; terenkripsi</span>
                </div>
            </div>
        </aside>

        {{-- Mobile Bottom Floating Action Bar --}}
        <div class="booking-mobile-bar lg:hidden">
            <div class="max-w-4xl mx-auto flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] text-[#64748B] truncate">{{ $service->namalayanan }}</p>
                    <div class="flex items-baseline gap-1.5">
                        <p id="mobile-total-display" class="text-base font-black text-[#4F46E5]">
                            Rp {{ number_format($hargaAkhir, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
                <button
                    type="submit"
                    id="mobile-submit-checkout-btn"
                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 shrink-0"
                >
                    <span>Lanjut ke Pembayaran</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputName = document.getElementById('namapelanggan');
        const inputEmail = document.getElementById('email');
        const inputPhone = document.getElementById('nomorhp');
        const chkRemember = document.getElementById('remember-customer-data');
        const checkoutForm = document.getElementById('booking-checkout-form');
        const submitBtn = document.getElementById('submit-checkout-btn');
        const mobileSubmitBtn = document.getElementById('mobile-submit-checkout-btn');

        const STORAGE_KEY = 'bookqu_saved_customer';

        // Auto-fill from localStorage if available
        try {
            const savedData = localStorage.getItem(STORAGE_KEY);
            if (savedData) {
                const parsed = JSON.parse(savedData);
                if (parsed && typeof parsed === 'object') {
                    if (inputName && !inputName.value && parsed.name) inputName.value = parsed.name;
                    if (inputEmail && !inputEmail.value && parsed.email) inputEmail.value = parsed.email;
                    if (inputPhone && !inputPhone.value && parsed.phone) inputPhone.value = parsed.phone;
                    if (chkRemember) chkRemember.checked = true;
                }
            }
        } catch (e) {
            console.warn('LocalStorage not accessible:', e);
        }

        // Save or remove customer data upon submit & prevent double submission
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function () {
                if (chkRemember && chkRemember.checked) {
                    const dataToSave = {
                        name: inputName ? inputName.value.trim() : '',
                        email: inputEmail ? inputEmail.value.trim() : '',
                        phone: inputPhone ? inputPhone.value.trim() : ''
                    };
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(dataToSave));
                } else {
                    localStorage.removeItem(STORAGE_KEY);
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                }
                if (mobileSubmitBtn) {
                    mobileSubmitBtn.disabled = true;
                    mobileSubmitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                }
            });
        }
    });
</script>
@endsection
