@props(['value', 'total', 'unit' => null])

@php
    $width = $total > 0 ? (int) round(100 * max(0, min($total, $value)) / $total) : 0;
@endphp

<span title="{{ $value }} dari {{ $total }}{{ $unit ? ' '.$unit : '' }}" {{ $attributes->class('inline-flex max-w-full items-center gap-2 text-bq-small text-bq-text-muted') }}>
    <span aria-hidden="true" class="h-1.5 w-14 shrink rounded-full bg-bq-surface-2">
        <span class="block h-full rounded-full bg-bq-primary" style="width: {{ $width }}%"></span>
    </span>
    <span class="bq-num">{{ $value }}/{{ $total }}{{ $unit ? ' '.$unit : '' }}</span>
</span>
