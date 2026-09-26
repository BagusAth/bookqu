    @if ($view === 'week')
        @php
            $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            $daysOfWeek = [];
            for ($i = 0; $i < 7; $i++) {
                $cDate = $weekStart->copy()->addDays($i);
                $daysOfWeek[] = [
                    'name'     => $dayNames[$i],
                    'date'     => $cDate->format('d M'),
                    'fullDate' => $cDate->toDateString(),
                    'isToday'  => $cDate->isToday(),
                ];
            }

            // Collect unique hours from week schedules and bookings (default 08:00 - 20:00)
            $calendarHours = collect();
            foreach ($weekSchedules as $s) {
                $calendarHours->push(substr($s->jam_mulai, 0, 2) . ':00');
            }
            foreach ($bookings as $b) {
                if ($b->jam) {
                    $calendarHours->push(substr($b->jam, 0, 2) . ':00');
                }
            }
            $defaultHours = collect(['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00']);
            $hours = $defaultHours->merge($calendarHours)->unique()->sort()->values()->all();
        @endphp

        <div class="rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_24px_rgba(35,26,61,0.03)] overflow-hidden">
            {{-- Mobile Week View (Day Selector Strip + Day Timeline) --}}
            <div class="block sm:hidden border-b border-[#e7e2f7] bg-[#f7f7fa] p-2.5">
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                    @foreach ($daysOfWeek as $d)
                        @php
                            $dDate = $d['fullDate'];
                            $bCount = $bookings->filter(fn($b) => \Carbon\Carbon::parse($b->tanggalbooking)->toDateString() === $dDate)->count();
                            $sCount = $weekSchedules->filter(fn($s) => \Carbon\Carbon::parse($s->tanggal)->toDateString() === $dDate && $s->getAvailabilityStatus() === \App\Models\Schedule::STATUS_AVAILABLE)->count();
                            $isBlocked = isset($blockedDates[$dDate]);
                        @endphp
                        <button
                            type="button"
                            @click="mobileWeekDay = '{{ $dDate }}'"
                            class="flex-1 min-w-[46px] py-2 px-1 rounded-xl text-center flex flex-col items-center justify-center transition-all duration-150 cursor-pointer"
                            :class="mobileWeekDay === '{{ $dDate }}' ? 'bg-[#382186] text-white shadow-xs' : 'bg-white text-[#231a3d] border border-[#e7e2f7] hover:bg-[#f3effe]'"
                        >
                            <span class="text-[9px] uppercase font-black tracking-wider" :class="mobileWeekDay === '{{ $dDate }}' ? 'text-white/80' : 'text-[#6e6584]'">
                                {{ substr($d['name'], 0, 3) }}
                            </span>
                            <span class="text-xs font-black mt-0.5">{{ explode(' ', $d['date'])[0] }}</span>
                            <div class="flex items-center gap-0.5 mt-1 h-1.5">
                                @if ($bCount > 0)
                                    <span class="h-1.5 w-1.5 rounded-full" :class="mobileWeekDay === '{{ $dDate }}' ? 'bg-emerald-300' : 'bg-emerald-500'"></span>
                                @elseif ($sCount > 0)
                                    <span class="h-1.5 w-1.5 rounded-full" :class="mobileWeekDay === '{{ $dDate }}' ? 'bg-[#d8c7ff]' : 'bg-[#b499ff]'"></span>
                                @elseif ($isBlocked)
                                    <span class="h-1.5 w-1.5 rounded-full" :class="mobileWeekDay === '{{ $dDate }}' ? 'bg-rose-300' : 'bg-rose-500'"></span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Mobile Timeline Content per Day --}}
            <div class="block sm:hidden p-3.5 space-y-3">
                @foreach ($daysOfWeek as $d)
                    @php
                        $fullDate = $d['fullDate'];
                        $isDayBlocked = isset($blockedDates[$fullDate]);
                        $dayBookings = $bookings->filter(fn($b) => \Carbon\Carbon::parse($b->tanggalbooking)->toDateString() === $fullDate)->sortBy('jam');
                        $daySchedules = $weekSchedules->filter(fn($s) => \Carbon\Carbon::parse($s->tanggal)->toDateString() === $fullDate)->sortBy('jam_mulai');
                    @endphp
                    <div x-show="mobileWeekDay === '{{ $fullDate }}'" x-cloak class="space-y-3">
                        {{-- Day Header Banner --}}
                        <div class="flex items-center justify-between pb-2.5 border-b border-[#e7e2f7]">
                            <div>
                                <h3 class="text-sm font-extrabold text-[#231a3d]">{{ $d['name'] }}, {{ $d['date'] }}</h3>
                                <p class="text-[11px] text-[#6e6584] font-medium">{{ $dayBookings->count() }} Booking &bull; {{ $daySchedules->count() }} Slot Operasional</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if ($isDayBlocked)
                                    <span class="rounded-lg bg-rose-50 border border-rose-200 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                                        🔒 {{ $blockedDates[$fullDate] ?: 'Libur' }}
                                    </span>
                                @elseif ($d['isToday'])
                                    <span class="rounded-lg bg-[#f3effe] border border-[#b499ff]/50 px-2 py-0.5 text-[10px] font-extrabold text-[#382186]">
                                        Hari Ini
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($isDayBlocked && $dayBookings->isEmpty() && $daySchedules->isEmpty())
                            <div class="rounded-2xl border border-dashed border-rose-200 bg-rose-50/50 p-6 text-center text-xs text-rose-700 font-semibold space-y-1">
                                <p class="text-sm font-extrabold">Hari Ini Libur / Tutup Operasional</p>
                                <p class="text-[11px] opacity-80">{{ $blockedDates[$fullDate] ?: 'Tidak ada aktivitas layanan terjadwal.' }}</p>
                            </div>
                        @elseif ($dayBookings->isEmpty() && $daySchedules->isEmpty())
                            <div class="rounded-2xl border border-dashed border-[#e7e2f7] p-6 text-center text-xs text-[#6e6584] bg-[#f7f7fa]/60 space-y-2">
                                <p class="font-medium">Tidak ada booking atau slot jadwal pada tanggal ini.</p>
                                <a href="{{ route('owner.schedule') }}" class="inline-flex items-center gap-1 text-[11px] font-extrabold text-[#382186] hover:underline">
                                    + Buka Menu Schedule
                                </a>
                            </div>
                        @else
                            {{-- 1. Bookings on this day --}}
                            @if ($dayBookings->isNotEmpty())
                                <div class="space-y-2">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-[#6e6584]">Booking Transaksi ({{ $dayBookings->count() }})</p>
                                    @foreach ($dayBookings as $b)
                                        @php
                                            $bStatus = $b->status;
                                            $cardStyle = match($bStatus) {
                                                'paid'      => 'bg-emerald-50/80 border-emerald-300 text-emerald-950',
                                                'pending'   => 'bg-[#fff8eb] border-[#ffb84d] text-[#875000]',
                                                'completed' => 'bg-sky-50/80 border-sky-300 text-sky-950',
                                                'cancelled' => 'bg-rose-50/80 border-rose-200 text-rose-800 line-through opacity-70',
                                                default     => 'bg-[#f7f7fa] border-[#e7e2f7] text-[#231a3d]',
                                            };
                                            $badgeStatus = match($bStatus) {
                                                'paid'      => 'Confirmed',
                                                'pending'   => 'Pending',
                                                'completed' => 'Completed',
                                                'cancelled' => 'Cancelled',
                                                default     => ucfirst($bStatus),
                                            };
                                        @endphp
                                        <div
                                            @click="openDetail({
                                                raw_booking_id: {{ $b->id }},
                                                raw_schedule_id: {{ $b->idschedule ?? 0 }},
                                                raw_date: '{{ \Carbon\Carbon::parse($b->tanggalbooking)->toDateString() }}',
                                                is_booking: true,
                                                raw_status: '{{ $b->status }}',
                                                booking_id: '{{ $b->booking_code ?? ('#BKG-' . $b->id) }}',
                                                customer: '{{ addslashes($b->namapelanggan) }}',
                                                phone: '{{ $b->nomorhp }}',
                                                email: '{{ $b->email }}',
                                                service: '{{ addslashes($b->layanan->namalayanan ?? 'Layanan') }}',
                                                date: '{{ \Carbon\Carbon::parse($b->tanggalbooking)->translatedFormat('d F Y') }}',
                                                time: '{{ substr($b->jam, 0, 5) }}',
                                                payment_status: '{{ $b->payment ? ucfirst($b->payment->status) : ($b->status === 'paid' ? 'Sukses' : 'Pending') }}',
                                                booking_status: '{{ $badgeStatus }}',
                                                amount: 'Rp {{ number_format($b->payment->jumlah ?? $b->layanan->harga ?? 0, 0, ',', '.') }}',
                                                notes: '{{ addslashes($b->catatan ?? '-') }}'
                                            })"
                                            class="craft-card rounded-xl border p-3 cursor-pointer shadow-2xs {{ $cardStyle }}"
                                        >
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="flex h-7 px-2 items-center justify-center rounded-lg bg-white/90 text-xs font-black text-[#231a3d] border border-black/5 shadow-2xs shrink-0">
                                                        {{ substr($b->jam, 0, 5) }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-black truncate">{{ $b->layanan->namalayanan ?? 'Layanan' }}</p>
                                                        <p class="text-[11px] font-medium opacity-90 truncate">{{ $b->namapelanggan }}</p>
                                                    </div>
                                                </div>
                                                <span class="shrink-0 text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-white shadow-2xs border border-black/5">
                                                    {{ $badgeStatus }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- 2. Available Slots on this day --}}
                            @if ($daySchedules->isNotEmpty())
                                <div class="space-y-2 pt-2 border-t border-[#e7e2f7]">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-[#6e6584]">Slot Jadwal Operasional ({{ $daySchedules->count() }})</p>
                                    @foreach ($daySchedules as $sched)
                                        @php
                                            $schedStatus = $sched->getAvailabilityStatus();
                                        @endphp
                                        @if ($schedStatus === \App\Models\Schedule::STATUS_AVAILABLE)
                                            <div
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
                                                    notes: 'Slot aktif ini terbuka untuk reservasi pelanggan.'
                                                })"
                                                class="craft-card rounded-xl border border-dashed border-[#b499ff] bg-[#f3effe]/60 hover:bg-[#f3effe] p-3 text-[#231a3d] cursor-pointer shadow-2xs"
                                            >
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-xs font-black text-[#382186]">{{ substr($sched->jam_mulai, 0, 5) }} - {{ substr($sched->jam_selesai, 0, 5) }}</span>
                                                            <span class="rounded bg-[#e7e2f7] px-1.5 py-0.2 text-[9px] font-black uppercase text-[#382186]">Tersedia</span>
                                                        </div>
                                                        <p class="text-xs font-bold text-[#231a3d] truncate mt-0.5">{{ $sched->layanan->namalayanan ?? 'Layanan' }}</p>
                                                        <p class="text-[10px] text-[#6e6584]">Rp {{ number_format($sched->harga_override ?? $sched->layanan->harga ?? 0, 0, ',', '.') }}</p>
                                                    </div>
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
                                                            notes: 'Slot aktif ini terbuka untuk reservasi pelanggan.'
                                                        }); walkinMode = true"
                                                        class="shrink-0 craft-btn rounded-lg bg-[#382186] hover:bg-[#2d1a6d] px-2.5 py-1.5 text-[10px] font-black text-white shadow-2xs"
                                                    >
                                                        + Walk-in
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Desktop Week Grid (Table) --}}
            <div class="hidden sm:block overflow-x-auto">
                <div class="min-w-[840px]">
                    {{-- Week Days Header --}}
                    <div class="grid grid-cols-8 border-b border-[#e7e2f7] bg-[#f7f7fa] text-center text-xs font-bold text-[#231a3d]">
                        <div class="py-3 px-2 text-[#6e6584] border-r border-[#e7e2f7] font-semibold">Waktu</div>
                        @foreach ($daysOfWeek as $d)
                            <div class="py-3 px-2 border-r last:border-r-0 border-[#e7e2f7] {{ $d['isToday'] ? 'bg-[#f3effe] text-[#382186]' : '' }}">
                                <p class="uppercase tracking-wider text-[10px] text-[#6e6584] font-bold">{{ $d['name'] }}</p>
                                <p class="text-xs font-extrabold text-[#231a3d] mt-0.5">
                                    @if ($d['isToday'])
                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-[#382186] text-white text-[11px] font-extrabold shadow-2xs">
                                            {{ $d['date'] }}
                                        </span>
                                    @else
                                        {{ $d['date'] }}
                                    @endif
                                </p>
                                @if (isset($blockedDates[$d['fullDate']]))
                                    <span class="inline-flex items-center gap-0.5 mt-1 rounded-md bg-[#e7e2f7] px-1.5 py-0.5 text-[9px] font-bold text-[#6e6584]" title="Alasan: {{ $blockedDates[$d['fullDate']] ?: 'Libur Operasional' }}">
                                        <span>🔒 Libur</span>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Time Slots Rows --}}
                    <div class="divide-y divide-[#e7e2f7]">
                        @foreach ($hours as $hour)
                            <div class="grid grid-cols-8 min-h-[68px]">
                                <div class="py-2.5 px-3 text-right text-[11px] font-bold text-[#6e6584] border-r border-[#e7e2f7] bg-[#f7f7fa]/60 select-none">
                                    {{ $hour }}
                                </div>

                                @foreach ($daysOfWeek as $d)
                                    @php
                                        $fullDate = $d['fullDate'];
                                        $isDayBlocked = isset($blockedDates[$fullDate]);

                                        // 1. Match bookings for this day and hour
                                        $cellBookings = $bookings->filter(function ($b) use ($fullDate, $hour) {
                                            $tgl = \Carbon\Carbon::parse($b->tanggalbooking)->toDateString();
                                            return $tgl === $fullDate && str_starts_with($b->jam, substr($hour, 0, 2));
                                        });

                                        // 2. Match schedules for this day and hour
                                        $cellSchedules = $weekSchedules->filter(function ($s) use ($fullDate, $hour) {
                                            $tgl = \Carbon\Carbon::parse($s->tanggal)->toDateString();
                                            return $tgl === $fullDate && str_starts_with($s->jam_mulai, substr($hour, 0, 2));
                                        });
                                    @endphp

                                    <div class="p-1 border-r last:border-r-0 border-[#e7e2f7] transition-colors duration-150 hover:bg-[#f7f7fa]/60 relative">
                                        @if ($cellBookings->isNotEmpty())
                                            @foreach ($cellBookings as $matchedBooking)
                                                @php
                                                    $bStatus = $matchedBooking->status;
                                                    $cardStyle = match($bStatus) {
                                                        'paid'      => 'bg-emerald-50/90 text-emerald-950 border-emerald-300 hover:border-emerald-400',
                                                        'pending'   => 'bg-[#fff8eb] text-[#875000] border-[#ffb84d] hover:border-[#ffb84d]/90',
                                                        'completed' => 'bg-sky-50/90 text-sky-950 border-sky-300 hover:border-sky-400',
                                                        'cancelled' => 'bg-rose-50/70 text-rose-800 border-rose-200 line-through opacity-70',
                                                        default     => 'bg-[#f7f7fa] text-[#231a3d] border-[#e7e2f7]',
                                                    };
                                                    $badgeStatus = match($bStatus) {
                                                        'paid'      => 'Confirmed',
                                                        'pending'   => 'Pending',
                                                        'completed' => 'Completed',
                                                        'cancelled' => 'Cancelled',
                                                        default     => ucfirst($bStatus),
                                                    };
                                                @endphp
                                                <div
                                                    @click="openDetail({
                                                        raw_booking_id: {{ $matchedBooking->id }},
                                                        raw_schedule_id: {{ $matchedBooking->idschedule ?? 0 }},
                                                        raw_date: '{{ \Carbon\Carbon::parse($matchedBooking->tanggalbooking)->toDateString() }}',
                                                        is_booking: true,
                                                        raw_status: '{{ $matchedBooking->status }}',
                                                        booking_id: '{{ $matchedBooking->booking_code ?? ('#BKG-' . $matchedBooking->id) }}',
                                                        customer: '{{ addslashes($matchedBooking->namapelanggan) }}',
                                                        phone: '{{ $matchedBooking->nomorhp }}',
                                                        email: '{{ $matchedBooking->email }}',
                                                        service: '{{ addslashes($matchedBooking->layanan->namalayanan ?? 'Layanan') }}',
                                                        date: '{{ \Carbon\Carbon::parse($matchedBooking->tanggalbooking)->translatedFormat('d F Y') }}',
                                                        time: '{{ substr($matchedBooking->jam, 0, 5) }}',
                                                        payment_status: '{{ $matchedBooking->payment ? ucfirst($matchedBooking->payment->status) : ($matchedBooking->status === 'paid' ? 'Sukses' : 'Pending') }}',
                                                        booking_status: '{{ $badgeStatus }}',
                                                        amount: 'Rp {{ number_format($matchedBooking->payment->jumlah ?? $matchedBooking->layanan->harga ?? 0, 0, ',', '.') }}',
                                                        notes: '{{ addslashes($matchedBooking->catatan ?? '-') }}'
                                                    })"
                                                    class="craft-card mb-1 rounded-xl border p-2 text-[11px] font-bold cursor-pointer shadow-2xs {{ $cardStyle }}"
                                                >
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="truncate leading-tight font-extrabold">{{ $matchedBooking->layanan->namalayanan ?? 'Layanan' }}</span>
                                                        <span class="text-[9px] uppercase px-1.5 py-0.2 rounded-md bg-white/80 font-bold shadow-2xs">{{ $badgeStatus }}</span>
                                                    </div>
                                                    <p class="text-[10px] font-medium opacity-90 truncate mt-1">{{ $matchedBooking->namapelanggan }} ({{ substr($matchedBooking->jam, 0, 5) }})</p>
                                                </div>
                                            @endforeach
                                        @elseif ($cellSchedules->isNotEmpty())
                                            @foreach ($cellSchedules as $sched)
                                                @php
                                                    $schedStatus = $sched->getAvailabilityStatus();
                                                @endphp
                                                @if ($schedStatus === \App\Models\Schedule::STATUS_AVAILABLE)
                                                    <div
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
                                                            notes: 'Slot aktif ini terbuka untuk reservasi pelanggan.'
                                                        })"
                                                        class="craft-card h-full rounded-xl border border-dashed border-[#b499ff] bg-[#f3effe]/60 hover:bg-[#f3effe] p-2 text-[10px] text-[#231a3d] font-semibold cursor-pointer shadow-2xs"
                                                    >
                                                        <div class="flex items-center justify-between">
                                                            <span class="truncate font-extrabold text-[#382186]">{{ $sched->layanan->namalayanan ?? 'Layanan' }}</span>
                                                            <span class="rounded-md bg-[#e7e2f7] px-1.5 py-0.2 text-[8px] font-extrabold uppercase text-[#382186]">Tersedia</span>
                                                        </div>
                                                        <div class="mt-1.5 flex items-center justify-between">
                                                            <span class="text-[9px] text-[#6e6584] font-semibold">{{ substr($sched->jam_mulai, 0, 5) }} - {{ substr($sched->jam_selesai, 0, 5) }}</span>
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
                                                                    notes: 'Slot aktif ini terbuka untuk reservasi pelanggan.'
                                                                }); walkinMode = true"
                                                                class="rounded-md bg-[#382186] hover:bg-[#2d1a6d] px-1.5 py-0.5 text-[8px] font-extrabold text-white transition shadow-2xs"
                                                                title="Catat Reservasi Walk-in Tamu"
                                                            >
                                                                + Walk-in
                                                            </button>
                                                        </div>
                                                    </div>
                                                @elseif ($schedStatus === \App\Models\Schedule::STATUS_BLOCKED)
                                                    <div class="h-full rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] p-1.5 text-[9px] text-[#6e6584] flex items-center justify-center font-semibold italic">
                                                        [Diblokir]
                                                    </div>
                                                @else
                                                    <div class="h-full rounded-xl border border-[#e7e2f7] bg-[#f7f7fa]/60 p-1.5 text-[9px] text-[#6e6584]/60 flex items-center justify-center">
                                                        [Nonaktif]
                                                    </div>
                                                @endif
                                            @endforeach
                                        @elseif ($isDayBlocked)
                                            <div class="h-full rounded-xl bg-[#f7f7fa] border border-[#e7e2f7] flex items-center justify-center text-[10px] text-[#6e6584] font-semibold italic">
                                                Libur
                                            </div>
                                        @else
                                            <div class="h-full rounded-xl border border-dashed border-transparent hover:border-[#e7e2f7] flex items-center justify-center text-[10px] text-[#e7e2f7] transition">
                                                -
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
