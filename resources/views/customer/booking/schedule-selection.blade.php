@extends('customer.layouts.booking-shell')

@section('title', 'Pilih jadwal')
@section('current_step', 2)
@section('back_url', \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug))
@section('back_label', 'Kembali ke pilih layanan')

@section('content')
    @php
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
    @endphp

    <div id="booking-schedule-root" x-data="schedulePicker(@js($scheduleConfig))" class="mx-auto max-w-5xl space-y-4">
        <x-customer.page-header title="Pilih jadwal" :description="$service->namalayanan" />
        @if ($errors->any())
            <x-ui.banner :title="$errors->first()" tone="danger" />
        @endif

        <form id="booking-schedule-form" method="POST"
            action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.select-schedule', $tenant->slug) }}"
            @submit="submit($event)" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
            @csrf
            <input type="hidden" name="tanggal" :value="selectedDate" value="{{ $selectedDate }}">
            <template x-for="slot in sortedSelectedSlots" :key="slot.id">
                <div>
                    <input type="hidden" name="jam[]" :value="slot.time">
                    <input type="hidden" name="schedule_ids[]" :value="slot.id">
                </div>
            </template>

            <div class="min-w-0 space-y-4">
                <x-ui.panel title="Pilih tanggal" description="Pilih tanggal yang tersedia untuk reservasi Anda.">
                    <x-customer.date-strip :dates="$dates" />
                </x-ui.panel>
                <x-ui.panel title="Pilih waktu">
                    <p class="mb-3 text-bq-body text-bq-text-muted" x-text="selectedDateFormatted">{{ $selectedDateLabel }}</p>
                    <p class="mb-3 rounded-bq-control bg-bq-danger-bg p-3 text-bq-body text-bq-danger" role="status"
                        x-show="errorMessage" x-text="errorMessage" x-cloak></p>
                    <p x-show="loadingSlots" x-cloak class="text-bq-body">Memuat jadwal…</p>

                    <div x-show="!loadingSlots" class="space-y-4">
                        <div x-show="morningSlots.length">
                            <h3 class="mb-2 text-bq-label">Sesi pagi</h3>
                            <x-customer.time-grid items="morningSlots" label="Jam sesi pagi" />
                        </div>
                        <div x-show="afternoonSlots.length">
                            <h3 class="mb-2 text-bq-label">Sesi siang dan sore</h3>
                            <x-customer.time-grid items="afternoonSlots" label="Jam sesi siang dan sore" />
                        </div>
                        <div x-show="eveningSlots.length">
                            <h3 class="mb-2 text-bq-label">Sesi malam</h3>
                            <x-customer.time-grid items="eveningSlots" label="Jam sesi malam" />
                        </div>
                        <div x-show="allCurrentSlots.length === 0 && !errorMessage" x-cloak>
                            <x-ui.empty title="Belum ada sesi di tanggal ini" description="Pilih tanggal lain untuk melihat jadwal." />
                        </div>
                    </div>
                    <noscript>
                        <p class="mt-4 text-bq-body">Aktifkan JavaScript untuk memilih jadwal.</p>
                        @foreach ([...$morningSlots, ...$afternoonSlots, ...$eveningSlots] as $slot)
                            @continue($slot['is_past'])
                            <p class="bq-num text-bq-small">{{ $slot['display_time'] }} · {{ $slot['price_label'] }}</p>
                        @endforeach
                    </noscript>
                </x-ui.panel>
            </div>

            <div class="space-y-3 lg:sticky lg:top-24 lg:self-start">
                <x-customer.summary :items="['Layanan' => $service->namalayanan]">
                    <p class="my-3 text-bq-body text-bq-text-muted" x-text="selectedSlots.length ? selectedSlots.length + ' sesi' : 'Belum ada sesi yang dipilih'"></p>
                    <ul class="space-y-2">
                        <template x-for="slot in sortedSelectedSlots" :key="slot.id">
                            <li class="bq-num flex flex-wrap justify-between gap-x-2 border-t border-bq-border pt-2 text-bq-small">
                                <span x-text="slot.display_time"></span>
                                <span x-text="slot.price_label"></span>
                            </li>
                        </template>
                    </ul>
                </x-customer.summary>
                <div class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-3 border-t border-bq-border bg-bq-surface p-4 lg:static lg:border-0 lg:bg-transparent lg:p-0">
                    <span x-text="selectedSlots.length + ' sesi'" aria-hidden="true" class="text-bq-small lg:hidden"></span>
                    <button type="submit" class="bq-btn bq-btn-primary min-h-11 lg:w-full" disabled
                        :disabled="!selectedSlots.length || loadingSlots || isSubmitting">Lanjutkan</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script defer src="{{ asset('js/booking-schedule.js') }}"></script>
@endsection
