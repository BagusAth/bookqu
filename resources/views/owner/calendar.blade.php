@extends('layouts.owner-layout')

@section('title', 'Calendar')

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        viewMode: '{{ $view }}',
        selectedSlot: null,
        modalOpen: false,
        walkinMode: false,
        mobileWeekDay: '{{ $currentDate->toDateString() }}',

        openDetail(slot) {
            this.selectedSlot = slot;
            this.walkinMode = false;
            this.modalOpen = true;
        }
    }"
    @keydown.escape.window="modalOpen = false"
>
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-[#231a3d] tracking-tight sm:text-3xl">Calendar &amp; Jadwal Operasional</h1>
        </div>
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <a
                href="{{ route('owner.schedule') }}"
                class="craft-btn flex-1 sm:flex-none justify-center inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-[#231a3d] hover:bg-[#f7f7fa] hover:border-[#b499ff] shadow-2xs active:scale-95"
            >
                <svg class="h-4 w-4 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span class="truncate">Kelola Schedule</span>
            </a>
            <a
                href="{{ route('owner.bookings') }}"
                class="craft-btn flex-1 sm:flex-none justify-center inline-flex items-center gap-2 rounded-xl bg-[#382186] hover:bg-[#2d1a6d] px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs active:scale-95"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span class="truncate">Daftar Bookings</span>
            </a>
        </div>
    </div>

    {{-- Controls Bar: View Switcher, Date Navigator, Filters --}}
    <div class="rounded-2xl border border-[#e7e2f7] bg-white p-3.5 sm:p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)] space-y-3.5">
        <div class="flex flex-col xl:flex-row gap-3 xl:items-center xl:justify-between">
            {{-- Left cluster: View Switcher & Date Navigation --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
                {{-- Segmented View Switcher Tabs (Day / Week / Month) --}}
                <div class="flex items-center gap-1 rounded-xl bg-[#f7f7fa] p-1 border border-[#e7e2f7] w-full sm:w-auto justify-between sm:justify-start shrink-0">
                    <a
                        href="{{ route('owner.calendar', ['view' => 'day', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="flex-1 sm:flex-none text-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150 cursor-pointer {{ $view === 'day' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d] hover:bg-white/60' }}"
                    >
                        Day
                    </a>
                    <a
                        href="{{ route('owner.calendar', ['view' => 'week', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="flex-1 sm:flex-none text-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150 cursor-pointer {{ $view === 'week' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d] hover:bg-white/60' }}"
                    >
                        Week
                    </a>
                    <a
                        href="{{ route('owner.calendar', ['view' => 'month', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="flex-1 sm:flex-none text-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150 cursor-pointer {{ $view === 'month' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d] hover:bg-white/60' }}"
                    >
                        Month
                    </a>
                </div>

                {{-- Date Navigation --}}
                <div class="flex items-center justify-between sm:justify-start gap-2 w-full sm:w-auto">
                    <a
                        href="{{ route('owner.calendar', ['view' => $view, 'date' => $todayDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="craft-btn rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] hover:bg-[#e7e2f7] px-3 py-1.5 text-xs font-bold text-[#382186] shadow-2xs active:scale-95 shrink-0"
                    >
                        Hari Ini
                    </a>
                    <div class="flex-1 sm:flex-none flex items-center justify-between rounded-xl border border-[#e7e2f7] bg-white shadow-2xs overflow-hidden">
                        <a
                            href="{{ route('owner.calendar', ['view' => $view, 'date' => $prevDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                            class="p-2 text-[#6e6584] hover:text-[#382186] hover:bg-[#f7f7fa] transition border-r border-[#e7e2f7] active:scale-95"
                            aria-label="Previous"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <span class="px-2 sm:px-3.5 py-1.5 text-xs font-bold text-[#231a3d] min-w-[110px] sm:min-w-[150px] text-center tracking-wide truncate">
                            {{ $dateLabel }}
                        </span>
                        <a
                            href="{{ route('owner.calendar', ['view' => $view, 'date' => $nextDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                            class="p-2 text-[#6e6584] hover:text-[#382186] hover:bg-[#f7f7fa] transition border-l border-[#e7e2f7] active:scale-95"
                            aria-label="Next"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right cluster: Service & Status Filters --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:flex items-center gap-2 w-full xl:w-auto">
                <select
                    onchange="window.location.href = updateCalendarFilter('service_id', this.value)"
                    class="w-full xl:w-44 rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] hover:bg-white px-3 py-1.5 text-xs font-semibold text-[#231a3d] focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 focus:outline-none transition shadow-2xs truncate"
                >
                    <option value="all" {{ $selectedService === 'all' ? 'selected' : '' }}>Semua Layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" {{ (string)$selectedService === (string)$service->id ? 'selected' : '' }}>{{ $service->namalayanan }}</option>
                    @endforeach
                </select>

                <select
                    onchange="window.location.href = updateCalendarFilter('status', this.value)"
                    class="w-full xl:w-44 rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] hover:bg-white px-3 py-1.5 text-xs font-semibold text-[#231a3d] focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 focus:outline-none transition shadow-2xs truncate"
                >
                    <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="available" {{ $selectedStatus === 'available' ? 'selected' : '' }}>Slot Kosong (Available)</option>
                    <option value="paid" {{ $selectedStatus === 'paid' ? 'selected' : '' }}>Confirmed (Lunas)</option>
                    <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending (Menunggu Bayar)</option>
                    <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                    <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Cancelled (Dibatalkan)</option>
                    <option value="blocked" {{ $selectedStatus === 'blocked' ? 'selected' : '' }}>Blocked (Libur / Tutup)</option>
                </select>
            </div>
        </div>

        {{-- Legend Indicators (Horizontal scroll on mobile) --}}
        <div class="flex items-center gap-3 overflow-x-auto pb-1 sm:pb-0 pt-3.5 border-t border-[#e7e2f7] text-xs text-[#6e6584] no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap">
            <span class="font-extrabold text-[#231a3d] shrink-0">Indikator:</span>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-2xs"></span>
                <span class="font-medium">Confirmed</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="h-2.5 w-2.5 rounded-full bg-[#ffb84d] shadow-2xs"></span>
                <span class="font-medium">Pending</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="h-2.5 w-2.5 rounded-full bg-sky-500 shadow-2xs"></span>
                <span class="font-medium">Completed</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="h-2.5 w-2.5 rounded-full bg-[#b499ff] shadow-2xs"></span>
                <span class="font-medium">Slot Tersedia</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-400 shadow-2xs"></span>
                <span class="font-medium">Cancelled</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="h-2.5 w-2.5 rounded-full bg-[#6e6584] shadow-2xs"></span>
                <span class="font-medium">Libur</span>
            </div>
        </div>
    </div>

    {{-- Week View (Default) --}}
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

    {{-- Month View --}}
    @if ($view === 'month')
        @php
            $startOfMonth = $currentDate->copy()->startOfMonth();
            $daysInMonth = $currentDate->daysInMonth;
            $dayOfWeekIso = $startOfMonth->dayOfWeekIso; // 1 = Monday .. 7 = Sunday
            $startDayOffset = $dayOfWeekIso - 1; // 0 for Monday
            $totalCells = (int) ceil(($startDayOffset + $daysInMonth) / 7) * 7;
        @endphp

        <div class="rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_24px_rgba(35,26,61,0.03)] p-3.5 sm:p-5 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-3 border-b border-[#e7e2f7]">
                <h2 class="text-base font-extrabold text-[#231a3d]">{{ $currentDate->translatedFormat('F Y') }}</h2>
                <span class="text-xs text-[#6e6584] font-medium">Klik tanggal untuk melihat jadwal harian lengkap</span>
            </div>

            <div class="grid grid-cols-7 gap-px rounded-2xl border border-[#e7e2f7] bg-[#e7e2f7] overflow-hidden text-center text-xs">
                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $dayName)
                    <div class="bg-[#f7f7fa] py-2 sm:py-2.5 font-extrabold text-[#6e6584] uppercase tracking-wider text-[10px] sm:text-[11px]">
                        <span class="sm:hidden">{{ substr($dayName, 0, 3) }}</span>
                        <span class="hidden sm:inline">{{ $dayName }}</span>
                    </div>
                @endforeach

                @foreach (range(0, $totalCells - 1) as $cell)
                    @php
                        $dayNum = $cell - $startDayOffset + 1;
                        $isValidDay = ($dayNum >= 1 && $dayNum <= $daysInMonth);
                        $cellDate = $isValidDay ? $startOfMonth->copy()->addDays($dayNum - 1)->toDateString() : null;
                        $dayBookings = $isValidDay ? ($monthBookings->get($dayNum) ?? collect()) : collect();
                        $daySchedules = $isValidDay ? ($monthSchedules->get($dayNum) ?? collect()) : collect();
                        $isBlocked = $cellDate && isset($blockedDates[$cellDate]);
                        $isToday = $cellDate && \Carbon\Carbon::parse($cellDate)->isToday();

                        $confirmedCount = $dayBookings->where('status', 'paid')->count();
                        $pendingCount = $dayBookings->where('status', 'pending')->count();
                        $availCount = $daySchedules->filter(fn($s) => $s->getAvailabilityStatus() === \App\Models\Schedule::STATUS_AVAILABLE)->count();
                    @endphp

                    <div class="bg-white min-h-[58px] sm:min-h-[96px] p-1 sm:p-2 text-left relative transition duration-150 hover:bg-[#f7f7fa] {{ !$isValidDay ? 'bg-[#f7f7fa]/60' : '' }}">
                        @if ($isValidDay)
                            <a
                                href="{{ route('owner.calendar', ['view' => 'day', 'date' => $cellDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                                class="block h-full cursor-pointer group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] sm:text-xs font-extrabold {{ $isToday ? 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#382186] text-white shadow-2xs' : 'text-[#231a3d] group-hover:text-[#382186]' }}">
                                        {{ $dayNum }}
                                    </span>
                                    @if ($isBlocked)
                                        <span class="rounded bg-[#e7e2f7] px-1 text-[8px] font-bold text-[#6e6584]" title="Alasan: {{ $blockedDates[$cellDate] ?: 'Libur Operasional' }}">
                                            <span class="sm:hidden">🔒</span>
                                            <span class="hidden sm:inline">Libur</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Desktop: Full Text Badges --}}
                                <div class="hidden sm:block mt-1.5 space-y-1">
                                    @if ($confirmedCount > 0)
                                        <span class="block truncate rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 border border-emerald-200/50">
                                            {{ $confirmedCount }} Confirmed
                                        </span>
                                    @endif
                                    @if ($pendingCount > 0)
                                        <span class="block truncate rounded-md bg-[#fff8eb] px-1.5 py-0.5 text-[10px] font-bold text-[#875000] border border-[#ffb84d]/50">
                                            {{ $pendingCount }} Pending
                                        </span>
                                    @endif
                                    @if ($availCount > 0)
                                        <span class="block truncate rounded-md bg-[#f3effe] px-1.5 py-0.5 text-[10px] font-bold text-[#382186] border border-[#b499ff]/40">
                                            {{ $availCount }} Slot Tersedia
                                        </span>
                                    @endif
                                </div>

                                {{-- Mobile: Compact Event Indicators --}}
                                <div class="flex sm:hidden flex-wrap items-center justify-center gap-1 mt-1">
                                    @if ($confirmedCount > 0)
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-emerald-500 text-white text-[8px] font-black shadow-2xs" title="{{ $confirmedCount }} Confirmed">
                                            {{ $confirmedCount }}
                                        </span>
                                    @endif
                                    @if ($pendingCount > 0)
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-[#ffb84d] text-white text-[8px] font-black shadow-2xs" title="{{ $pendingCount }} Pending">
                                            {{ $pendingCount }}
                                        </span>
                                    @endif
                                    @if ($availCount > 0)
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-[#b499ff] text-white text-[8px] font-black shadow-2xs" title="{{ $availCount }} Slot Tersedia">
                                            {{ $availCount }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Detail Modal Drawer (All 10 required fields, responsive bottom sheet on mobile) --}}
    <div
        id="calendar-detail-modal"
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        @click.self="modalOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-lg rounded-t-3xl sm:rounded-3xl bg-white p-4 sm:p-6 shadow-2xl border border-[#e7e2f7] space-y-4 max-h-[90vh] sm:max-h-[85vh] overflow-y-auto transform transition-all duration-200"
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 sm:scale-95"
        >
            {{-- Mobile Drag Handle --}}
            <div class="sm:hidden flex justify-center -mt-1 pb-1">
                <div class="w-10 h-1 rounded-full bg-[#e7e2f7]"></div>
            </div>

            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3 sm:pb-3.5">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#231a3d]">Detail Booking Calendar</h3>
                        <span class="inline-block mt-0.5 rounded-md bg-[#e7e2f7] px-2 py-0.5 text-[11px] sm:text-xs font-mono font-bold text-[#382186]" x-text="selectedSlot ? selectedSlot.booking_id : ''"></span>
                    </div>
                </div>
                <button
                    @click="modalOpen = false"
                    class="rounded-xl p-1.5 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa] transition active:scale-95"
                    aria-label="Tutup"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedSlot">
                <div class="space-y-3 sm:space-y-3.5 text-sm">
                    {{-- 1. Service & Amount Banner --}}
                    <div class="rounded-2xl bg-[#f7f7fa] p-3.5 sm:p-4 border border-[#e7e2f7] flex items-center justify-between">
                        <div>
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-extrabold">Layanan / Service</p>
                            <p class="text-sm sm:text-base font-black text-[#231a3d] mt-0.5" x-text="selectedSlot.service"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-extrabold">Amount / Tarif</p>
                            <p class="text-base sm:text-lg font-black text-[#382186] mt-0.5" x-text="selectedSlot.amount"></p>
                        </div>
                    </div>

                    {{-- 2. Date & Time --}}
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Tanggal / Date</p>
                            <p class="text-xs sm:text-sm font-extrabold text-[#231a3d] mt-0.5" x-text="selectedSlot.date"></p>
                        </div>
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Waktu / Time</p>
                            <p class="text-xs sm:text-sm font-black text-[#382186] mt-0.5" x-text="selectedSlot.time"></p>
                        </div>
                    </div>

                    {{-- 3. Customer Info --}}
                    <div class="rounded-xl border border-[#e7e2f7] bg-white p-3 sm:p-3.5 space-y-1">
                        <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Informasi Pelanggan</p>
                        <p class="font-black text-[#231a3d] text-xs sm:text-sm" x-text="selectedSlot.customer"></p>
                        <div class="flex flex-wrap items-center gap-2.5 pt-0.5 text-xs text-[#6e6584] font-medium">
                            <span class="inline-flex items-center gap-1">📞 <span x-text="selectedSlot.phone"></span></span>
                            <span class="inline-flex items-center gap-1">✉️ <span x-text="selectedSlot.email"></span></span>
                        </div>
                    </div>

                    {{-- 4. Status Separation: Booking Status & Payment Status --}}
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Booking Status</p>
                            <div class="mt-1 sm:mt-1.5">
                                <span
                                    class="inline-flex rounded-full px-2 sm:px-2.5 py-0.5 text-[10px] sm:text-xs font-black uppercase border"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': selectedSlot.booking_status === 'Confirmed',
                                        'bg-[#fff8eb] text-[#875000] border-[#ffb84d]': selectedSlot.booking_status === 'Pending',
                                        'bg-sky-100 text-sky-800 border-sky-200': selectedSlot.booking_status === 'Completed',
                                        'bg-rose-100 text-rose-800 border-rose-200': selectedSlot.booking_status === 'Cancelled',
                                        'bg-[#f3effe] text-[#382186] border-[#b499ff]': selectedSlot.booking_status === 'Available',
                                        'bg-[#f7f7fa] text-[#231a3d] border-[#e7e2f7]': !['Confirmed', 'Pending', 'Completed', 'Cancelled', 'Available'].includes(selectedSlot.booking_status)
                                    }"
                                    x-text="selectedSlot.booking_status"
                                ></span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Payment Status</p>
                            <div class="mt-1 sm:mt-1.5">
                                <span
                                    class="inline-flex rounded-full px-2 sm:px-2.5 py-0.5 text-[10px] sm:text-xs font-black uppercase border"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': selectedSlot.payment_status.toLowerCase() === 'sukses',
                                        'bg-[#fff8eb] text-[#875000] border-[#ffb84d]': selectedSlot.payment_status.toLowerCase() === 'pending',
                                        'bg-rose-100 text-rose-800 border-rose-200': ['gagal', 'failed', 'expired'].includes(selectedSlot.payment_status.toLowerCase()),
                                        'bg-[#f7f7fa] text-[#6e6584] border-[#e7e2f7]': !['sukses', 'pending', 'gagal', 'failed', 'expired'].includes(selectedSlot.payment_status.toLowerCase())
                                    }"
                                    x-text="selectedSlot.payment_status"
                                ></span>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Notes --}}
                    <div class="rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] p-2.5 sm:p-3">
                        <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Catatan / Notes</p>
                        <p class="text-xs text-[#231a3d] mt-1 italic" x-text="selectedSlot.notes"></p>
                    </div>

                    {{-- Walk-in Invitation Banner when available slot is selected and walkinMode is false --}}
                    <template x-if="selectedSlot && !selectedSlot.is_booking && selectedSlot.raw_status === 'available' && !walkinMode">
                        <div class="mt-3 rounded-2xl border border-dashed border-[#b499ff] bg-[#f3effe]/60 p-3.5 text-center space-y-2">
                            <p class="text-xs font-bold text-[#382186]">Slot ini masih kosong &amp; siap diisi reservasi walk-in tamu di tempat.</p>
                            <button
                                type="button"
                                @click="walkinMode = true"
                                class="craft-btn w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Buka Form Walk-in Tamu</span>
                            </button>
                        </div>
                    </template>

                    {{-- Walk-in Booking Form (When Available slot is selected and walkinMode is active) --}}
                    <div x-show="walkinMode" class="mt-4 pt-4 border-t border-[#e7e2f7] space-y-3 rounded-2xl bg-[#f3effe]/40 p-3.5 sm:p-4 border border-[#b499ff]/50">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-[#382186] uppercase tracking-wider">Form Reservasi Walk-in Langsung</h4>
                            <span class="text-[10px] text-[#6e6584] font-semibold">Tamu Datang Langsung</span>
                        </div>
                        <form method="POST" action="{{ route('owner.bookings.walkin') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="idschedule" :value="selectedSlot.raw_schedule_id">
                            <div>
                                <label class="block text-xs font-bold text-[#231a3d]">Nama Pelanggan <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    name="namapelanggan"
                                    required
                                    placeholder="Nama tamu walk-in..."
                                    class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                >
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-bold text-[#231a3d]">No. WhatsApp / HP <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        name="nomorhp"
                                        required
                                        placeholder="08..."
                                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[#231a3d]">Email (Opsional)</label>
                                    <input
                                        type="email"
                                        name="email"
                                        placeholder="email@tamu.com"
                                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#231a3d]">Metode Pembayaran</label>
                                <select
                                    name="metode"
                                    class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                >
                                    <option value="cash">Tunai (Cash di Tempat)</option>
                                    <option value="transfer">Transfer Bank Manual</option>
                                    <option value="qris">QRIS Langsung</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#231a3d]">Catatan Khusus (Opsional)</label>
                                <input
                                    type="text"
                                    name="catatan"
                                    placeholder="Catatan tamu atau permintaan khusus..."
                                    class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                >
                            </div>
                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    @click="walkinMode = false"
                                    class="craft-btn flex-1 sm:flex-none justify-center rounded-xl px-3.5 py-2 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs"
                                >
                                    Konfirmasi Walk-in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

            <div class="pt-3 border-t border-[#e7e2f7] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Quick Action for Confirmed Booking --}}
                    <template x-if="selectedSlot && selectedSlot.is_booking && selectedSlot.raw_status === 'paid'">
                        <div class="flex items-center gap-1.5 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="modalOpen = false; $dispatch('open-owner-reschedule', {
                                    id: selectedSlot.raw_booking_id,
                                    booking_code: selectedSlot.booking_id,
                                    namapelanggan: selectedSlot.customer,
                                    idschedule: selectedSlot.raw_schedule_id,
                                    tanggalbooking: selectedSlot.raw_date || selectedSlot.date,
                                    jam: selectedSlot.time,
                                    layanan: { namalayanan: selectedSlot.service }
                                })"
                                class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer flex items-center gap-1.5"
                                title="Ubah jadwal / slot reservasi ini"
                            >
                                <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Ubah Jadwal</span>
                            </button>
                            <form method="POST" :action="`/owner/bookings/${selectedSlot.raw_booking_id}/status`" class="flex-1 sm:flex-none inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer">
                                    ✓ Selesai
                                </button>
                            </form>
                            <form method="POST" :action="`/owner/bookings/${selectedSlot.raw_booking_id}/status`" :id="`form-calendar-cancel-${selectedSlot.raw_booking_id}`" class="flex-1 sm:flex-none inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button
                                    type="button"
                                    @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan booking ' + selectedSlot.booking_id + ' untuk ' + selectedSlot.customer + '?', formId: `form-calendar-cancel-${selectedSlot.raw_booking_id}`, confirmText: 'Ya, Batalkan' })"
                                    class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer"
                                >
                                    Batalkan
                                </button>
                            </form>
                        </div>
                    </template>

                    {{-- Quick Action for Pending Booking --}}
                    <template x-if="selectedSlot && selectedSlot.is_booking && selectedSlot.raw_status === 'pending'">
                        <div class="flex items-center gap-1.5 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="modalOpen = false; $dispatch('open-owner-reschedule', {
                                    id: selectedSlot.raw_booking_id,
                                    booking_code: selectedSlot.booking_id,
                                    namapelanggan: selectedSlot.customer,
                                    idschedule: selectedSlot.raw_schedule_id,
                                    tanggalbooking: selectedSlot.raw_date || selectedSlot.date,
                                    jam: selectedSlot.time,
                                    layanan: { namalayanan: selectedSlot.service }
                                })"
                                class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer flex items-center gap-1.5"
                                title="Ubah jadwal / slot reservasi ini"
                            >
                                <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Ubah Jadwal</span>
                            </button>
                            <form method="POST" :action="`/owner/bookings/${selectedSlot.raw_booking_id}/status`" :id="`form-calendar-cancel-${selectedSlot.raw_booking_id}`" class="w-full sm:w-auto inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button
                                    type="button"
                                    @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan pesanan pending ' + selectedSlot.booking_id + ' ini?', formId: `form-calendar-cancel-${selectedSlot.raw_booking_id}`, confirmText: 'Ya, Batalkan' })"
                                    class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer"
                                >
                                    Batalkan
                                </button>
                            </form>
                        </div>
                    </template>

                    {{-- Actions for Available Slot (Walk-in & Delete Slot) --}}
                    <template x-if="selectedSlot && !selectedSlot.is_booking && selectedSlot.raw_status === 'available' && !walkinMode">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="walkinMode = true"
                                class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#382186] hover:bg-[#2d1a6d] px-3.5 py-2 text-xs font-bold text-white shadow-xs cursor-pointer"
                            >
                                + Walk-in Booking
                            </button>
                            <form method="POST" :action="`/owner/schedule/slots/${selectedSlot.raw_schedule_id}`" :id="`form-delete-calendar-slot-${selectedSlot.raw_schedule_id}`" class="flex-1 sm:flex-none inline">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="button"
                                    @click="$dispatch('open-confirm', { title: 'Hapus Slot Jadwal?', message: 'Slot ketersediaan pada jam ' + selectedSlot.time + ' ini akan dihapus dari jadwal operasional. Lanjutkan?', formId: `form-delete-calendar-slot-${selectedSlot.raw_schedule_id}`, confirmText: 'Ya, Hapus Slot' })"
                                    class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer"
                                    title="Hapus slot jadwal ini"
                                >
                                    Hapus Slot
                                </button>
                            </form>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="modalOpen = false"
                        class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#f7f7fa] border border-[#e7e2f7] px-4 py-2 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#e7e2f7] transition cursor-pointer"
                    >
                        Tutup
                    </button>
                    <a
                        href="{{ route('owner.bookings') }}"
                        class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition cursor-pointer"
                    >
                        Lihat di Daftar Booking
                    </a>
                </div>
            </div>
        </div>
</div>

{{-- Owner Reschedule Booking Modal Component --}}
<x-owner.modal-reschedule-booking />

<script>
function updateCalendarFilter(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, val);
    return url.toString();
}
</script>
@endsection
