@if ($row['bookingPanel'])
    <a href="{{ $row['bookingHref'] }}" x-data class="block text-xs bq-link"
        @click.prevent="$dispatch('open-booking-panel', { url: @js($row['bookingPanel']), trigger: $el })">{{ $row['bookingCode'] }}</a>
@endif
