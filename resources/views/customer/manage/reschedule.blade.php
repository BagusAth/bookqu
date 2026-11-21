@extends('customer.layouts.booking-shell')

@section('title', 'Ubah jadwal')
@section('immersive', true)
@section('bottom_bar', true)

@php
    $manageUrl = route('booking.manage', ['booking_code' => $booking->booking_code]);
    $currentDate = $timeRange->start->toDateString();
    $currentLabel = $timeRange->start->locale('id')->translatedFormat('D, d M Y');
    $config = [
        'dates' => $dates,
        'slotUrl' => route('booking.manage.reschedule.slots', ['booking_code' => $booking->booking_code]),
        'previousDate' => old('tanggal'),
        'previousSlot' => (int) old('schedule_id'),
        'currentDate' => $currentDate,
    ];
@endphp

@section('content')
    <div x-data="customerReschedule(@js($config))">
        <x-customer.step-header title="Ubah jadwal" :back-url="$manageUrl" back-label="Kembali ke reservasi" overlap>
            {{-- Tablet: From → To stays in the header, never below the content (spec §7.7). --}}
            <x-slot:aside>
                <div class="flex items-center gap-2 rounded-sf-card-small bg-sf-hero-faint px-3.5 py-2 text-sm lg:hidden">
                    <span><span class="block text-xs text-sf-hero-soft">Dari</span><span class="sf-num font-bold">{{ $currentLabel }} · {{ $timeRange->displayLabel() }}</span></span>
                    <x-customer.icon name="arrow-right" :size="18" />
                    <span><span class="block text-xs text-sf-hero-soft">Ke</span><span class="sf-num font-bold" x-text="selectedSlot ? selectedDayLabel + ' · ' + selectedSlot.display_time : 'Belum dipilih'">Belum dipilih</span></span>
                </div>
            </x-slot:aside>
        </x-customer.step-header>

        <form method="POST" action="{{ route('booking.manage.reschedule.store', ['booking_code' => $booking->booking_code]) }}"
            @submit="submit($event)"
            class="relative mx-auto grid max-w-[1240px] gap-4 px-4 md:grid-cols-[330px_minmax(0,1fr)] md:items-start md:px-8 lg:grid-cols-[320px_minmax(0,1fr)_340px] lg:px-14">
            @csrf
            <input type="hidden" name="tanggal" :value="selectedDate">
            <input type="hidden" name="schedule_id" :value="selectedSlot?.id">

            {{-- Phone: current and new schedule, price rule, date strip. --}}
            <div class="-mt-9 min-w-0 space-y-4 md:hidden">
                <div class="sf-card-raised divide-y divide-sf-divider">
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="text-[13px] text-sf-muted">Saat ini</span>
                        <span class="sf-num text-right text-sm font-bold">{{ $currentLabel }} · Pukul {{ $timeRange->displayLabel() }} WIB</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="text-[13px] text-sf-muted">Baru</span>
                        <span class="sf-num text-right text-sm font-bold text-sf-primary-text" x-text="selectedSlot ? selectedDayLabel + ' · ' + selectedSlot.display_time + ' WIB' : 'Belum dipilih'">Belum dipilih</span>
                    </div>
                </div>
                <details class="sf-card-small px-4 py-3 text-sm">
                    <summary class="min-h-6 cursor-pointer font-semibold">Aturan harga</summary>
                    <p class="mt-2 text-[13px] text-sf-muted">{{ $pricePolicy }}</p>
                </details>
                <div>
                    <div class="flex items-center justify-between gap-3 pb-2.5">
                        <h2 class="text-[17px] font-bold" x-text="stripMonthLabel">Pilih tanggal</h2>
                        <button type="button" class="sf-btn-secondary px-3 text-[13px]" x-on:click="$dispatch('open-dialog', { id: 'kalender-ubah', trigger: $el })">
                            <x-customer.icon name="calendar" :size="16" :stroke="2" />Kalender
                        </button>
                    </div>
                    <x-customer.date-strip :dates="$dates" :current-date="$currentDate" class="-mx-4 px-4" />
                </div>
            </div>

            <x-customer.month-calendar class="relative -mt-9 hidden md:block lg:-mt-12" heading-id="kalender-ubah-panel" />

            <section aria-labelledby="judul-jam-baru" class="sf-card relative min-w-0 p-[18px] md:-mt-9 lg:-mt-12" x-bind:aria-busy="loadingSlots.toString()">
                <div class="flex flex-wrap items-baseline justify-between gap-1">
                    <h2 id="judul-jam-baru" class="text-[17px] font-bold" x-text="selectedDayLabel || 'Pilih waktu'">Pilih waktu</h2>
                    <span class="text-[13px] text-sf-muted">Waktu WIB</span>
                </div>
                <p class="mt-1 hidden text-[13px] text-sf-muted md:block">Jadwal saat ini: <span class="sf-num">{{ $currentLabel }} · Pukul {{ $timeRange->displayLabel() }} WIB</span></p>

                @if ($errors->any())
                    <x-customer.status-note tone="danger" role="alert" title="Jadwal belum berubah" class="mt-4">{{ $errors->first() }} Reservasi Anda tetap di jadwal lama.</x-customer.status-note>
                @endif

                <p class="mt-4 text-sm text-sf-muted" x-show="!selectedDate" x-cloak>Pilih tanggal untuk melihat jam yang tersedia.</p>
                <div x-show="loadingSlots" x-cloak class="mt-4">
                    <div class="grid grid-cols-3 gap-2 lg:grid-cols-4" aria-hidden="true">
                        @foreach (range(1, 6) as $placeholder)
                            <span class="sf-skeleton h-14"></span>
                        @endforeach
                    </div>
                    <p class="mt-2.5 text-[13px] text-sf-muted">Memuat jadwal…</p>
                </div>
                <div x-show="selectedDate && !loadingSlots && timeSlots.length" x-cloak class="mt-4">
                    <x-customer.time-grid label="Pilih jam baru" />
                </div>
                <div x-show="selectedDate && !loadingSlots && !timeSlots.length" x-cloak class="mt-4 space-y-3 rounded-sf-card-small bg-sf-page p-[22px] text-center">
                    <p role="status" class="text-[15px] font-bold" x-text="slotMessage || 'Tidak ada sesi tersedia di tanggal ini. Pilih tanggal lain.'"></p>
                    @if ($merchantWaUrl)
                        <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary" x-show="slotMessage">Hubungi bisnis via WhatsApp</a>
                    @endif
                    <button type="button" class="sf-btn-secondary" x-show="loadError" @click="selectDate(selectedDate)">Coba lagi</button>
                </div>
            </section>

            {{-- Desktop: From → To summary. --}}
            <x-customer.summary-panel class="relative hidden lg:-mt-12 lg:block" eyebrow="Perubahan jadwal" :title="$booking->layanan?->namalayanan ?? 'Reservasi'">
                <div class="flex justify-between gap-4">
                    <dt class="text-sf-muted">Dari</dt>
                    <dd class="sf-num text-right font-bold">{{ $currentLabel }}<br>{{ $timeRange->displayLabel() }} WIB</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-sf-muted">Ke</dt>
                    <dd class="sf-num text-right font-bold text-sf-primary-text" x-text="selectedSlot ? selectedDayLabel + ' · ' + selectedSlot.display_time + ' WIB' : 'Belum dipilih'">Belum dipilih</dd>
                </div>
                <x-slot:action>
                    <button type="submit" class="sf-btn sf-btn-lg w-full" disabled
                        :disabled="!selectedSlot || loadingSlots || submitting" :aria-busy="submitting.toString()">Simpan jadwal baru</button>
                </x-slot:action>
                <x-slot:note>{{ $pricePolicy }}</x-slot:note>
            </x-customer.summary-panel>

            <x-customer.bottom-bar aria-label="Simpan perubahan jadwal">
                <a href="{{ $manageUrl }}" class="sf-btn-secondary sf-btn-lg hidden md:inline-flex">Batal</a>
                <p class="hidden min-w-0 grow text-[13px] text-sf-muted md:block">{{ $pricePolicy }}</p>
                <button type="submit" class="sf-btn sf-btn-lg w-full md:w-auto" disabled
                    :disabled="!selectedSlot || loadingSlots || submitting" :aria-busy="submitting.toString()">
                    <span class="md:hidden">Simpan</span><span class="hidden md:inline">Simpan jadwal baru</span>
                </button>
            </x-customer.bottom-bar>
        </form>

        <x-customer.sheet id="kalender-ubah" title="Pilih tanggal baru">
            <x-customer.month-calendar :panel="false" heading-id="kalender-ubah-lembar" select="pickFromCalendar" />
        </x-customer.sheet>
    </div>
@endsection

@section('scripts')
    <script defer src="{{ asset('js/owner-ui.js') }}"></script>
    <script defer src="{{ asset('js/booking-reschedule.js') }}"></script>
@endsection
