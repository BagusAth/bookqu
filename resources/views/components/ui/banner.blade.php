@props(['title', 'description' => null, 'tone' => 'neutral', 'icon' => null])

@php
    [$color, $iconColor, $fallbackIcon] = match ($tone) {
        'warning' => ['border-bq-warning-border bg-bq-warning-bg', 'text-bq-warning', 'alert'],
        'danger' => ['border-bq-danger-border bg-bq-danger-bg', 'text-bq-danger', 'alert'],
        default => ['border-bq-border bg-bq-surface', 'text-bq-primary', 'info'],
    };
@endphp

<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class([
    'flex flex-wrap items-start gap-3 rounded-bq-panel border px-4 py-3 text-bq-body text-bq-text',
    $color,
]) }}>
    <x-owner.icon :name="$icon ?? $fallbackIcon" class="size-5 shrink-0 {{ $iconColor }}" />
    <div class="min-w-0 flex-1 basis-40">
        <p class="font-semibold">{{ $title }}</p>
        @if ($description)
            <p @class(['mt-1 text-bq-small', 'text-bq-warning-copy' => $tone === 'warning', 'text-bq-text-muted' => $tone !== 'warning'])>{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
    @isset($action)
        <div class="ml-auto flex flex-wrap items-center gap-2">{{ $action }}</div>
    @endisset
</div>
