@props(['title', 'description' => null])

<fieldset {{ $attributes->class('min-w-0 border-0') }}>
    <legend class="text-bq-form font-semibold text-bq-ink">{{ $title }}</legend>
    @if ($description)
        <p class="mt-1 text-bq-small text-bq-text-muted">{{ $description }}</p>
    @endif
    <div class="mt-3 grid gap-4">{{ $slot }}</div>
</fieldset>
