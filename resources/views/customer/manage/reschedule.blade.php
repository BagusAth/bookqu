<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reschedule — {{ $booking->booking_code }} | {{ $booking->tenant->namabisnis }}</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/booking-manage.css') }}" />
</head>
<body class="manage-page" x-data="rescheduleManager()" x-init="init()">

    {{-- ── Header ── --}}
    <header class="manage-header">
        <nav class="manage-shell flex items-center justify-between px-6 py-4">
            <a href="/" class="flex items-center">
                <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
            </a>
            <a
                href="{{ route('booking.manage', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                class="text-sm font-semibold text-[#6B7280] hover:text-[#111827] transition flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Detail Booking
            </a>
        </nav>
    </header>

    <main class="manage-shell mx-auto px-6 py-8">

        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-widest text-[#6B7280]">RESCHEDULE BOOKING</p>
            <h1 class="mt-2 text-2xl font-bold text-[#111827] sm:text-3xl">Pilih Jadwal Baru</h1>
            <p class="mt-2 text-sm text-[#6B7280]">
                Booking <strong class="text-[#4F46E5]">{{ $booking->booking_code }}</strong> —
                {{ $booking->layanan->namalayanan ?? '' }}
            </p>
        </div>

        @if($errors->has('reschedule'))
        <div class="alert-error mb-6">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ $errors->first('reschedule') }}</span>
        </div>
        @endif

        {{-- ── Current Schedule Info ── --}}
        <div class="manage-card p-6 mb-6">
            <p class="section-title">Jadwal Saat Ini</p>
            <div class="mt-4 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FEF2F2] text-[#DC2626] flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="4"/><path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-[#111827]">
                        {{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') }}
                    </p>
                    <p class="text-sm text-[#6B7280]">
                        Pukul {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB
                    </p>
                </div>
                <div class="ml-auto">
                    <span class="status-badge status-badge--cancelled text-xs">Akan Diubah</span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">

            {{-- ── Left: Date + Time Picker ── --}}
            <div class="space-y-6">

                {{-- Calendar Card --}}
                <div class="manage-card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <p class="section-title mb-0">Pilih Tanggal Baru</p>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="prevMonth()" class="p-2 rounded-xl hover:bg-[#F3F4F6] transition text-[#6B7280]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <span class="text-sm font-bold text-[#111827] min-w-[120px] text-center" x-text="currentMonthLabel"></span>
                            <button type="button" @click="nextMonth()" class="p-2 rounded-xl hover:bg-[#F3F4F6] transition text-[#6B7280]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Day headers --}}
                    <div class="calendar-grid mb-2">
                        <template x-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']">
                            <div class="text-center text-xs font-bold text-[#9CA3AF] py-1" x-text="day"></div>
                        </template>
                    </div>

                    {{-- Calendar days --}}
                    <div class="calendar-grid">
                        <template x-for="(cell, idx) in calendarCells" :key="idx">
                            <button
                                type="button"
                                class="calendar-day"
                                :class="{
                                    'calendar-day--empty':     !cell.day,
                                    'calendar-day--disabled':  cell.day && cell.disabled,
                                    'calendar-day--available': cell.day && !cell.disabled && cell.hasSlots,
                                    'calendar-day--today':     cell.day && cell.isToday && !cell.selected,
                                    'calendar-day--selected':  cell.day && cell.selected,
                                }"
                                :disabled="!cell.day || cell.disabled"
                                @click="cell.day && !cell.disabled && selectDate(cell.dateStr)"
                                :title="cell.day ? (cell.disabled ? 'Tidak tersedia' : (cell.hasSlots ? cell.availableSlots + ' slot tersedia' : 'Tidak ada slot')) : ''"
                            >
                                <span x-text="cell.day || ''"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Legend --}}
                    <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t border-[#F3F4F6]">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-md bg-[#ECFDF5] border border-[#6EE7B7]"></span>
                            <span class="text-xs text-[#6B7280]">Tersedia</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-md bg-[#4F46E5]"></span>
                            <span class="text-xs text-[#6B7280]">Terpilih</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-md bg-[#F9FAFB] border border-[#E5E7EB]"></span>
                            <span class="text-xs text-[#6B7280]">Tidak tersedia</span>
                        </div>
                    </div>
                </div>

                {{-- Time Slots Card --}}
                <div class="manage-card p-6" x-show="selectedDate" x-cloak>
                    <div class="flex items-center justify-between mb-6">
                        <p class="section-title mb-0">Pilih Jam</p>
                        <span class="text-sm text-[#6B7280]" x-text="selectedDateLabel"></span>
                    </div>

                    {{-- Loading state --}}
                    <div x-show="loadingSlots" class="flex items-center justify-center py-8 gap-3 text-[#6B7280]">
                        <span class="spinner"></span>
                        <span class="text-sm">Memuat slot waktu...</span>
                    </div>

                    {{-- Time slots --}}
                    <div x-show="!loadingSlots && timeSlots.length > 0" class="time-slot-grid">
                        <template x-for="slot in timeSlots" :key="slot.id">
                            <button
                                type="button"
                                class="time-slot"
                                :class="{
                                    'time-slot--disabled': slot.is_booked || slot.is_past,
                                    'time-slot--selected': selectedScheduleId === slot.id,
                                }"
                                :disabled="slot.is_booked || slot.is_past"
                                @click="selectSlot(slot)"
                            >
                                <span x-text="slot.jam_mulai"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!loadingSlots && timeSlots.length === 0" class="text-center py-8">
                        <svg class="w-10 h-10 text-[#E5E7EB] mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
                        </svg>
                        <p class="text-sm text-[#9CA3AF]">Tidak ada slot tersedia di tanggal ini.</p>
                    </div>
                </div>
            </div>

            {{-- ── Right: Summary + Submit ── --}}
            <div class="lg:sticky lg:top-24">
                <div class="manage-card p-6">
                    <p class="section-title">Ringkasan Perubahan</p>

                    <div class="mt-4 space-y-4">
                        {{-- Old Schedule --}}
                        <div class="rounded-2xl border border-[#FECACA] bg-[#FEF2F2] p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-[#DC2626] mb-2">Jadwal Lama</p>
                            <p class="text-sm font-semibold text-[#111827]">
                                {{ \Carbon\Carbon::parse($booking->tanggalbooking)->format('d M Y') }}
                            </p>
                            <p class="text-xs text-[#6B7280]">
                                {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB
                            </p>
                        </div>

                        {{-- Arrow --}}
                        <div class="flex justify-center">
                            <svg class="w-5 h-5 text-[#9CA3AF]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </div>

                        {{-- New Schedule --}}
                        <div
                            class="rounded-2xl border p-4 transition-all duration-200"
                            :class="selectedScheduleId
                                ? 'border-[#6EE7B7] bg-[#ECFDF5]'
                                : 'border-[#E5E7EB] bg-[#F9FAFB]'"
                        >
                            <p class="text-xs font-bold uppercase tracking-wide mb-2"
                               :class="selectedScheduleId ? 'text-[#059669]' : 'text-[#9CA3AF]'">
                                Jadwal Baru
                            </p>
                            <template x-if="selectedScheduleId">
                                <div>
                                    <p class="text-sm font-semibold text-[#111827]" x-text="selectedDateLabel"></p>
                                    <p class="text-xs text-[#6B7280]" x-text="selectedSlotTime + ' WIB'"></p>
                                </div>
                            </template>
                            <template x-if="!selectedScheduleId">
                                <p class="text-sm text-[#9CA3AF]">Pilih tanggal & jam baru</p>
                            </template>
                        </div>
                    </div>

                    {{-- Error --}}
                    <div x-show="errorMsg" x-cloak class="alert-error mt-4">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span x-text="errorMsg" class="text-xs"></span>
                    </div>

                    {{-- Submit Form --}}
                    <form
                        method="POST"
                        action="{{ route('booking.manage.reschedule.store', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                        @submit.prevent="submitReschedule"
                        x-ref="rescheduleForm"
                        class="mt-6"
                    >
                        @csrf
                        <input type="hidden" name="tanggal" x-model="selectedDate" />
                        <input type="hidden" name="schedule_id" x-model="selectedScheduleId" />

                        <button
                            type="submit"
                            class="btn-primary w-full"
                            :disabled="!selectedScheduleId || isSubmitting"
                            :class="{ 'opacity-50 cursor-not-allowed': !selectedScheduleId || isSubmitting }"
                        >
                            <template x-if="!isSubmitting">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Konfirmasi Reschedule
                                </span>
                            </template>
                            <template x-if="isSubmitting">
                                <span class="flex items-center gap-2">
                                    <span class="spinner"></span> Memproses...
                                </span>
                            </template>
                        </button>

                        <p class="mt-3 text-xs text-center text-[#9CA3AF]">
                            Email konfirmasi akan dikirim setelah reschedule berhasil.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="border-t border-[#E5E7EB] bg-[#EDEBFA] mt-12">
        <div class="manage-shell mx-auto px-6 py-8 text-center">
            <p class="text-xs text-[#9CA3AF]">&copy; {{ date('Y') }} BookQu. Semua hak dilindungi.</p>
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
                // State
                viewYear:           new Date().getFullYear(),
                viewMonth:          new Date().getMonth(), // 0-indexed
                selectedDate:       '',
                selectedDateLabel:  '',
                selectedScheduleId: null,
                selectedSlotTime:   '',
                timeSlots:          [],
                loadingSlots:       false,
                isSubmitting:       false,
                errorMsg:           '',

                // Build availability lookup
                availabilityMap: Object.fromEntries(
                    AVAILABILITY_DATA.map(r => [r.date, r])
                ),

                init() {
                    // Start calendar on current month
                    const today = new Date();
                    this.viewYear  = today.getFullYear();
                    this.viewMonth = today.getMonth();
                },

                // ── Calendar ─────────────────────────────────────────────────
                get currentMonthLabel() {
                    const months = ['Januari','Februari','Maret','April','Mei','Juni',
                                    'Juli','Agustus','September','Oktober','November','Desember'];
                    return months[this.viewMonth] + ' ' + this.viewYear;
                },

                get calendarCells() {
                    const cells     = [];
                    const firstDay  = new Date(this.viewYear, this.viewMonth, 1).getDay(); // 0=Sun
                    const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                    const todayStr  = new Date().toISOString().split('T')[0];

                    // Empty cells before first day
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

                // ── Date / Slot selection ─────────────────────────────────────
                async selectDate(dateStr) {
                    if (this.selectedDate === dateStr) return;
                    this.selectedDate       = dateStr;
                    this.selectedScheduleId = null;
                    this.selectedSlotTime   = '';
                    this.errorMsg           = '';

                    // Human-readable label
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

                // ── Submit ────────────────────────────────────────────────────
                submitReschedule() {
                    if (!this.selectedDate || !this.selectedScheduleId) {
                        this.errorMsg = 'Pilih tanggal dan jam terlebih dahulu.';
                        return;
                    }
                    this.isSubmitting = true;
                    this.$refs.rescheduleForm.submit();
                },
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
