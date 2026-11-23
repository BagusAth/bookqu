@props(['label', 'hideLabel' => false])

<label class="inline-flex min-h-8 cursor-pointer items-center gap-2 text-bq-small">
    <input type="checkbox" {{ $attributes->class('size-4 shrink-0 accent-bq-primary') }}>
    <span @class(['sr-only' => $hideLabel])>{{ $label }}</span>
</label>
