@extends('layouts.owner-layout')

@section('title', 'Calendar')

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        viewMode: 'week', // 'day', 'week', 'month'
        selectedService: 'all',
        selectedStatus: 'all',
        activeDate: new Date(),
        selectedSlot: null,
        modalOpen: false,

        formatDate(d) {
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },
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
            <p class="mt-1 text-sm text-bq-text-muted">Visualisasi seluruh aktivitas pemesanan, ketersediaan slot, dan operasional layanan.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('owner.schedule') }}" class="inline-flex items-center gap-2 rounded-xl border border-bq-border bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-bq-text hover:bg-bq-surface transition-all shadow-2xs">
                <svg class="h-4 w-4 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Konfigurasi Jam Buka</span>
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
                <button
                    type="button"
                    @click="viewMode = 'day'"
                    :class="viewMode === 'day' ? 'bg-white text-[#4F46E5] font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer"
                >
                    Day
                </button>
                <button
                    type="button"
                    @click="viewMode = 'week'"
                    :class="viewMode === 'week' ? 'bg-white text-[#4F46E5] font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer"
                >
                    Week
                </button>
                <button
                    type="button"
                    @click="viewMode = 'month'"
                    :class="viewMode === 'month' ? 'bg-white text-[#4F46E5] font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer"
                >
                    Month
                </button>
            </div>

            {{-- Date Navigation --}}
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-bq-border bg-white px-3 py-1.5 text-xs font-bold text-bq-text hover:bg-slate-50 transition cursor-pointer"
                    @click="activeDate = new Date()"
                >
                    Hari Ini
                </button>
                <div class="flex items-center rounded-xl border border-bq-border bg-white shadow-2xs">
                    <button
                        type="button"
                        class="p-2 text-slate-500 hover:text-slate-800 transition cursor-pointer border-r border-bq-border"
                        aria-label="Previous"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <span class="px-4 py-1.5 text-xs font-bold text-bq-text min-w-[160px] text-center">
                        {{ now()->translatedFormat('d F Y') }} (Minggu Ini)
                    </span>
                    <button
                        type="button"
                        class="p-2 text-slate-500 hover:text-slate-800 transition cursor-pointer border-l border-bq-border"
                        aria-label="Next"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Service & Status Filters --}}
            <div class="flex flex-wrap items-center gap-2">
                <select
                    x-model="selectedService"
                    class="rounded-xl border border-bq-border bg-white px-3 py-1.5 text-xs font-medium text-slate-700 focus:border-[#4F46E5] focus:outline-none"
                >
                    <option value="all">Semua Layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->namalayanan }}</option>
                    @endforeach
                </select>

                <select
                    x-model="selectedStatus"
                    class="rounded-xl border border-bq-border bg-white px-3 py-1.5 text-xs font-medium text-slate-700 focus:border-[#4F46E5] focus:outline-none"
                >
                    <option value="all">Semua Status</option>
                    <option value="paid">Confirmed / Paid</option>
                    <option value="pending">Pending</option>
                    <option value="cancelled">Cancelled</option>
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
                <span>Menunggu Bayar</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-indigo-200"></span>
                <span>Slot Tersedia</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                <span>Dibatalkan</span>
            </div>
        </div>
    </div>

    {{-- Week View (Default) --}}
    <div x-show="viewMode === 'week'" class="rounded-2xl border border-bq-border bg-white shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[760px]">
                {{-- Week Days Header --}}
                @php
                    $daysOfWeek = [
                        ['day' => 'Senin', 'date' => now()->startOfWeek()->format('d M')],
                        ['day' => 'Selasa', 'date' => now()->startOfWeek()->addDays(1)->format('d M')],
                        ['day' => 'Rabu', 'date' => now()->startOfWeek()->addDays(2)->format('d M')],
                        ['day' => 'Kamis', 'date' => now()->startOfWeek()->addDays(3)->format('d M')],
                        ['day' => 'Jumat', 'date' => now()->startOfWeek()->addDays(4)->format('d M')],
                        ['day' => 'Sabtu', 'date' => now()->startOfWeek()->addDays(5)->format('d M')],
                        ['day' => 'Minggu', 'date' => now()->startOfWeek()->addDays(6)->format('d M')],
                    ];
                @endphp
                <div class="grid grid-cols-8 border-b border-bq-border bg-slate-50/80 text-center text-xs font-bold text-slate-700">
                    <div class="py-3 px-2 text-slate-400 border-r border-bq-border">Waktu</div>
                    @foreach ($daysOfWeek as $d)
                        <div class="py-3 px-2 border-r last:border-r-0 border-bq-border {{ $loop->first ? 'bg-[#EEF2FF]/40 text-[#4F46E5]' : '' }}">
                            <p class="uppercase tracking-wider text-[11px]">{{ $d['day'] }}</p>
                            <p class="text-xs font-extrabold text-slate-900 mt-0.5">{{ $d['date'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Time Slots Rows --}}
                @php
                    $hours = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00', '19:00', '20:00'];
                @endphp
                <div class="divide-y divide-slate-100">
                    @foreach ($hours as $hour)
                        <div class="grid grid-cols-8 min-h-[58px]">
                            <div class="py-2.5 px-3 text-right text-[11px] font-semibold text-slate-400 border-r border-bq-border bg-slate-50/40">
                                {{ $hour }}
                            </div>

                            @foreach (range(0, 6) as $dayIdx)
                                @php
                                    // Match any booking for this day of current week and hour
                                    $matchedBooking = $bookings->first(function ($b) use ($dayIdx, $hour) {
                                        $tgl = \Carbon\Carbon::parse($b->tanggalbooking);
                                        $targetDate = now()->startOfWeek()->addDays($dayIdx)->toDateString();
                                        return $tgl->toDateString() === $targetDate && str_starts_with($b->jam, substr($hour, 0, 2));
                                    });
                                @endphp
                                <div class="p-1 border-r last:border-r-0 border-bq-border transition hover:bg-slate-50/70">
                                    @if ($matchedBooking)
                                        <div
                                            @click="openDetail({
                                                id: '{{ $matchedBooking->id }}',
                                                customer: '{{ addslashes($matchedBooking->namapelanggan) }}',
                                                phone: '{{ $matchedBooking->nomorhp }}',
                                                email: '{{ $matchedBooking->email }}',
                                                service: '{{ addslashes($matchedBooking->layanan->namalayanan ?? 'Layanan') }}',
                                                time: '{{ $matchedBooking->jam }}',
                                                date: '{{ $matchedBooking->tanggalbooking }}',
                                                status: '{{ $matchedBooking->status }}'
                                            })"
                                            class="h-full rounded-lg p-1.5 text-[11px] font-bold cursor-pointer transition shadow-2xs {{ $matchedBooking->status === 'paid' ? 'bg-emerald-100/80 text-emerald-900 border border-emerald-300' : ($matchedBooking->status === 'cancelled' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-amber-100/80 text-amber-900 border border-amber-300') }}"
                                        >
                                            <p class="truncate leading-tight">{{ $matchedBooking->layanan->namalayanan ?? 'Layanan' }}</p>
                                            <p class="text-[10px] font-medium opacity-80 truncate">{{ $matchedBooking->namapelanggan }}</p>
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

    {{-- Day View --}}
    @php
        $todayBookings = $bookings->filter(fn($b) => \Carbon\Carbon::parse($b->tanggalbooking)->isToday());
    @endphp
    <div x-show="viewMode === 'day'" x-cloak class="rounded-2xl border border-bq-border bg-white shadow-2xs p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-bq-text">Jadwal Harian — {{ now()->translatedFormat('l, d F Y') }}</h2>
            <span class="text-xs font-semibold text-slate-500">{{ $todayBookings->count() }} Sesi Terjadwal</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($todayBookings as $item)
                <div
                    @click="openDetail({
                        id: '{{ $item->id }}',
                        customer: '{{ addslashes($item->namapelanggan) }}',
                        phone: '{{ $item->nomorhp }}',
                        email: '{{ $item->email }}',
                        service: '{{ addslashes($item->layanan->namalayanan ?? 'Layanan') }}',
                        time: '{{ $item->jam }}',
                        date: '{{ $item->tanggalbooking }}',
                        status: '{{ $item->status }}'
                    })"
                    class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 hover:bg-slate-50/60 rounded-xl px-2 transition cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-16 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-xs font-extrabold text-[#4F46E5]">
                            {{ substr($item->jam, 0, 5) }}
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $item->layanan->namalayanan ?? 'Layanan' }}</p>
                            <p class="text-xs text-slate-500">{{ $item->namapelanggan }} &bull; {{ $item->nomorhp }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $badgeClass = match($item->status) {
                                'paid'      => 'bg-emerald-100 text-emerald-800',
                                'pending'   => 'bg-amber-100 text-amber-800',
                                'completed' => 'bg-indigo-100 text-indigo-800',
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
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase {{ $badgeClass }}">
                            {{ $badgeLabel }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-slate-400 text-sm">
                    Belum ada jadwal booking hari ini.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Month View --}}
    @php
        $startOfMonth = now()->startOfMonth();
        $daysInMonth = now()->daysInMonth;
        // Carbon dayOfWeek: 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $startDayOffset = $startOfMonth->dayOfWeek;
        $totalCells = (int) ceil(($startDayOffset + $daysInMonth) / 7) * 7;
    @endphp
    <div x-show="viewMode === 'month'" x-cloak class="rounded-2xl border border-bq-border bg-white shadow-2xs p-5">
        <div class="grid grid-cols-7 gap-px rounded-xl border border-bq-border bg-slate-200 overflow-hidden text-center text-xs">
            @foreach (['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $dayName)
                <div class="bg-slate-50 py-2.5 font-bold text-slate-700">{{ $dayName }}</div>
            @endforeach
            @foreach (range(0, $totalCells - 1) as $cell)
                @php
                    $dayNum = $cell - $startDayOffset + 1;
                    $isValidDay = ($dayNum >= 1 && $dayNum <= $daysInMonth);
                    $dayBookings = $isValidDay ? ($monthBookings->get($dayNum) ?? collect()) : collect();
                @endphp
                <div class="bg-white min-h-[75px] p-2 text-left relative transition hover:bg-slate-50 {{ !$isValidDay ? 'bg-slate-50/50' : '' }}">
                    @if ($isValidDay)
                        <span class="text-xs font-bold {{ $dayNum === (int)now()->format('j') ? 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#4F46E5] text-white' : 'text-slate-700' }}">
                            {{ $dayNum }}
                        </span>
                        @if ($dayBookings->isNotEmpty())
                            <div class="mt-1 space-y-1">
                                <span class="block truncate rounded bg-indigo-50 px-1 py-0.5 text-[10px] font-bold text-[#4F46E5]">
                                    {{ $dayBookings->count() }} Booking
                                </span>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Detail Modal Drawer --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
        @click.self="modalOpen = false"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-bq-border space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-bq-text">Detail Booking Calendar</h3>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedSlot">
                <div class="space-y-3 text-sm">
                    <div class="rounded-xl bg-[#F8FAFC] p-3 border border-bq-border">
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Layanan</p>
                        <p class="text-base font-bold text-slate-900 mt-0.5" x-text="selectedSlot.service"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-bq-border p-3">
                            <p class="text-xs text-slate-500">Pelanggan</p>
                            <p class="font-bold text-slate-900 mt-0.5 truncate" x-text="selectedSlot.customer"></p>
                        </div>
                        <div class="rounded-xl border border-bq-border p-3">
                            <p class="text-xs text-slate-500">Waktu</p>
                            <p class="font-bold text-[#4F46E5] mt-0.5" x-text="selectedSlot.time"></p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-bq-border p-3">
                        <p class="text-xs text-slate-500">Kontak</p>
                        <p class="font-medium text-slate-800" x-text="selectedSlot.phone"></p>
                        <p class="text-xs text-slate-500" x-text="selectedSlot.email"></p>
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-xs text-slate-500">Status Sesi:</span>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase"
                            :class="{
                                'bg-emerald-100 text-emerald-800': selectedSlot.status === 'paid',
                                'bg-amber-100 text-amber-800': selectedSlot.status === 'pending',
                                'bg-indigo-100 text-indigo-800': selectedSlot.status === 'completed',
                                'bg-rose-100 text-rose-800': selectedSlot.status === 'cancelled'
                            }"
                            x-text="selectedSlot.status === 'paid' ? 'Confirmed' : (selectedSlot.status === 'pending' ? 'Pending' : (selectedSlot.status === 'completed' ? 'Completed' : (selectedSlot.status === 'cancelled' ? 'Cancelled' : selectedSlot.status)))"
                        ></span>
                    </div>
                </div>
            </template>

            <div class="pt-2">
                <button
                    type="button"
                    @click="modalOpen = false"
                    class="w-full rounded-xl bg-slate-100 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
