{{--
    Section heading of the early access page (BQ-175): the section's <h2> and an optional lead.
    Pass the same `id` to the section's `aria-labelledby`.
--}}
@props([
    'id',
    'title',
    'lead' => null,
    'align' => 'center',
])

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $align === 'center']) }}>
    <h2 id="{{ $id }}" class="text-3xl font-bold tracking-tight text-bq-ink sm:text-4xl">{{ $title }}</h2>
    @if ($lead)
        <p class="mt-3 text-base text-bq-text-muted">{{ $lead }}</p>
    @endif
</div>
