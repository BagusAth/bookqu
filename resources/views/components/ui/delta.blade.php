{{-- Change against the previous period, in percent (BQ-162, prototype ui.delta). --}}
@props(['value'])

@if ((int) $value === 0)
    <span class="bq-delta text-bq-text-muted">sama</span>
@else
    <span class="bq-delta {{ $value > 0 ? 'text-bq-success' : 'text-bq-danger' }}">
        <span aria-hidden="true">{{ $value > 0 ? '↑' : '↓' }}</span><span class="sr-only">{{ $value > 0 ? 'naik' : 'turun' }}</span>{{ abs((int) $value) }}%
    </span>
@endif
