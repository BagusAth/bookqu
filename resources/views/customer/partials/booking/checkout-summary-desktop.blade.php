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

        {{-- Expandable Price Breakdown (S4.2) --}}
        @if(count($selectedTimes) > 1)
            <details class="group mt-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3 text-xs">
                <summary class="flex items-center justify-between cursor-pointer font-semibold text-[#475569] hover:text-[#4F46E5] transition-colors list-none select-none">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Rincian Harga Tiap Sesi</span>
                    </span>
                    <svg class="h-3.5 w-3.5 transition-transform duration-200 group-open:rotate-180 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="mt-2.5 pt-2 border-t border-[#E2E8F0] space-y-1.5">
                    @foreach ($selectedTimes as $i => $time)
                        @php
                            $st = \Carbon\Carbon::createFromFormat('H:i', substr($time, 0, 5));
                            $et = (clone $st)->addMinutes($service->durasi);
                            $slotPrice = $schedules[$i]?->harga_override ?? $service->harga;
                        @endphp
                        <div class="flex justify-between items-center text-[#64748B]">
                            <span>Sesi {{ $i + 1 }} ({{ $st->format('H:i') }} – {{ $et->format('H:i') }})</span>
                            <span class="font-medium text-[#0F172A]">Rp {{ number_format($slotPrice, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

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

        <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-[#64748B]">
            <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span class="font-medium">Pembayaran aman &amp; terenkripsi</span>
        </div>
    </div>
</aside>
