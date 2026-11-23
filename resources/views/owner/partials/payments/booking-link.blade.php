@if ($row['bookingPanel'])
    <a href="{{ $row['bookingHref'] }}" x-data class="bq-link"
        @click.prevent="$dispatch('open-booking-panel', { url: @js($row['bookingPanel']), trigger: $el })">{{ $row['bookingCode'] }}</a>
@endif
