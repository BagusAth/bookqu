<form method="POST" action="{{ route('owner.settings.booking-policy') }}" class="space-y-4 rounded-xl border border-bq-border bg-bq-surface p-4"
    x-data="{
        formatHours(value) {
            const count = Math.max(0, Math.floor(Number(value)));
            if (count < 24) return count + ' jam';
            const days = Math.floor(count / 24);
            const hours = count % 24;
            return count + ' jam = ' + days + ' hari' + (hours ? ' ' + hours + ' jam' : '');
        }
    }">
    @csrf
    @method('PATCH')
    <h2 class="font-semibold">Kebijakan Booking</h2>
    <input type="hidden" name="refunds_enabled" value="0">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="refunds_enabled" value="1" @checked(old('refunds_enabled', $bookingPolicy['refunds_enabled']))> Izinkan pelanggan mengajukan refund</label>
    <p class="text-sm text-bq-text-muted">Jika aktif, pelanggan dapat mengajukan refund sebelum batas waktu. Tim Anda meninjau permintaan sebelum refund diproses. Jika dinonaktifkan, pelanggan tidak dapat membuat permintaan baru.</p>
    <x-owner.field-error name="refunds_enabled" />
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ([
            'cancel_before_hours' => ['Batas pengajuan refund (jam sebelum sesi)', 0, 8760],
            'reschedule_before_hours' => ['Batas pindah jadwal (jam sebelum sesi)', 0, 8760],
            'booking_min_lead_hours' => ['Paling lambat booking baru (jam sebelum sesi)', 0, 8760],
            'booking_max_days_ahead' => ['Booking paling jauh (hari ke depan)', 1, 365],
            'booking_buffer_minutes' => ['Jeda antar-booking (menit)', 0, 1440],
        ] as $field => [$label, $min, $max])
            @php $usesHours = str_ends_with($field, '_hours'); @endphp
            <label class="grid gap-1 text-sm" @if ($usesHours) x-data="{ hours: @js(old($field, $bookingPolicy[$field])) }" @endif>{{ $label }}
                <input type="number" name="{{ $field }}" min="{{ $min }}" max="{{ $max }}" value="{{ old($field, $bookingPolicy[$field]) }}"
                    @if ($usesHours) x-model.number="hours" @endif required class="rounded-lg border border-bq-border bg-bq-surface p-2">
                @if ($usesHours)<span class="text-xs text-bq-text-muted" aria-live="polite" x-text="hours === '' ? '' : formatHours(hours)"></span>@endif
                <x-owner.field-error :name="$field" />
            </label>
        @endforeach
    </div>
    <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan kebijakan</button>
</form>
<div class="mt-4 space-y-2">
    <h2 class="font-semibold">Kebijakan yang terlihat oleh pelanggan</h2>
    <x-customer.booking-policy :policy="$bookingPolicy" />
</div>
