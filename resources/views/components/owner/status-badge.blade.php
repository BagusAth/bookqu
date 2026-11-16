{{--
    Status badge (BQ-129, owner-portal.md §9): one label and one colour per state, from
    App\Support\StatusBadge. Types: booking, payment, payout, refund_request, refund, availability (active|inactive).
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
        default => 'bg-bq-surface-2 text-bq-text-muted',
    };
@endphp

<span data-tone="{{ $tone }}" {{ $attributes->merge(['class' => "bq-badge {$toneClass}"]) }}>{{ $badgeLabel }}</span>
