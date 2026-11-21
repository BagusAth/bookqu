<form method="POST" action="{{ route('owner.settings.booking-policy') }}"
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
    <x-ui.form-row title="Pembatalan dan refund" description="Yang boleh dilakukan pelanggan lewat link booking.">
        <input type="hidden" name="refunds_enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="refunds_enabled" value="1" @checked(old('refunds_enabled', $bookingPolicy['refunds_enabled']))> Izinkan pelanggan mengajukan refund</label>
        <p class="text-sm text-bq-text-muted">Jika aktif, pelanggan dapat mengajukan refund sebelum batas waktu. Tim Anda meninjau permintaan sebelum refund diproses. Jika dinonaktifkan, pelanggan tidak dapat membuat permintaan baru.</p>
        <x-owner.field-error name="refunds_enabled" />
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'cancel_before_hours' => ['Batas pengajuan refund (jam sebelum sesi)', 0, 8760],
                'reschedule_before_hours' => ['Batas pindah jadwal (jam sebelum sesi)', 0, 8760],
            ] as $field => [$label, $min, $max])
                @include('owner.settings.policy-field')
            @endforeach
        </div>
    </x-ui.form-row>
    <x-ui.form-row title="Waktu booking" description="Kapan pelanggan boleh memesan.">
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'booking_min_lead_hours' => ['Paling lambat booking baru (jam sebelum sesi)', 0, 8760],
                'booking_max_days_ahead' => ['Booking paling jauh (hari ke depan)', 1, 365],
                'booking_buffer_minutes' => ['Jeda antar-booking (menit)', 0, 1440],
            ] as $field => [$label, $min, $max])
                @include('owner.settings.policy-field')
            @endforeach
        </div>
    </x-ui.form-row>
    <x-ui.form-row title="Sesi lewat" description="Supaya Laporan tetap benar tanpa menandai satu per satu.">
        <input type="hidden" name="auto_complete_bookings" value="0">
        <x-ui.switch name="auto_complete_bookings" value="1" :checked="(bool) old('auto_complete_bookings', $bookingPolicy['auto_complete_bookings'] ?? true)"
            label="Tandai selesai otomatis"
            description="Booking lunas ditandai selesai 6 jam setelah sesi berakhir, kecuali yang Anda tandai tidak datang atau sedang minta refund atau pembatalan." />
        <x-owner.field-error name="auto_complete_bookings" />
    </x-ui.form-row>
    <x-ui.form-row title="Kebijakan yang terlihat oleh pelanggan">
        <x-customer.booking-policy :policy="$bookingPolicy" />
    </x-ui.form-row>
    <x-ui.savebar class="!static flex-wrap">
        <x-ui.button variant="ghost" :href="route('owner.settings', ['tab' => 'policy'])">Batal</x-ui.button>
        <x-ui.button type="submit" variant="primary">Simpan kebijakan</x-ui.button>
    </x-ui.savebar>
</form>
