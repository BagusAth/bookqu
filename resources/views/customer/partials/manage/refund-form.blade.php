{{-- Refund request or contact help (design A §7.6); follows `refunds_enabled` and the existing refund form. --}}
@if ($refundRequestStatus)
    <x-customer.status-note tone="info">{{ $refundRequestStatus }}</x-customer.status-note>
@endif
@if ($refundBookingCode)
    <div class="space-y-3" x-data="{ refundOpen: {{ $errors->hasAny(['reason', 'bank_nama', 'nomor_rekening', 'nama_pemilik_rekening']) ? 'true' : 'false' }} }">
        <button type="button" class="sf-btn-secondary w-full justify-between" x-on:click="refundOpen = !refundOpen"
            x-bind:aria-expanded="refundOpen.toString()" aria-controls="refund-request-form">
            <span id="refund-request-title">Ajukan pengembalian dana</span>
            <x-customer.icon name="chevron-down" :size="18" />
        </button>
        <div id="refund-request-form" x-show="refundOpen" x-cloak class="space-y-3">
            <p class="text-sm text-sf-muted">Booking tetap berlaku sampai pemilik menyetujui pengajuan Anda.</p>
            <form class="grid gap-3" method="POST" action="{{ route('booking.manage.cancel', ['booking_code' => $refundBookingCode]) }}">
                @csrf
                <label class="grid gap-1.5 text-[13px] font-semibold">Alasan
                    <textarea class="sf-area font-normal" name="reason" maxlength="500" required>{{ old('reason') }}</textarea>
                </label>
                <label class="grid gap-1.5 text-[13px] font-semibold">Nama bank
                    <input class="sf-field font-normal" name="bank_nama" maxlength="100" value="{{ old('bank_nama') }}" required>
                </label>
                <label class="grid gap-1.5 text-[13px] font-semibold">Nomor rekening
                    <input class="sf-field font-normal" name="nomor_rekening" inputmode="numeric" pattern="[0-9]{5,30}" required>
                </label>
                <label class="grid gap-1.5 text-[13px] font-semibold">Nama pemilik rekening
                    <input class="sf-field font-normal" name="nama_pemilik_rekening" maxlength="100" value="{{ old('nama_pemilik_rekening') }}" required>
                </label>
                <button type="submit" class="sf-btn justify-self-start">Kirim pengajuan</button>
            </form>
        </div>
    </div>
@elseif (!$refundRequestStatus)
    <p class="text-sm text-sf-muted">Hubungi pemilik untuk bantuan pembatalan atau pengembalian dana.</p>
    @if ($merchantWaUrl)
        <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary mt-3" aria-label="Bantuan lewat WhatsApp">
            <x-customer.icon name="whatsapp" :size="18" />Bantuan
        </a>
    @endif
@endif
