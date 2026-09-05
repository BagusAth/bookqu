@extends('layouts.owner-layout')

@section('title', 'Calendar')

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        viewMode: '{{ $view }}',
        selectedSlot: null,
        modalOpen: false,

        openDetail(slot) {
            this.selectedSlot = slot;
            this.modalOpen = true;
        }
    }"
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-bq-text sm:text-3xl">Calendar &amp; Jadwal Operasional</h1>
            <p class="mt-1 text-sm text-bq-text-muted">Visualisasi terpadu ketersediaan slot Schedule dan transaksi Booking.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('owner.schedule') }}" class="inline-flex items-center gap-2 rounded-xl border border-bq-border bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-bq-text hover:bg-bq-surface transition-all shadow-2xs">
                <svg class="h-4 w-4 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Kelola Schedule</span>
            </a>
            <a href="{{ route('owner.bookings') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs transition-all active:scale-98">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span>Daftar Bookings</span>
            </a>
        </div>
    </div>

    {{-- Controls Bar: View Switcher, Date Navigator, Filters --}}
    <div class="rounded-2xl border border-bq-border bg-white p-4 shadow-2xs space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            {{-- View Switcher Tabs --}}
            <div class="flex items-center gap-1 rounded-xl bg-slate-100 p-1 w-fit">
                <a
                    href="{{ route('owner.calendar', ['view' => 'day', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer {{ $view === 'day' ? 'bg-white text-[#4F46E5] font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}"
                >
                    Day
                </a>
                <a
                    href="{{ route('owner.calendar', ['view' => 'week', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer {{ $view === 'week' ? 'bg-white text-[#4F46E5] font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}"
                >
                    Week
                </a>
                <a
                    href="{{ route('owner.calendar', ['view' => 'month', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer {{ $view === 'month' ? 'bg-white text-[#4F46E5] font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}"
                >
                    Month
                </a>
            </div>

            {{-- Date Navigation --}}
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('owner.calendar', ['view' => $view, 'date' => $todayDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="rounded-xl border border-bq-border bg-white px-3 py-1.5 text-xs font-bold text-bq-text hover:bg-slate-50 transition shadow-2xs"
                >
                    Hari Ini
                </a>
                <div class="flex items-center rounded-xl border border-bq-border bg-white shadow-2xs">
                    <a
                        href="{{ route('owner.calendar', ['view' => $view, 'date' => $prevDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="p-2 text-slate-500 hover:text-slate-800 transition border-r border-bq-border"
                        aria-label="Previous"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <span class="px-4 py-1.5 text-xs font-bold text-bq-text min-w-[170px] text-center">
                        {{ $dateLabel }}
                    </span>
                    <a
                        href="{{ route('owner.calendar', ['view' => $view, 'date' => $nextDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="p-2 text-slate-500 hover:text-slate-800 transition border-l border-bq-border"
                        aria-label="Next"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Service & Status Filters --}}
            <div class="flex flex-wrap items-center gap-2">
                <select
                    onchange="window.location.href = updateCalendarFilter('service_id', this.value)"
                    class="rounded-xl border border-bq-border bg-white px-3 py-1.5 text-xs font-medium text-slate-700 focus:border-[#4F46E5] focus:outline-none"
                >
                    <option value="all" {{ $selectedService === 'all' ? 'selected' : '' }}>Semua Layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" {{ (string)$selectedService === (string)$service->id ? 'selected' : '' }}>{{ $service->namalayanan }}</option>
                    @endforeach
                </select>

                <select
                    onchange="window.location.href = updateCalendarFilter('status', this.value)"
                    class="rounded-xl border border-bq-border bg-white px-3 py-1.5 text-xs font-medium text-slate-700 focus:border-[#4F46E5] focus:outline-none"
                >
                    <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="available" {{ $selectedStatus === 'available' ? 'selected' : '' }}>Slot Tersedia (Available)</option>
                    <option value="paid" {{ $selectedStatus === 'paid' ? 'selected' : '' }}>Confirmed (Lunas)</option>
                    <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending (Menunggu Bayar)</option>
                    <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                    <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Cancelled (Dibatalkan)</option>
                    <option value="blocked" {{ $selectedStatus === 'blocked' ? 'selected' : '' }}>Blocked (Diblokir)</option>
                </select>
            </div>
        </div>

        {{-- Legend Indicators --}}
        <div class="flex flex-wrap items-center gap-4 pt-3 border-t border-slate-100 text-xs text-slate-600">
            <span class="font-bold text-slate-900">Indikator:</span>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <span>Confirmed (Lunas)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                <span>Pending</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                <span>Completed</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-indigo-400"></span>
                <span>Slot Tersedia (Available)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                <span>Cancelled</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                <span>Blocked / Libur</span>
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

        <div class="rounded-2xl border border-bq-border bg-white shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <div class="min-w-[820px]">
                    {{-- Week Days Header --}}
                    <div class="grid grid-cols-8 border-b border-bq-border bg-slate-50/80 text-center text-xs font-bold text-slate-700">
                        <div class="py-3 px-2 text-slate-400 border-r border-bq-border">Waktu</div>
                        @foreach ($daysOfWeek as $d)
                            <div class="py-3 px-2 border-r last:border-r-0 border-bq-border {{ $d['isToday'] ? 'bg-[#EEF2FF]/60 text-[#4F46E5]' : '' }}">
                                <p class="uppercase tracking-wider text-[11px]">{{ $d['name'] }}</p>
                                <p class="text-xs font-extrabold text-slate-900 mt-0.5">{{ $d['date'] }}</p>
                                @if (isset($blockedDates[$d['fullDate']]))
                                    <span class="inline-block mt-0.5 rounded bg-slate-200 px-1 text-[9px] font-bold text-slate-700">Libur</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Time Slots Rows --}}
                    <div class="divide-y divide-slate-100">
                        @foreach ($hours as $hour)
                            <div class="grid grid-cols-8 min-h-[64px]">
                                <div class="py-2.5 px-3 text-right text-[11px] font-semibold text-slate-400 border-r border-bq-border bg-slate-50/40">
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

                                    <div class="p-1 border-r last:border-r-0 border-bq-border transition hover:bg-slate-50/70 relative">
                                        @if ($cellBookings->isNotEmpty())
                                            @foreach ($cellBookings as $matchedBooking)
                                                @php
                                                    $bStatus = $matchedBooking->status;
                                                    $cardStyle = match($bStatus) {
                                                        'paid'      => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                                                        'pending'   => 'bg-amber-50 text-amber-900 border-amber-300',
                                                        'completed' => 'bg-blue-50 text-blue-900 border-blue-300',
                                                        'cancelled' => 'bg-rose-50 text-rose-800 border-rose-200 line-through opacity-70',
                                                        default     => 'bg-slate-100 text-slate-800 border-slate-300',
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
                                                    class="mb-1 rounded-lg border p-1.5 text-[11px] font-bold cursor-pointer transition shadow-2xs hover:shadow-xs {{ $cardStyle }}"
                                                >
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="truncate leading-tight">{{ $matchedBooking->layanan->namalayanan ?? 'Layanan' }}</span>
                                                        <span class="text-[9px] uppercase px-1 py-0.2 rounded bg-white/70">{{ $badgeStatus }}</span>
                                                    </div>
                                                    <p class="text-[10px] font-medium opacity-85 truncate mt-0.5">{{ $matchedBooking->namapelanggan }} ({{ substr($matchedBooking->jam, 0, 5) }})</p>
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
                                                        class="h-full rounded-lg border border-dashed border-indigo-300 bg-indigo-50/60 p-1.5 text-[10px] text-indigo-950 font-semibold cursor-pointer transition hover:bg-indigo-100/70"
                                                    >
                                                        <div class="flex items-center justify-between">
                                                            <span class="truncate font-bold text-indigo-800">{{ $sched->layanan->namalayanan ?? 'Layanan' }}</span>
                                                            <span class="rounded bg-indigo-200 px-1 py-0.2 text-[8px] font-extrabold uppercase text-indigo-900">Tersedia</span>
                                                        </div>
                                                        <p class="text-[9px] text-indigo-700 opacity-80 mt-0.5">{{ substr($sched->jam_mulai, 0, 5) }} - {{ substr($sched->jam_selesai, 0, 5) }}</p>
                                                    </div>
                                                @elseif ($schedStatus === \App\Models\Schedule::STATUS_BLOCKED)
                                                    <div class="h-full rounded-md border border-slate-200 bg-slate-100/70 p-1 text-[9px] text-slate-500 flex items-center justify-center font-medium">
                                                        [Diblokir]
                                                    </div>
                                                @else
                                                    <div class="h-full rounded-md border border-slate-100 bg-slate-50/50 p-1 text-[9px] text-slate-400 flex items-center justify-center">
                                                        [Nonaktif]
                                                    </div>
                                                @endif
                                            @endforeach
                                        @elseif ($isDayBlocked)
                                            <div class="h-full rounded-md bg-slate-100/40 border border-slate-200/50 flex items-center justify-center text-[10px] text-slate-400 font-medium">
                                                Libur
                                            </div>
                                        @else
                                            <div class="h-full rounded-md border border-dashed border-transparent hover:border-slate-300 flex items-center justify-center text-[10px] text-slate-300">
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

        <div class="rounded-2xl border border-bq-border bg-white shadow-2xs p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-bq-text">Jadwal Harian &mdash; {{ $currentDate->translatedFormat('l, d F Y') }}</h2>
                    <p class="text-xs text-bq-text-muted mt-0.5">{{ $dayBookings->count() }} Booking &bull; {{ $daySchedules->count() }} Total Slot Schedule</p>
                </div>
                @if ($isBlockedToday)
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700">
                        <span>⚠️ Tanggal Diblokir: {{ $blockedDates[$targetDateStr] ?: 'Libur Operasional' }}</span>
                    </span>
                @endif
            </div>

            {{-- 1. Transaksi Bookings Pada Hari Ini --}}
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Booking Aktif &amp; Riwayat Hari Ini</h3>
                @if ($dayBookings->isNotEmpty())
                    <div class="divide-y divide-slate-100 rounded-xl border border-bq-border bg-white overflow-hidden">
                        @foreach ($dayBookings as $item)
                            @php
                                $badgeClass = match($item->status) {
                                    'paid'      => 'bg-emerald-100 text-emerald-800',
                                    'pending'   => 'bg-amber-100 text-amber-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                    default     => 'bg-slate-100 text-slate-800',
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
                                class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50 transition cursor-pointer"
                            >
                                <div class="flex items-center gap-3.5">
                                    <span class="flex h-11 w-16 shrink-0 items-center justify-center rounded-xl bg-[#EEF2FF] text-xs font-black text-[#4F46E5]">
                                        {{ substr($item->jam, 0, 5) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">{{ $item->layanan->namalayanan ?? 'Layanan' }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $item->namapelanggan }} &bull; {{ $item->nomorhp }} &bull; {{ $item->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-900">
                                        Rp {{ number_format($item->payment->jumlah ?? $item->layanan->harga ?? 0, 0, ',', '.') }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase {{ $badgeClass }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
                        Tidak ada booking transaksi pada tanggal ini.
                    </div>
                @endif
            </div>

            {{-- 2. Ketersediaan Slot Schedule Pada Hari Ini --}}
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Slot Schedule Operasional Hari Ini</h3>
                @if ($daySchedules->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($daySchedules as $sched)
                            @php
                                $avail = $sched->getAvailabilityStatus();
                                $hasBooking = $sched->bookings->whereIn('status', ['paid', 'pending', 'completed'])->first();
                            @endphp
                            <div class="rounded-xl border p-3.5 {{ $avail === \App\Models\Schedule::STATUS_AVAILABLE ? 'border-indigo-200 bg-indigo-50/40' : 'border-bq-border bg-white opacity-85' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#4F46E5]">
                                        {{ substr($sched->jam_mulai, 0, 5) }} - {{ substr($sched->jam_selesai, 0, 5) }}
                                    </span>
                                    @if ($avail === \App\Models\Schedule::STATUS_AVAILABLE)
                                        <span class="rounded bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-800 uppercase">Tersedia</span>
                                    @elseif ($avail === \App\Models\Schedule::STATUS_BOOKED)
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 uppercase">Booked</span>
                                    @elseif ($avail === \App\Models\Schedule::STATUS_BLOCKED)
                                        <span class="rounded bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700 uppercase">Diblokir</span>
                                    @else
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 uppercase">Unavailable</span>
                                    @endif
                                </div>
                                <p class="text-sm font-bold text-slate-900 mt-1">{{ $sched->layanan->namalayanan ?? 'Layanan' }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    @if ($hasBooking)
                                        Dipesan oleh: <span class="font-medium text-slate-700">{{ $hasBooking->namapelanggan }}</span>
                                    @else
                                        Tarif: Rp {{ number_format($sched->harga_override ?? $sched->layanan->harga ?? 0, 0, ',', '.') }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
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
            // dayOfWeek: 0 = Sun, 1 = Mon, ..., 6 = Sat
            // Let's normalize Monday as first column (0 = Mon .. 6 = Sun)
            $dayOfWeekIso = $startOfMonth->dayOfWeekIso; // 1 = Monday .. 7 = Sunday
            $startDayOffset = $dayOfWeekIso - 1; // 0 for Monday
            $totalCells = (int) ceil(($startDayOffset + $daysInMonth) / 7) * 7;
        @endphp

        <div class="rounded-2xl border border-bq-border bg-white shadow-2xs p-5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h2 class="text-base font-bold text-bq-text">{{ $currentDate->translatedFormat('F Y') }}</h2>
                <span class="text-xs text-slate-500">Klik tanggal untuk melihat jadwal harian lengkap</span>
            </div>

            <div class="grid grid-cols-7 gap-px rounded-xl border border-bq-border bg-slate-200 overflow-hidden text-center text-xs">
                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $dayName)
                    <div class="bg-slate-50 py-2.5 font-bold text-slate-700">{{ $dayName }}</div>
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

                    <div class="bg-white min-h-[90px] p-2 text-left relative transition hover:bg-slate-50 {{ !$isValidDay ? 'bg-slate-50/50' : '' }}">
                        @if ($isValidDay)
                            <a
                                href="{{ route('owner.calendar', ['view' => 'day', 'date' => $cellDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                                class="block h-full cursor-pointer group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold {{ $isToday ? 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#4F46E5] text-white' : 'text-slate-700 group-hover:text-[#4F46E5]' }}">
                                        {{ $dayNum }}
                                    </span>
                                    @if ($isBlocked)
                                        <span class="rounded bg-slate-200 px-1 text-[8px] font-bold text-slate-700">Libur</span>
                                    @endif
                                </div>

                                <div class="mt-1.5 space-y-1">
                                    @if ($confirmedCount > 0)
                                        <span class="block truncate rounded bg-emerald-50 px-1 py-0.5 text-[10px] font-bold text-emerald-800">
                                            {{ $confirmedCount }} Confirmed
                                        </span>
                                    @endif
                                    @if ($pendingCount > 0)
                                        <span class="block truncate rounded bg-amber-50 px-1 py-0.5 text-[10px] font-bold text-amber-800">
                                            {{ $pendingCount }} Pending
                                        </span>
                                    @endif
                                    @if ($availCount > 0)
                                        <span class="block truncate rounded bg-indigo-50 px-1 py-0.5 text-[10px] font-medium text-indigo-800">
                                            {{ $availCount }} Slot Tersedia
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

    {{-- Detail Modal Drawer (All 10 required fields) --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
        @click.self="modalOpen = false"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-bq-border space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-bq-text">Detail Booking Calendar</h3>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono font-bold text-slate-700" x-text="selectedSlot ? selectedSlot.booking_id : ''"></span>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedSlot">
                <div class="space-y-3.5 text-sm">
                    {{-- 1. Service & Amount --}}
                    <div class="rounded-xl bg-[#F8FAFC] p-3.5 border border-bq-border flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Layanan / Service</p>
                            <p class="text-base font-extrabold text-slate-900 mt-0.5" x-text="selectedSlot.service"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Amount / Tarif</p>
                            <p class="text-base font-extrabold text-[#4F46E5] mt-0.5" x-text="selectedSlot.amount"></p>
                        </div>
                    </div>

                    {{-- 2. Date & Time --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-bq-border p-3">
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Tanggal / Date</p>
                            <p class="font-bold text-slate-900 mt-0.5" x-text="selectedSlot.date"></p>
                        </div>
                        <div class="rounded-xl border border-bq-border p-3">
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Waktu / Time</p>
                            <p class="font-bold text-[#4F46E5] mt-0.5" x-text="selectedSlot.time"></p>
                        </div>
                    </div>

                    {{-- 3. Customer Info --}}
                    <div class="rounded-xl border border-bq-border p-3 space-y-1">
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Informasi Pelanggan</p>
                        <p class="font-bold text-slate-900 text-sm" x-text="selectedSlot.customer"></p>
                        <div class="flex flex-wrap items-center gap-3 pt-1 text-xs text-slate-600">
                            <span>📞 <span x-text="selectedSlot.phone"></span></span>
                            <span>✉️ <span x-text="selectedSlot.email"></span></span>
                        </div>
                    </div>

                    {{-- 4. Status Separation: Booking Status & Payment Status --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-bq-border p-3">
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Booking Status</p>
                            <div class="mt-1">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-extrabold uppercase"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': selectedSlot.booking_status === 'Confirmed',
                                        'bg-amber-100 text-amber-800': selectedSlot.booking_status === 'Pending',
                                        'bg-blue-100 text-blue-800': selectedSlot.booking_status === 'Completed',
                                        'bg-rose-100 text-rose-800': selectedSlot.booking_status === 'Cancelled',
                                        'bg-indigo-100 text-indigo-800': selectedSlot.booking_status === 'Available',
                                        'bg-slate-100 text-slate-800': !['Confirmed', 'Pending', 'Completed', 'Cancelled', 'Available'].includes(selectedSlot.booking_status)
                                    }"
                                    x-text="selectedSlot.booking_status"
                                ></span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-bq-border p-3">
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Payment Status</p>
                            <div class="mt-1">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-extrabold uppercase"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': selectedSlot.payment_status.toLowerCase() === 'sukses',
                                        'bg-amber-100 text-amber-800': selectedSlot.payment_status.toLowerCase() === 'pending',
                                        'bg-rose-100 text-rose-800': ['gagal', 'failed', 'expired'].includes(selectedSlot.payment_status.toLowerCase()),
                                        'bg-slate-100 text-slate-700': !['sukses', 'pending', 'gagal', 'failed', 'expired'].includes(selectedSlot.payment_status.toLowerCase())
                                    }"
                                    x-text="selectedSlot.payment_status"
                                ></span>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Notes --}}
                    <div class="rounded-xl border border-bq-border p-3">
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Catatan / Notes</p>
                        <p class="text-xs text-slate-700 mt-1 italic" x-text="selectedSlot.notes"></p>
                    </div>
                </div>
            </template>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="modalOpen = false"
                    class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                >
                    Tutup
                </button>
                <a
                    href="{{ route('owner.bookings') }}"
                    class="rounded-xl bg-[#4F46E5] px-4 py-2 text-xs font-bold text-white hover:bg-[#4338CA] transition cursor-pointer"
                >
                    Lihat di Daftar Booking
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function updateCalendarFilter(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, val);
    return url.toString();
}
</script>
@endsection
