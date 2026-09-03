@props([
    'service'           => null,
    'buttonLabel'       => 'Lanjutkan',
    'buttonEnabledWhen' => 'true',
    'backUrl'           => null,
    'backLabel'         => 'Kembali',
    'onButtonClick'     => null,
])

{{-- Desktop Sticky Sidebar --}}
<aside class="hidden lg:block lg:sticky lg:top-24 h-fit">
    <div class="rounded-2xl border border-[#E2E8F0] bg-white p-6 shadow-sm">
        <div class="border-b border-[#F1F5F9] pb-4">
            <h2 class="text-base font-bold text-[#0F172A]">Ringkasan Booking</h2>
            <p class="text-xs text-[#64748B] mt-0.5">Periksa detail pesanan Anda</p>
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
                    @if ($service)
                        <p class="text-sm font-bold text-[#0F172A] truncate">{{ $service->namalayanan }}</p>
                        <p class="text-xs text-[#64748B] mt-0.5">{{ $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }}</p>
                    @else
                        <p class="text-sm font-bold text-[#0F172A] truncate" x-text="serviceName">Pilih layanan</p>
                        <p class="text-xs text-[#64748B] mt-0.5" x-text="serviceDuration">-</p>
                    @endif
                </div>
            </div>

            {{-- Jadwal (Tanggal & Jam) --}}
            <div class="flex items-start gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Jadwal Sesi</p>
                    <p class="text-sm font-bold text-[#0F172A] truncate" x-text="selectedDateLabel">
                        {{ isset($selectedDate) && $selectedDate ? \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d M Y') : 'Pilih tanggal' }}
                    </p>
                    <p class="text-xs text-[#64748B] mt-0.5" x-text="selectedTimeLabel">
                        {{ isset($selectedTime) && $selectedTime ? 'Pukul ' . $selectedTime . ' WIB' : 'Pilih jam' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Total Price Box --}}
        <div class="mt-6 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-[#64748B]">Total Biaya</span>
                <span class="text-xl font-extrabold text-[#4F46E5]" x-text="totalLabel">
                    {{ $service ? 'Rp ' . number_format($service->harga, 0, ',', '.') : 'Rp 0' }}
                </span>
            </div>
            <p class="mt-1 text-[11px] text-[#94A3B8]">Sudah termasuk pajak &amp; biaya layanan</p>
        </div>

        {{-- Primary CTA Button --}}
        <button
            type="{{ $onButtonClick ? 'button' : 'submit' }}"
            @if($onButtonClick) @click="{{ $onButtonClick }}" @endif
            class="mt-5 w-full flex items-center justify-center gap-2 rounded-xl py-3.5 px-4 text-sm font-bold text-white shadow-md shadow-[#4F46E5]/20 transition-all active:scale-98"
            :class="({{ $buttonEnabledWhen }}) && !isSubmitting
                ? 'bg-[#4F46E5] hover:bg-[#4338CA] hover:shadow-lg hover:shadow-[#4F46E5]/30 cursor-pointer'
                : 'bg-[#CBD5E1] text-[#94A3B8] cursor-not-allowed pointer-events-none'
            "
            :disabled="!({{ $buttonEnabledWhen }}) || isSubmitting"
        >
            <template x-if="isSubmitting">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memproses...
                </span>
            </template>
            <template x-if="!isSubmitting">
                <span class="flex items-center gap-2">
                    {{ $buttonLabel }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </span>
            </template>
        </button>

        @if ($backUrl)
            <a
                href="{{ $backUrl }}"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-[#E2E8F0] bg-white py-2.5 px-4 text-xs font-semibold text-[#64748B] transition hover:border-[#CBD5E1] hover:bg-[#F8FAFC] hover:text-[#0F172A]"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ $backLabel }}
            </a>
        @endif

        <div class="mt-4 flex items-center justify-center gap-1.5 text-[11px] font-medium text-[#64748B]">
            <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Reservasi Langsung &amp; Terjamin</span>
        </div>
    </div>
</aside>

{{-- Mobile Sticky Bottom Action Bar --}}
<div class="booking-mobile-bar lg:hidden">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-[11px] text-[#64748B] truncate">
                @if ($service)
                    {{ $service->namalayanan }}
                @else
                    <span x-text="serviceName">Pilih layanan</span>
                @endif
            </p>
            <p class="text-base font-black text-[#4F46E5]" x-text="totalLabel">
                {{ $service ? 'Rp ' . number_format($service->harga, 0, ',', '.') : 'Rp 0' }}
            </p>
        </div>

        <button
            type="{{ $onButtonClick ? 'button' : 'submit' }}"
            @if($onButtonClick) @click="{{ $onButtonClick }}" @endif
            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 shrink-0"
            :class="({{ $buttonEnabledWhen }}) && !isSubmitting
                ? 'bg-[#4F46E5] hover:bg-[#4338CA] cursor-pointer'
                : 'bg-[#CBD5E1] text-[#94A3B8] cursor-not-allowed pointer-events-none'
            "
            :disabled="!({{ $buttonEnabledWhen }}) || isSubmitting"
        >
            <template x-if="isSubmitting">
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Proses
                </span>
            </template>
            <template x-if="!isSubmitting">
                <span class="flex items-center gap-1.5">
                    {{ $buttonLabel }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </span>
            </template>
        </button>
    </div>
</div>
