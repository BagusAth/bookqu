    {{-- Day View --}}
    @if ($view === 'day')
        @php
            $targetDateStr = $currentDate->toDateString();
            $dayBookings = $bookings->filter(fn($b) => \Carbon\Carbon::parse($b->tanggalbooking)->toDateString() === $targetDateStr);
            $daySchedules = $schedules->filter(fn($s) => \Carbon\Carbon::parse($s->tanggal)->toDateString() === $targetDateStr);
            $isBlockedToday = isset($blockedDates[$targetDateStr]);
        @endphp

        <div class="rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_24px_rgba(35,26,61,0.03)] p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-[#e7e2f7] pb-4">
                <div>
                    <h2 class="text-lg font-extrabold text-[#231a3d] tracking-tight">Jadwal Harian &mdash; {{ $currentDate->translatedFormat('l, d F Y') }}</h2>
                    <p class="text-xs text-[#6e6584] mt-0.5 font-medium">{{ $dayBookings->count() }} Booking &bull; {{ $daySchedules->count() }} Total Slot Schedule</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($currentDate->isToday())
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-[#f3effe] border border-[#b499ff]/50 px-3 py-1.5 text-xs font-bold text-[#382186] shadow-2xs">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#382186] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#382186]"></span>
                            </span>
                            <span>Hari Ini &bull; Waktu Sekarang {{ now()->format('H:i') }} WIB</span>
                        </span>
                    @endif
                    @if ($isBlockedToday)
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700 shadow-2xs">
                            <span>⚠️ Tanggal Diblokir: {{ $blockedDates[$targetDateStr] ?: 'Libur Operasional' }}</span>
                        </span>
                    @endif
                </div>
            </div>

            {{-- 1. Transaksi Bookings Pada Hari Ini --}}
            <div class="space-y-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-[#6e6584]">Booking Aktif &amp; Riwayat Hari Ini</h3>
                @if ($dayBookings->isNotEmpty())
                    <div class="divide-y divide-[#e7e2f7] rounded-2xl border border-[#e7e2f7] bg-white overflow-hidden shadow-2xs">
                        @foreach ($dayBookings as $item)
                            @php
                                $badgeClass = match($item->status) {
                                    'paid'      => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'pending'   => 'bg-[#fff8eb] text-[#875000] border-[#ffb84d]',
                                    'completed' => 'bg-sky-100 text-sky-800 border-sky-200',
                                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    default     => 'bg-[#f7f7fa] text-[#231a3d] border-[#e7e2f7]',
                                };
                                $badgeLabel = match($item->status) {
                                    'paid'      => 'Confirmed',
                                    'pending'   => 'Pending',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                    default     => ucfirst($item->status),
                                };
                            @endphp
                            <div
                                @click="openDetail({
                                    raw_booking_id: {{ $item->id }},
                                    raw_schedule_id: {{ $item->idschedule ?? 0 }},
                                    raw_date: '{{ \Carbon\Carbon::parse($item->tanggalbooking)->toDateString() }}',
                                    is_booking: true,
                                    raw_status: '{{ $item->status }}',
                                    booking_id: '{{ $item->booking_code ?? ('#BKG-' . $item->id) }}',
                                    customer: '{{ addslashes($item->namapelanggan) }}',
                                    phone: '{{ $item->nomorhp }}',
                                    email: '{{ $item->email }}',
                                    service: '{{ addslashes($item->layanan->namalayanan ?? 'Layanan') }}',
                                    date: '{{ \Carbon\Carbon::parse($item->tanggalbooking)->translatedFormat('d F Y') }}',
                                    time: '{{ substr($item->jam, 0, 5) }}',
                                    payment_status: '{{ $item->payment ? ucfirst($item->payment->status) : ($item->status === 'paid' ? 'Sukses' : 'Pending') }}',
                                    booking_status: '{{ $badgeLabel }}',
                                    amount: 'Rp {{ number_format($item->payment->jumlah ?? $item->layanan->harga ?? 0, 0, ',', '.') }}',
                                    notes: '{{ addslashes($item->catatan ?? '-') }}'
                                })"
                                class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-[#f7f7fa] transition duration-150 cursor-pointer"
                            >
                                <div class="flex items-center gap-3.5">
                                    <span class="flex h-11 w-16 shrink-0 items-center justify-center rounded-xl bg-[#f3effe] text-xs font-black text-[#382186] border border-[#e7e2f7]">
                                        {{ substr($item->jam, 0, 5) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-extrabold text-[#231a3d]">{{ $item->layanan->namalayanan ?? 'Layanan' }}</p>
                                        <p class="text-xs text-[#6e6584] mt-0.5 font-medium">{{ $item->namapelanggan }} &bull; {{ $item->nomorhp }} &bull; {{ $item->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xs font-black text-[#231a3d]">
                                        Rp {{ number_format($item->payment->jumlah ?? $item->layanan->harga ?? 0, 0, ',', '.') }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-extrabold uppercase border {{ $badgeClass }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-[#e7e2f7] p-8 text-center text-sm text-[#6e6584] bg-[#f7f7fa]/40">
                        Tidak ada booking transaksi pada tanggal ini.
                    </div>
                @endif
            </div>

            {{-- 2. Ketersediaan Slot Schedule Pada Hari Ini --}}
            <div class="space-y-3 pt-4 border-t border-[#e7e2f7]">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-[#6e6584]">Slot Schedule Operasional Hari Ini</h3>
                @if ($daySchedules->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        @foreach ($daySchedules as $sched)
                            @php
                                $avail = $sched->getAvailabilityStatus();
                                $hasBooking = $sched->bookings->whereIn('status', ['paid', 'pending', 'completed'])->first();
                            @endphp
                            <div
                                @if ($avail === \App\Models\Schedule::STATUS_AVAILABLE)
                                    @click="openDetail({
                                        raw_booking_id: null,
                                        raw_schedule_id: {{ $sched->id }},
                                        is_booking: false,
                                        raw_status: 'available',
                                        booking_id: 'SCHED-{{ $sched->id }}',
                                        customer: 'Belum Terisi (Slot Tersedia)',
                                        phone: '-',
                                        email: '-',
                                        service: '{{ addslashes($sched->layanan->namalayanan ?? 'Layanan') }}',
                                        date: '{{ \Carbon\Carbon::parse($sched->tanggal)->translatedFormat('d F Y') }}',
                                        time: '{{ substr($sched->jam_mulai, 0, 5) }} - {{ substr($sched->jam_selesai, 0, 5) }}',
                                        payment_status: 'Belum Ada Transaksi',
                                        booking_status: 'Available',
                                        amount: 'Rp {{ number_format($sched->harga_override ?? $sched->layanan->harga ?? 0, 0, ',', '.') }}',
                                        notes: 'Slot aktif ini terbuka untuk reservasi pelanggan atau walk-in booking.'
                                    })"
                                    class="craft-card rounded-2xl border p-4 border-[#b499ff] bg-[#f3effe]/50 hover:bg-[#f3effe] cursor-pointer shadow-2xs"
                                @else
                                    class="rounded-2xl border p-4 border-[#e7e2f7] bg-white opacity-90 shadow-2xs"
                                @endif
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black text-[#382186]">
                                        {{ substr($sched->jam_mulai, 0, 5) }} - {{ substr($sched->jam_selesai, 0, 5) }}
                                    </span>
                                    @if ($avail === \App\Models\Schedule::STATUS_AVAILABLE)
                                        <span class="rounded-md bg-[#e7e2f7] px-2 py-0.5 text-[10px] font-extrabold text-[#382186] uppercase">Tersedia</span>
                                    @elseif ($avail === \App\Models\Schedule::STATUS_BOOKED)
                                        <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-extrabold text-emerald-800 uppercase">Booked</span>
                                    @elseif ($avail === \App\Models\Schedule::STATUS_BLOCKED)
                                        <span class="rounded-md bg-[#e7e2f7] px-2 py-0.5 text-[10px] font-extrabold text-[#6e6584] uppercase">Diblokir</span>
                                    @else
                                        <span class="rounded-md bg-[#f7f7fa] px-2 py-0.5 text-[10px] font-extrabold text-[#6e6584] uppercase">Unavailable</span>
                                    @endif
                                </div>
                                <p class="text-sm font-extrabold text-[#231a3d] mt-1.5">{{ $sched->layanan->namalayanan ?? 'Layanan' }}</p>
                                <p class="text-xs text-[#6e6584] mt-0.5 font-medium">
                                    @if ($hasBooking)
                                        Dipesan oleh: <span class="font-bold text-[#231a3d]">{{ $hasBooking->namapelanggan }}</span>
                                    @else
                                        Tarif: <span class="font-bold text-[#231a3d]">Rp {{ number_format($sched->harga_override ?? $sched->layanan->harga ?? 0, 0, ',', '.') }}</span>
                                    @endif
                                </p>
                                @if ($avail === \App\Models\Schedule::STATUS_AVAILABLE)
                                    <div class="mt-3 pt-2.5 border-t border-[#b499ff]/30 flex items-center justify-between">
                                        <span class="text-[10px] text-[#6e6584] font-medium">Slot siap dipesan</span>
                                        <button
                                            type="button"
                                            @click.stop="openDetail({
                                                raw_booking_id: null,
                                                raw_schedule_id: {{ $sched->id }},
                                                is_booking: false,
                                                raw_status: 'available',
                                                booking_id: 'SCHED-{{ $sched->id }}',
                                                customer: 'Belum Terisi (Slot Tersedia)',
                                                phone: '-',
                                                email: '-',
                                                service: '{{ addslashes($sched->layanan->namalayanan ?? 'Layanan') }}',
                                                date: '{{ \Carbon\Carbon::parse($sched->tanggal)->translatedFormat('d F Y') }}',
                                                time: '{{ substr($sched->jam_mulai, 0, 5) }} - {{ substr($sched->jam_selesai, 0, 5) }}',
                                                payment_status: 'Belum Ada Transaksi',
                                                booking_status: 'Available',
                                                amount: 'Rp {{ number_format($sched->harga_override ?? $sched->layanan->harga ?? 0, 0, ',', '.') }}',
                                                notes: 'Slot aktif ini terbuka untuk reservasi pelanggan atau walk-in booking.'
                                            }); walkinMode = true"
                                            class="craft-btn inline-flex items-center gap-1 rounded-lg bg-[#382186] px-2.5 py-1 text-[10px] font-bold text-white hover:bg-[#2d1a6d] shadow-2xs transition"
                                        >
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <span>+ Walk-in</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-[#e7e2f7] p-8 text-center text-sm text-[#6e6584] bg-[#f7f7fa]/40">
                        Tidak ada slot schedule yang dibuat untuk tanggal ini.
                    </div>
                @endif
            </div>
        </div>
    @endif
