@props(['label', 'description' => null])

<label class="flex cursor-pointer items-start gap-3 py-1.5 text-bq-body">
    <input type="checkbox" role="switch" {{ $attributes->class('peer sr-only') }}>
    <span aria-hidden="true" class="relative mt-0.5 h-5 w-9 shrink-0 rounded-full bg-bq-border-strong after:absolute after:top-0.5 after:left-0.5 after:size-4 after:rounded-full after:bg-bq-surface peer-checked:bg-bq-primary peer-checked:after:translate-x-4 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-bq-primary peer-disabled:opacity-60"></span>
    <span>
        <span class="block font-semibold">{{ $label }}</span>
        @if ($description)<span class="block text-bq-small text-bq-text-muted">{{ $description }}</span>@endif
    </span>
</label>
