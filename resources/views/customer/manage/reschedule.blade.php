<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ubah Jadwal (Reschedule) — {{ $booking->booking_code }} | {{ $booking->tenant?->namabisnis ?? 'BookQu' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/booking-manage.css') }}" />
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased min-h-screen flex flex-col" x-data="rescheduleManager()" x-init="init()" @pageshow.window="isSubmitting = false" @pagehide.window="isSubmitting = false" @popstate.window="isSubmitting = false">

    {{-- ── Elevated Header ── --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 sm:px-6 py-3.5">
            <a href="{{ $booking->tenant?->slug ? url('/' . $booking->tenant->slug) : '/' }}" class="flex items-center gap-2.5 group">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                    {{ strtoupper(substr($booking->tenant?->namabisnis ?? 'B', 0, 1)) }}
                </div>
                <div>
                    <span class="block text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $booking->tenant?->namabisnis ?? 'Merchant' }}</span>
                    <span class="block text-[11px] font-medium text-slate-500">Ubah Jadwal Booking</span>
                </div>
            </a>
            <a
                href="{{ route('booking.manage', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-all cursor-pointer"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Kembali ke Detail</span>
            </a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl px-4 sm:px-6 py-6 sm:py-8 space-y-6 flex-grow">

        {{-- ── Page Title & Context ── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Pengubahan Jadwal Reservasi</span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">Pilih Tanggal &amp; Jam Baru</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Booking <span class="font-mono font-bold text-slate-800">{{ $booking->booking_code }}</span> • {{ $booking->layanan->namalayanan ?? 'Layanan' }}
                </p>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200/80">
                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Bebas Biaya Tambahan
                </span>
            </div>
        </div>

        {{-- ── Error Notification ── --}}
        @if($errors->has('reschedule'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs sm:text-sm text-rose-800 flex items-center gap-3">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">{{ $errors->first('reschedule') }}</div>
            </div>
        @endif

        {{-- ── VISUAL SCHEDULE COMPARISON BANNER (SEBELUM VS SESUDAH) ── --}}
        <div class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2 items-center">
                {{-- Jadwal Lama --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50/90 p-4">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Jadwal Saat Ini</span>
                        <span class="rounded-md bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">Akan Diganti</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-200/80 text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="3"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-sm sm:text-base">
                                {{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') }}
                            </p>
                            <p class="text-xs sm:text-sm font-semibold text-slate-500">
                                Pukul {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Jadwal Baru Terpilih --}}
                <div
                    class="rounded-2xl border p-4 transition-all duration-200"
                    :class="selectedScheduleId
                        ? 'border-emerald-300 bg-emerald-50/80 shadow-xs'
                        : 'border-dashed border-slate-200 bg-slate-50/50'"
                >
                    <div class="flex items-center justify-between mb-1.5">
                        <span
                            class="text-xs font-bold uppercase tracking-wider"
                            :class="selectedScheduleId ? 'text-emerald-700' : 'text-slate-400'"
                        >
                            Jadwal Baru Terpilih
                        </span>
                        <template x-if="selectedScheduleId">
                            <span class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Siap Dikonfirmasi
                            </span>
                        </template>
                    </div>

                    <template x-if="selectedScheduleId">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="3"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 text-sm sm:text-base" x-text="selectedDateLabel"></p>
                                <p class="text-xs sm:text-sm font-bold text-emerald-700" x-text="'Pukul ' + selectedSlotTime + ' WIB'"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedScheduleId">
                        <div class="flex items-center gap-2.5 py-1 text-slate-400">
                            <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v4l3 3"/>
                            </svg>
                            <span class="text-xs sm:text-sm italic">Pilih tanggal dan jam kunjungan di bawah</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── TWO COLUMN: PICKER & CONFIRMATION SIDEBAR ── --}}
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">

            {{-- ── LEFT: DATE & TIME SELECTOR ── --}}
            <div class="space-y-6">

                {{-- Modern Calendar Card --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                    {{-- Month Navigation Header --}}
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">1. Pilih Tanggal Baru</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Pilih tanggal yang memiliki ketersediaan slot</p>
                        </div>
                        <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-2xl border border-slate-200/80">
                            <button
                                type="button"
                                @click="prevMonth()"
                                class="p-1.5 rounded-xl hover:bg-white text-slate-600 hover:text-slate-900 hover:shadow-2xs active:scale-95 transition-all cursor-pointer"
                                title="Bulan Sebelumnya"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <span class="text-xs sm:text-sm font-bold text-slate-900 px-3 min-w-[130px] text-center select-none" x-text="currentMonthLabel"></span>
                            <button
                                type="button"
                                @click="nextMonth()"
                                class="p-1.5 rounded-xl hover:bg-white text-slate-600 hover:text-slate-900 hover:shadow-2xs active:scale-95 transition-all cursor-pointer"
                                title="Bulan Berikutnya"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Day Headers --}}
                    <div class="grid grid-cols-7 gap-1.5 mb-2">
                        <template x-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']">
                            <div class="text-center text-[11px] font-bold uppercase tracking-wider text-slate-400 py-1" x-text="day"></div>
                        </template>
                    </div>

                    {{-- Calendar Days Grid --}}
                    <div class="grid grid-cols-7 gap-1.5">
                        <template x-for="(cell, idx) in calendarCells" :key="idx">
                            <div>
                                <template x-if="!cell.day">
                                    <div class="h-10 sm:h-12 w-full rounded-2xl"></div>
                                </template>
                                <template x-if="cell.day">
                                    <button
                                        type="button"
                                        class="h-10 sm:h-12 w-full rounded-2xl flex flex-col items-center justify-center text-xs font-bold transition-all relative cursor-pointer"
                                        :class="{
                                            'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 scale-[1.02]': cell.selected,
                                            'bg-emerald-50/80 text-emerald-900 border border-emerald-200/80 hover:bg-emerald-100 hover:border-emerald-300': !cell.selected && !cell.disabled && cell.hasSlots,
                                            'ring-2 ring-indigo-400/50': cell.isToday && !cell.selected,
                                            'opacity-30 cursor-not-allowed bg-slate-50 text-slate-400 border border-transparent': cell.disabled,
                                        }"
                                        :disabled="cell.disabled"
                                        @click="selectDate(cell.dateStr)"
                                        :title="cell.hasSlots ? (cell.availableSlots + ' slot tersedia') : 'Tidak tersedia'"
                                    >
                                        <span x-text="cell.day"></span>
                                        <template x-if="cell.hasSlots && !cell.selected">
                                            <span class="h-1 w-1 rounded-full bg-emerald-500 mt-0.5"></span>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Legend --}}
                    <div class="flex flex-wrap items-center gap-4 mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-emerald-100 border border-emerald-300"></span>
                            <span>Tersedia</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-indigo-600"></span>
                            <span>Terpilih</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-slate-100 border border-slate-200"></span>
                            <span>Tidak Tersedia / Libur</span>
                        </div>
                    </div>
                </div>

                {{-- Time Slots Card --}}
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all"
                    x-show="selectedDate"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5 pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">2. Pilih Jam Sesi</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Durasi sesi: {{ $booking->layanan->durasi ?? 60 }} menit</p>
                        </div>
                        <div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 border border-indigo-100" x-text="selectedDateLabel"></span>
                        </div>
                    </div>

                    {{-- Loading Slots Spinner --}}
                    <div x-show="loadingSlots" class="flex flex-col items-center justify-center py-10 gap-3 text-slate-500">
                        <div class="spinner"></div>
                        <span class="text-xs font-medium">Mengecek ketersediaan jam...</span>
                    </div>

                    {{-- Available Time Slot Grid --}}
                    <div x-show="!loadingSlots && timeSlots.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                        <template x-for="slot in timeSlots" :key="slot.id">
                            <button
                                type="button"
                                class="flex flex-col items-center justify-center p-3 rounded-2xl border text-xs transition-all cursor-pointer select-none"
                                :class="{
                                    'border-indigo-600 bg-indigo-50/80 ring-2 ring-indigo-600 text-indigo-950 font-bold shadow-xs': selectedScheduleId === slot.id,
                                    'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 text-slate-800 font-semibold': selectedScheduleId !== slot.id && !slot.is_booked && !slot.is_past,
                                    'opacity-40 cursor-not-allowed bg-slate-50 text-slate-400 border-slate-100': slot.is_booked || slot.is_past
                                }"
                                :disabled="slot.is_booked || slot.is_past"
                                @click="selectSlot(slot)"
                            >
                                <span class="text-sm" x-text="slot.jam_mulai"></span>
                                <span class="text-[10px] text-slate-400 mt-0.5">WIB</span>
                            </button>
                        </template>
                    </div>

                    {{-- Empty Slots State --}}
                    <div x-show="!loadingSlots && timeSlots.length === 0" class="text-center py-10 rounded-2xl bg-slate-50 border border-dashed border-slate-200">
                        <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
                        </svg>
                        <p class="text-xs sm:text-sm font-bold text-slate-700">Tidak ada slot waktu tersedia di tanggal ini</p>
                        <p class="text-xs text-slate-400 mt-1">Silakan pilih tanggal lain pada kalender di atas.</p>
                    </div>
                </div>

            </div>

            {{-- ── RIGHT: SUMMARY & SUBMISSION SIDEBAR ── --}}
            <div class="lg:sticky lg:top-20 space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">
                        Ringkasan Reschedule
                    </h3>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Layanan</span>
                            <span class="font-bold text-slate-900 text-right">{{ $booking->layanan->namalayanan ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Biaya Pengubahan</span>
                            <span class="font-bold text-emerald-600">Gratis (Rp 0)</span>
                        </div>
                    </div>

                    {{-- Highlight Box --}}
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 space-y-2 text-xs">
                        <div class="flex justify-between items-start">
                            <span class="text-slate-500">Jadwal Baru:</span>
                            <template x-if="selectedScheduleId">
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 block" x-text="selectedDateLabel"></span>
                                    <span class="font-bold text-indigo-600" x-text="'Pukul ' + selectedSlotTime + ' WIB'"></span>
                                </div>
                            </template>
                            <template x-if="!selectedScheduleId">
                                <span class="text-slate-400 italic">Belum dipilih</span>
                            </template>
                        </div>
                    </div>

                    {{-- Error Message Box --}}
                    <div x-show="errorMsg" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span x-text="errorMsg"></span>
                    </div>

                    {{-- Submit Form --}}
                    <form
                        method="POST"
                        action="{{ route('booking.manage.reschedule.store', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                        @submit.prevent="submitReschedule"
                        x-ref="rescheduleForm"
                        class="pt-2"
                    >
                        @csrf
                        <input type="hidden" name="tanggal" x-model="selectedDate" />
                        <input type="hidden" name="schedule_id" x-model="selectedScheduleId" />

                        <button
                            type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3.5 text-xs sm:text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition-all hover:bg-indigo-700 active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            :disabled="!selectedScheduleId || isSubmitting"
                        >
                            <span x-show="!isSubmitting" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Konfirmasi Jadwal Baru</span>
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-2" x-cloak>
                                <span class="spinner"></span> Memproses...
                            </span>
                        </button>
                    </form>

                    <p class="text-[11px] text-slate-400 text-center leading-relaxed">
                        Tiket dan konfirmasi jadwal baru akan langsung dikirimkan ke email <strong>{{ $booking->email }}</strong>.
                    </p>
                </div>
            </div>
        </div>

    </main>

    {{-- ── Minimalist Clean Footer ── --}}
    <footer class="border-t border-slate-200/80 bg-white py-6 mt-12 text-center text-xs text-slate-500">
        <div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-3 px-4 sm:px-6">
            <p>&copy; {{ date('Y') }} {{ $booking->tenant->namabisnis }}. Hak cipta dilindungi.</p>
            <div class="flex items-center gap-2 text-slate-400 text-xs">
                <span>Didukung oleh <strong class="text-slate-700">BookQu</strong></span>
            </div>
        </div>
    </footer>

    <script>
        const AVAILABILITY_DATA  = @json($availabilityPayload);
        const MIN_DATE           = '{{ $minDate }}';
        const MAX_DATE           = '{{ $maxDate }}';
        const SLOTS_URL          = '{{ route('booking.manage.reschedule.slots', ['booking_code' => $booking->booking_code]) }}';
        const TOKEN              = '{{ $token }}';

        function rescheduleManager() {
            return {
                viewYear:           new Date().getFullYear(),
                viewMonth:          new Date().getMonth(),
                selectedDate:       '',
                selectedDateLabel:  '',
                selectedScheduleId: null,
                selectedSlotTime:   '',
                timeSlots:          [],
                loadingSlots:       false,
                isSubmitting:       false,
                errorMsg:           '',

                availabilityMap: Object.fromEntries(
                    AVAILABILITY_DATA.map(r => [r.date, r])
                ),

                init() {
                    const today = new Date();
                    this.viewYear  = today.getFullYear();
                    this.viewMonth = today.getMonth();
                },

                get currentMonthLabel() {
                    const months = ['Januari','Februari','Maret','April','Mei','Juni',
                                    'Juli','Agustus','September','Oktober','November','Desember'];
                    return months[this.viewMonth] + ' ' + this.viewYear;
                },

                get calendarCells() {
                    const cells     = [];
                    const firstDay  = new Date(this.viewYear, this.viewMonth, 1).getDay();
                    const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                    const todayStr  = new Date().toISOString().split('T')[0];

                    for (let i = 0; i < firstDay; i++) cells.push({ day: null });

                    for (let d = 1; d <= daysInMonth; d++) {
                        const y = this.viewYear;
                        const m = String(this.viewMonth + 1).padStart(2, '0');
                        const dd = String(d).padStart(2, '0');
                        const dateStr = `${y}-${m}-${dd}`;

                        const availability = this.availabilityMap[dateStr];
                        const hasSlots     = availability && availability.available_slots > 0;
                        const isPast       = dateStr < todayStr || dateStr < MIN_DATE || dateStr > MAX_DATE;

                        cells.push({
                            day:            d,
                            dateStr,
                            isToday:        dateStr === todayStr,
                            selected:       dateStr === this.selectedDate,
                            hasSlots,
                            availableSlots: availability ? availability.available_slots : 0,
                            disabled:       isPast || !hasSlots,
                        });
                    }

                    return cells;
                },

                prevMonth() {
                    if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; }
                    else { this.viewMonth--; }
                },

                nextMonth() {
                    if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; }
                    else { this.viewMonth++; }
                },

                async selectDate(dateStr) {
                    if (this.selectedDate === dateStr) return;
                    this.selectedDate       = dateStr;
                    this.selectedScheduleId = null;
                    this.selectedSlotTime   = '';
                    this.errorMsg           = '';

                    const months = ['Januari','Februari','Maret','April','Mei','Juni',
                                    'Juli','Agustus','September','Oktober','November','Desember'];
                    const d  = new Date(dateStr + 'T00:00:00');
                    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                    this.selectedDateLabel = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();

                    await this.fetchTimeSlots(dateStr);
                },

                async fetchTimeSlots(dateStr) {
                    this.loadingSlots = true;
                    this.timeSlots    = [];
                    try {
                        const res = await fetch(`${SLOTS_URL}?tanggal=${dateStr}&token=${TOKEN}`);
                        const data = await res.json();
                        this.timeSlots = data.slots || [];
                    } catch (e) {
                        this.errorMsg = 'Gagal memuat slot waktu. Silakan coba lagi.';
                    } finally {
                        this.loadingSlots = false;
                    }
                },

                selectSlot(slot) {
                    this.selectedScheduleId = slot.id;
                    this.selectedSlotTime   = slot.jam_mulai;
                    this.errorMsg           = '';
                },

                submitReschedule() {
                    if (!this.selectedDate || !this.selectedScheduleId) {
                        this.errorMsg = 'Silakan pilih tanggal dan jam terlebih dahulu.';
                        return;
                    }
                    this.isSubmitting = true;
                    setTimeout(() => { this.isSubmitting = false; }, 1200);
                    this.$refs.rescheduleForm.submit();
                },
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
