@extends('customer.layouts.booking-shell')

@section('title', 'Ubah jadwal')
@section('back_url', route('booking.manage', ['booking_code' => $booking->booking_code]))
@section('back_label', 'Kembali ke reservasi')

@section('content')
    @php
        $config = [
            'dates' => $dates,
            'slotUrl' => route('booking.manage.reschedule.slots', ['booking_code' => $booking->booking_code]),
            'previousDate' => old('tanggal'),
            'previousSlot' => (int) old('schedule_id'),
        ];
    @endphp
    <div x-data="customerReschedule(@js($config))" class="mx-auto max-w-4xl space-y-4">
        <x-customer.page-header title="Ubah jadwal" description="Pilih tanggal dan jam baru." />
        @if ($errors->any())
            <x-ui.banner :title="$errors->first()" tone="danger" />
        @endif
        <x-customer.summary title="Jadwal saat ini" :items="['Tanggal' => $timeRange->start->locale('id')->translatedFormat('l, d M Y')]">
            <p class="bq-num mt-2 text-bq-body">Pukul {{ $timeRange->displayLabel() }} WIB</p>
        </x-customer.summary>

        <form method="POST" action="{{ route('booking.manage.reschedule.store', ['booking_code' => $booking->booking_code]) }}"
            @submit="submit($event)" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
            @csrf
            <input type="hidden" name="tanggal" :value="selectedDate">
            <input type="hidden" name="schedule_id" :value="selectedSlot?.id">

            <div class="min-w-0 space-y-4">
                <x-ui.panel title="Pilih tanggal">
                    <x-customer.date-strip :dates="$dates" :current-date="$timeRange->start->toDateString()" />
                </x-ui.panel>
                <x-ui.panel title="Pilih waktu">
                    <p class="text-bq-body text-bq-text-muted" x-show="!selectedDate" x-cloak>Pilih tanggal untuk melihat jam yang tersedia.</p>
                    <p class="text-bq-body text-bq-text-muted" x-show="loadingSlots" x-cloak>Memuat jadwal…</p>
                    <div x-show="selectedDate && !loadingSlots && timeSlots.length" x-cloak>
                        <x-customer.time-grid />
                    </div>
                    <div x-show="selectedDate && !loadingSlots && !timeSlots.length" x-cloak class="space-y-2">
                        <p role="status" class="text-bq-body" x-text="slotMessage || 'Tidak ada sesi tersedia di tanggal ini. Pilih tanggal lain.'"></p>
                        @if($merchantWaUrl)
                            <x-customer.help-link :href="$merchantWaUrl" label="Hubungi bisnis via WhatsApp" x-show="slotMessage">
                                Hubungi bisnis via WhatsApp
                            </x-customer.help-link>
                        @endif
                        <button type="button" class="bq-btn bq-btn-secondary min-h-11" x-show="loadError" @click="selectDate(selectedDate)">Coba lagi</button>
                    </div>
                </x-ui.panel>
            </div>

            <div class="space-y-3 lg:sticky lg:top-24 lg:self-start">
                <x-customer.summary title="Ringkasan perubahan jadwal">
                    <p class="mb-2 text-bq-body font-semibold" x-text="selectedDayLabel || 'Belum ada tanggal baru'"></p>
                    <p class="bq-num mb-2 text-bq-body" x-show="selectedSlot" x-text="selectedSlot?.display_time + ' WIB'" x-cloak></p>
                    <p class="text-bq-small text-bq-text-muted">{{ $pricePolicy }}</p>
                </x-customer.summary>
                <div class="fixed inset-x-0 bottom-0 z-30 border-t border-bq-border bg-bq-surface p-4 lg:static lg:border-0 lg:bg-transparent lg:p-0">
                    <button type="submit" class="bq-btn bq-btn-primary min-h-11 w-full" disabled
                        :disabled="!selectedSlot || loadingSlots || submitting">Konfirmasi jadwal baru</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script defer src="{{ asset('js/booking-reschedule.js') }}"></script>
@endsection
