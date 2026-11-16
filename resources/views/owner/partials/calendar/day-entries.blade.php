<div class="mt-2 space-y-2">
    @if ($day['closed'])<p class="text-xs font-medium text-bq-warning">{{ $day['closed'] }}</p>@endif
    @foreach ($day['slots'] as $slot)
        @include('owner.partials.calendar.slot')
    @endforeach
    @foreach ($day['bookings'] as $booking)
        @include('owner.partials.calendar.booking')
    @endforeach
</div>
