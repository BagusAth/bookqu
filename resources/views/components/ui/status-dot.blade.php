@props(['tone' => 'neutral'])

@php
    $dot = match ($tone) {
        'success' => 'bg-bq-success',
        'warning' => 'bg-bq-warning-dot',
        'danger' => 'bg-bq-danger',
        'brand' => 'bg-bq-primary',
        default => 'bg-bq-text-muted',
    };
@endphp

<span {{ $attributes->class('inline-flex items-center gap-2 text-bq-small') }}>
    <span aria-hidden="true" class="size-2 shrink-0 rounded-full {{ $dot }}"></span>
    {{ $slot }}
</span>
