@if ($refundRequestStatus)
    <p class="text-bq-body">{{ $refundRequestStatus }}</p>
@endif
@if ($refundBookingCode)
    <section class="space-y-3 border-t border-bq-border pt-3" aria-labelledby="refund-request-title">
        <h3 class="text-bq-section" id="refund-request-title">Ajukan pengembalian dana</h3>
        <p class="text-bq-body text-bq-text-muted">Booking tetap berlaku sampai pemilik menyetujui pengajuan Anda.</p>
        <form class="grid gap-3" method="POST" action="{{ route('booking.manage.cancel', ['booking_code' => $refundBookingCode]) }}">
            @csrf
            <label class="grid gap-1 text-bq-label">Alasan
                <textarea class="bq-input" name="reason" maxlength="500" required>{{ old('reason') }}</textarea>
            </label>
            <label class="grid gap-1 text-bq-label">Nama bank
                <input class="bq-input" name="bank_nama" maxlength="100" required>
            </label>
            <label class="grid gap-1 text-bq-label">Nomor rekening
                <input class="bq-input" name="nomor_rekening" inputmode="numeric" pattern="[0-9]{5,30}" required>
            </label>
            <label class="grid gap-1 text-bq-label">Nama pemilik rekening
                <input class="bq-input" name="nama_pemilik_rekening" maxlength="100" required>
            </label>
            <x-ui.button type="submit" class="min-h-11 justify-self-start">Kirim pengajuan</x-ui.button>
        </form>
    </section>
@elseif (!$refundRequestStatus)
    <div class="flex flex-wrap items-center gap-2">
        <p class="text-bq-body text-bq-text-muted">Hubungi pemilik untuk bantuan pembatalan atau pengembalian dana.</p>
        <x-customer.help-link :href="$merchantWaUrl" />
    </div>
@endif
