<form method="POST" action="{{ route('owner.settings.booking-policy') }}" class="space-y-4 rounded-xl border border-bq-border bg-bq-surface p-4">
    @csrf
    @method('PATCH')
    <h2 class="font-semibold">Kebijakan Booking</h2>
    <input type="hidden" name="refunds_enabled" value="0">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="refunds_enabled" value="1" @checked(old('refunds_enabled', $bookingPolicy['refunds_enabled']))> Izinkan pelanggan mengajukan refund</label>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ([
            'cancel_before_hours' => ['Batas pengajuan refund (jam sebelum sesi)', 0, 8760],
            'reschedule_before_hours' => ['Batas pindah jadwal (jam sebelum sesi)', 0, 8760],
            'booking_min_lead_hours' => ['Paling lambat booking baru (jam sebelum sesi)', 0, 8760],
            'booking_max_days_ahead' => ['Booking paling jauh (hari ke depan)', 1, 365],
            'booking_buffer_minutes' => ['Jeda antar-booking (menit)', 0, 1440],
        ] as $field => [$label, $min, $max])
            <label class="grid gap-1 text-sm">{{ $label }}
                <input type="number" name="{{ $field }}" min="{{ $min }}" max="{{ $max }}" value="{{ old($field, $bookingPolicy[$field]) }}" required class="rounded-lg border border-bq-border bg-bq-surface p-2">
            </label>
        @endforeach
    </div>
    <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan kebijakan</button>
</form>
<div class="mt-4 space-y-2">
    <h2 class="font-semibold">Kebijakan yang terlihat oleh pelanggan</h2>
    <x-customer.booking-policy :policy="$bookingPolicy" />
</div>
