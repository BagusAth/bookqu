@if ($walkIn = session('walkin_booking'))
    <div data-walkin-booking-success class="mb-4 rounded-xl border border-bq-success/30 bg-bq-success-bg px-4 py-3 text-sm text-bq-success" role="status" x-data>
        Booking berhasil dicatat.
        <a href="{{ $walkIn['href'] }}" class="font-semibold underline"
            @click.prevent="$dispatch('open-booking-panel', { url: @js($walkIn['panelUrl']), trigger: $el })">Buka {{ $walkIn['code'] }}</a>
    </div>
@endif
