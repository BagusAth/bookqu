{{-- BQ-162 (prototype M6): group services show participants against capacity; one-person services show nothing. --}}
@if ($rows[$booking->id]['capacity'] > 1)
    <span class="text-bq-text-muted"> · {{ $rows[$booking->id]['participants'] }}/{{ $rows[$booking->id]['capacity'] }} peserta</span>
@endif
