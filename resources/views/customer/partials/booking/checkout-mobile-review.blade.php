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

        {{-- Expandable Price Breakdown (S4.2) --}}
        @if(count($selectedTimes) > 1)
            <details class="group rounded-xl border border-[#E2E8F0] bg-white p-3 text-xs">
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
                <div class="mt-2.5 pt-2 border-t border-[#F1F5F9] space-y-1.5">
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
    </div>
</div>
