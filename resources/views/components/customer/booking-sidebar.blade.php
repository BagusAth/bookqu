@props([
    'service',
    'buttonLabel' => 'Continue to Time Selection',
    'buttonEnabledWhen' => 'selectedDate',
])

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
                    <p class="text-xs text-[#6B7280]" x-text="selectedDateLabel">Choose your date</p>
                    <p class="text-xs text-[#6B7280]" x-text="selectedTimeLabel">Select time next</p>
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
                    <p class="text-xs text-[#6B7280]" x-text="totalLabel">Rp0.00</p>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] p-4">
            <div class="flex items-center justify-between text-sm font-semibold text-[#111827]">
                <span>Subtotal</span>
                <span x-text="totalLabel">Rp0.00</span>
            </div>
            <p class="mt-1 text-xs text-[#6B7280]">Tax included</p>
        </div>

        <button
            type="submit"
            class="mt-4 w-full rounded-xl px-4 py-3 text-sm font-semibold transition"
            :class="({{ $buttonEnabledWhen }})
                ? 'bg-[#4F46E5] text-white hover:bg-[#4338CA]'
                : 'cursor-not-allowed bg-[#E5E7EB] text-[#9CA3AF]'
            "
            :disabled="!({{ $buttonEnabledWhen }})"
        >
            {{ $buttonLabel }}
        </button>

        <p class="mt-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">Secure checkout enabled</p>
    </div>
</aside>
