{{-- Badge (BQ-161): a tone and a label. For booking, payment, payout and refund states use x-owner.status-badge. --}}
@props(['tone' => 'neutral'])

@php
    $toneClass = match ($tone) {
        'info' => 'bg-bq-info-bg text-bq-info',
        'success' => 'bg-bq-success-bg text-bq-success',
        'warning' => 'bg-bq-warning-bg text-bq-warning',
        'danger' => 'bg-bq-danger-bg text-bq-danger',
        'brand' => 'bg-bq-primary-light text-bq-primary',
        'accent' => 'bg-bq-accent-bg text-bq-ink',
        default => 'bg-bq-surface-2 text-bq-text-muted',
    };
@endphp

<span data-tone="{{ $tone }}" {{ $attributes->class("bq-badge {$toneClass}") }}>{{ $slot }}</span>
