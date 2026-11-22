@props(['title', 'description' => null, 'icon' => null])

<div {{ $attributes->class('grid justify-items-center gap-2 px-4 py-10 text-center') }}>
    @if ($icon)
        <span class="grid size-10 place-items-center rounded-bq-panel bg-bq-surface-2 text-bq-primary"><x-owner.icon :name="$icon" /></span>
    @endif
    <p class="text-bq-form font-semibold text-bq-ink">{{ $title }}</p>
    @if ($description)
        <p class="max-w-96 text-bq-body text-bq-text-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
