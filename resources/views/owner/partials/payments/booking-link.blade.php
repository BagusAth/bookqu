@if ($row['bookingPanel'])
    <a href="{{ $row['bookingHref'] }}" x-data class="block text-xs font-medium text-bq-primary underline"
        @click.prevent="$dispatch('open-booking-panel', { url: @js($row['bookingPanel']), trigger: $el })">{{ $row['bookingCode'] }}</a>
@endif
