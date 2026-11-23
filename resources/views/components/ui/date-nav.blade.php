{{-- Date navigation (BQ-162, prototype ui.tglNav): ‹ Hari ini › and the period label. --}}
@props(['label', 'previous', 'next', 'today' => null])

<div {{ $attributes->class('bq-date-nav') }}>
    <a href="{{ $previous }}" class="bq-btn bq-btn-secondary bq-btn-sm bq-btn-icon !min-h-8 !min-w-8" aria-label="Sebelumnya"><x-owner.icon name="chevron-left" class="h-4 w-4" /></a>
    @if ($today)<a href="{{ $today }}" class="bq-btn bq-btn-secondary bq-btn-sm">Hari ini</a>@endif
    <a href="{{ $next }}" class="bq-btn bq-btn-secondary bq-btn-sm bq-btn-icon !min-h-8 !min-w-8" aria-label="Berikutnya"><x-owner.icon name="chevron-right" class="h-4 w-4" /></a>
    <b>{{ $label }}</b>
</div>
