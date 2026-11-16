{{--
    Empty state (BQ-129, owner-portal.md §9): what the list is, how to start, one button.
    Pass `action-label` with `action-href` for a link, or an `action` slot for a button.
--}}
@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
    'icon' => 'sparkle',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 rounded-xl border border-dashed border-bq-border-strong bg-bq-surface px-6 py-10 text-center']) }}>
    <span class="mb-1 flex h-11 w-11 items-center justify-center rounded-full bg-bq-primary-light text-bq-primary"><x-owner.icon :name="$icon" /></span>
    <p class="text-base font-semibold text-bq-ink">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-bq-text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @elseif ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="bq-btn bq-btn-primary mt-2">{{ $actionLabel }}</a>
    @endisset
</div>
