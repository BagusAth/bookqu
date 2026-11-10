{{--
    Empty state (BQ-129, owner-portal.md §9): what the list is, how to start, one button.
    Pass `action-label` with `action-href` for a link, or an `action` slot for a button.
--}}
@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 rounded-xl border border-dashed border-bq-border bg-bq-surface px-6 py-10 text-center']) }}>
    <p class="text-sm font-semibold text-bq-text">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-bq-text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @elseif ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="mt-2 inline-flex items-center rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover">{{ $actionLabel }}</a>
    @endisset
</div>
