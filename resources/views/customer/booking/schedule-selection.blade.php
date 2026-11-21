@extends('customer.layouts.booking-shell')

@section('title', 'Pilih jadwal')
@section('immersive', true)
@section('bottom_bar', true)

@php
    $storefront = \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug);
    $serviceMeta = ($service->durasi ?? 60).' '.($service->satuan_durasi ?: 'menit').' per '.($service->satuan_harga ?: 'sesi');
    $priceLine = $servicePayload['price_label'].' per '.($service->satuan_harga ?: 'sesi');
    $scheduleConfig = [
        'slotsUrl' => \App\Support\CustomerBookingRoutes::url('customer.booking.schedule-slots', $tenant->slug),
        'initialDate' => $selectedDate,
        'initialDateLabel' => $selectedDateLabel,
        'dates' => $dates,
        'initialMorningSlots' => $morningSlots,
        'initialAfternoonSlots' => $afternoonSlots,
        'initialEveningSlots' => $eveningSlots,
        'selectedTimes' => $selectedTimes,
        'selectedScheduleIds' => $selectedScheduleIds,
    ];
    $groups = [
        ['items' => 'morningSlots', 'label' => 'Pagi'],
        ['items' => 'afternoonSlots', 'label' => 'Siang dan sore'],
        ['items' => 'eveningSlots', 'label' => 'Malam'],
    ];
@endphp

@section('content')
    <x-customer.step-header title="Pilih jadwal" :step="2" :back-url="$storefront" back-label="Kembali ke pilih layanan"
        :service="$service->namalayanan" :service-meta="$serviceMeta" :change-url="$storefront" overlap />

    <div id="booking-schedule-root" x-data="schedulePicker(@js($scheduleConfig))">
        <form id="booking-schedule-form" method="POST"
            action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.select-schedule', $tenant->slug) }}"
            @submit="submit($event)"
            class="relative mx-auto grid max-w-[1240px] gap-4 px-4 md:grid-cols-[330px_minmax(0,1fr)] md:items-start md:px-8 lg:grid-cols-[320px_minmax(0,1fr)_340px] lg:px-14">
            @csrf
            <input type="hidden" name="tanggal" :value="selectedDate" value="{{ $selectedDate }}">
            <div hidden>
                <template x-for="slot in sortedSelectedSlots" :key="slot.id">
                    <div>
                        <input type="hidden" name="jam[]" :value="slot.time">
                        <input type="hidden" name="schedule_ids[]" :value="slot.id">
                    </div>
                </template>
            </div>

            {{-- Phone: the chosen service sits on the header, then the month and a scrolling date strip. --}}
            <div class="-mt-9 min-w-0 space-y-4 md:hidden">
                <div class="sf-card-raised flex items-center gap-3 px-3 py-2.5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-sf-card-small bg-sf-tint-strong text-sf-primary-text">
                        <x-customer.icon name="calendar" :size="22" :stroke="1.6" />
                    </span>
                    <span class="min-w-0 grow">
                        <span class="block truncate text-sm font-bold">{{ $service->namalayanan }}</span>
                        <span class="block text-xs text-sf-muted">{{ $serviceMeta }}</span>
                    </span>
                    <a href="{{ $storefront }}" class="px-1 py-3 text-[13px] font-bold text-sf-primary-text">Ganti</a>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 pb-2.5">
                        <h2 class="text-[17px] font-bold first-letter:uppercase" x-text="stripMonthLabel">Pilih tanggal</h2>
                        <button type="button" class="sf-btn-secondary px-3 text-[13px]" x-on:click="$dispatch('open-dialog', { id: 'kalender-jadwal', trigger: $el })">
                            <x-customer.icon name="calendar" :size="16" :stroke="2" />
                            Kalender
                        </button>
                    </div>
                    <x-customer.date-strip :dates="$dates" label="Pilih tanggal" class="-mx-4 px-4" />
                </div>
            </div>

            {{-- Tablet and desktop: month calendar panel. --}}
            <x-customer.month-calendar class="relative -mt-9 hidden md:block lg:-mt-12" heading-id="kalender-panel" />

            <section aria-labelledby="judul-jam" class="sf-card relative min-w-0 p-[18px] md:-mt-9 lg:-mt-12" x-bind:aria-busy="loadingSlots.toString()">
                <div class="flex flex-wrap items-baseline justify-between gap-1">
                    <h2 id="judul-jam" class="text-[17px] font-bold" x-text="selectedDateFormatted">{{ $selectedDateLabel }}</h2>
                    <span class="text-[13px] text-sf-muted">Waktu WIB</span>
                </div>
                <p class="mt-1 text-sm text-sf-muted">Pilih waktu</p>
                <p class="mt-2.5 flex flex-wrap gap-x-3.5 gap-y-1.5 text-[13px] text-sf-muted">
                    <span class="sf-num">{{ $priceLine }}</span>
                    <span>Bisa pilih beberapa sesi berurutan</span>
                </p>

                @if ($errors->any())
                    <x-customer.status-note tone="danger" role="alert" class="mt-4">{{ $errors->first() }}</x-customer.status-note>
                @endif
                <div x-show="errorMessage" x-cloak class="mt-4">
                    <x-customer.status-note tone="danger" role="status"><span x-text="errorMessage"></span></x-customer.status-note>
                </div>

                <div x-show="loadingSlots" x-cloak class="mt-4">
                    <div class="grid grid-cols-3 gap-2 lg:grid-cols-4" aria-hidden="true">
                        @foreach (range(1, 6) as $placeholder)
                            <span class="sf-skeleton h-14"></span>
                        @endforeach
                    </div>
                    <p class="mt-2.5 text-[13px] text-sf-muted">Memuat jam yang tersedia…</p>
                </div>

                <div x-show="!loadingSlots">
                    @foreach ($groups as $group)
                        <div x-show="{{ $group['items'] }}.length" class="mt-4">
                            <h3 class="mb-2 text-xs font-bold text-sf-muted">{{ $group['label'] }}</h3>
                            <div role="group" aria-label="Jam sesi {{ strtolower($group['label']) }}" class="grid grid-cols-3 gap-2 lg:grid-cols-4">
                                <template x-for="slot in {{ $group['items'] }}" :key="slot.id">
                                    <button type="button" class="sf-tile min-h-14 px-2"
                                        :disabled="!slot.is_available" :aria-pressed="String(isSlotSelected(slot))"
                                        :aria-label="slot.display_time + ' WIB' + (slot.detail ? ', ' + slot.detail : '')"
                                        @click="selectSlot(slot)">
                                        <span class="sf-num text-[15px]" x-text="startLabel(slot)"></span>
                                        <span class="sf-tile-note" x-show="slot.detail" x-text="slot.detail"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    @endforeach

                    <div x-show="allCurrentSlots.length === 0 && !errorMessage" x-cloak role="status"
                        class="mt-4 rounded-sf-card-small bg-sf-page p-[22px] text-center">
                        <p class="text-[15px] font-bold">Tidak ada sesi di tanggal ini</p>
                        <p class="mt-1 text-[13px] text-sf-muted" x-show="nearestAvailableDay">Coba tanggal terdekat yang masih ada sesi.</p>
                        <p class="mt-1 text-[13px] text-sf-muted" x-show="!nearestAvailableDay">Belum ada sesi yang bisa dipesan. Hubungi bisnis untuk jadwal lain.</p>
                        <button type="button" class="sf-btn sf-btn-sm mt-3.5" x-show="nearestAvailableDay" x-on:click="selectDate(nearestAvailableDay.date)">
                            Lihat <span x-text="nearestAvailableDay?.label"></span>
                        </button>
                    </div>
                </div>

                <noscript>
                    <p class="mt-4 text-sm">Aktifkan JavaScript untuk memilih jadwal.</p>
                    @foreach ([...$morningSlots, ...$afternoonSlots, ...$eveningSlots] as $slot)
                        @continue($slot['is_past'])
                        <p class="sf-num text-[13px]">{{ $slot['display_time'] }} · {{ $slot['price_label'] }}</p>
                    @endforeach
                </noscript>
            </section>

            {{-- Desktop: sticky summary. --}}
            <x-customer.summary-panel class="relative hidden lg:-mt-12 lg:block" :title="$service->namalayanan">
                <div class="flex justify-between gap-4">
                    <dt class="text-sf-muted">Tanggal</dt>
                    <dd class="text-right font-bold" x-text="selectedDayShort"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-sf-muted">Jam</dt>
                    <dd class="sf-num text-right font-bold" x-text="selectedTimeLabel"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-sf-muted">Sesi</dt>
                    <dd class="sf-num text-right font-bold" x-text="selectedSlots.length"></dd>
                </div>
                <x-slot:total><span x-text="subtotalLabel">Rp0</span></x-slot:total>
                <x-slot:action>
                    <button type="submit" class="sf-btn sf-btn-lg w-full" disabled
                        :disabled="!selectedSlots.length || loadingSlots || isSubmitting" :aria-busy="isSubmitting.toString()">Lanjut ke data diri</button>
                </x-slot:action>
                <x-slot:note>Tambahan dan kode promo diatur di langkah berikutnya.</x-slot:note>
            </x-customer.summary-panel>

            <x-customer.bottom-bar aria-label="Ringkasan pilihan">
                <x-slot:summary>
                    <p class="truncate text-[13px] text-sf-muted" x-text="selectedSlots.length ? selectedSlots.length + ' sesi · ' + selectedDayShort : 'Belum ada sesi dipilih'">Belum ada sesi dipilih</p>
                    <p class="sf-num text-xl font-bold" x-text="subtotalLabel">Rp0</p>
                </x-slot:summary>
                <button type="submit" class="sf-btn sf-btn-lg px-6" disabled
                    :disabled="!selectedSlots.length || loadingSlots || isSubmitting" :aria-busy="isSubmitting.toString()">Lanjutkan</button>
            </x-customer.bottom-bar>
        </form>

        <x-customer.sheet id="kalender-jadwal" title="Pilih tanggal">
            <x-customer.month-calendar :panel="false" heading-id="kalender-lembar" select="pickFromCalendar" />
        </x-customer.sheet>
    </div>
@endsection

@section('scripts')
    <script defer src="{{ asset('js/owner-ui.js') }}"></script>
    <script defer src="{{ asset('js/booking-schedule.js') }}"></script>
@endsection
