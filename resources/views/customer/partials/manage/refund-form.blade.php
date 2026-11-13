<section class="mt-4 space-y-3 rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="refund-request-title">
    <h3 class="text-sm font-semibold" id="refund-request-title">Pengajuan refund</h3>
    @if ($refundRequestStatus)
        <p class="text-sm">{{ $refundRequestStatus }}</p>
    @endif
    @if ($refundBookingCode)
        <p class="text-sm text-slate-600">Booking tetap berlaku sampai pemilik menyetujui pengajuan Anda.</p>
        <form class="grid gap-3" method="POST" action="{{ route('booking.manage.cancel', ['booking_code' => $refundBookingCode]) }}">
            @csrf
            <label class="grid gap-1 text-sm">Alasan
                <textarea class="rounded-lg border border-slate-200 p-2" name="reason" maxlength="500" required>{{ old('reason') }}</textarea>
            </label>
            <label class="grid gap-1 text-sm">Nama bank
                <input class="rounded-lg border border-slate-200 p-2" name="bank_nama" maxlength="100" required>
            </label>
            <label class="grid gap-1 text-sm">Nomor rekening
                <input class="rounded-lg border border-slate-200 p-2" name="nomor_rekening" inputmode="numeric" pattern="[0-9]{5,30}" required>
            </label>
            <label class="grid gap-1 text-sm">Nama pemilik rekening
                <input class="rounded-lg border border-slate-200 p-2" name="nama_pemilik_rekening" maxlength="100" required>
            </label>
            <button class="justify-self-start rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Kirim pengajuan refund</button>
        </form>
    @elseif (!$refundRequestStatus)
        <p class="text-sm text-slate-600">Hubungi pemilik untuk bantuan pembatalan atau refund.</p>
    @endif
</section>
