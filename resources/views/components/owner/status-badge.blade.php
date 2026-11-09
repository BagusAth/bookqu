{{--
    Status badge (BQ-129, owner-portal.md §9): one label and one colour per state, from
    App\Support\StatusBadge. Types: booking, payment, payout, refund_request, refund.
--}}
@props([
    'type',
    'status',
])

@php
    [$badgeLabel, $tone] = \App\Support\StatusBadge::for((string) $type, (string) $status);
    $toneClass = match ($tone) {
        'info' => 'bg-bq-info-bg text-bq-info',
        'success' => 'bg-bq-success-bg text-bq-success',
        'warning' => 'bg-bq-warning-bg text-bq-warning',
        'danger' => 'bg-bq-danger-bg text-bq-danger',
        default => 'bg-bq-background text-bq-text-muted ring-1 ring-inset ring-bq-border',
    };
@endphp

<span data-tone="{{ $tone }}" {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap {$toneClass}"]) }}>{{ $badgeLabel }}</span>
