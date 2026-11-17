@props(['label', 'description' => null])

<label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-bq-panel border border-bq-border bg-bq-surface p-3 text-bq-body has-checked:border-bq-selected-border has-checked:bg-bq-primary-light">
    <input type="radio" {{ $attributes->class('mt-0.5 size-4 shrink-0 accent-bq-primary') }}>
    <span class="min-w-0">
        <span class="block font-semibold">{{ $label }}</span>
        @if ($description)<span class="mt-1 block text-bq-small text-bq-text-muted">{{ $description }}</span>@endif
    </span>
</label>
