{{--
    Numbered step of "Mulai dalam tiga langkah" (BQ-175). Render inside an <ol>; `accent` marks the final step.
--}}
@props([
    'number',
    'title',
    'accent' => false,
])

<li {{ $attributes->class([
    'flex gap-4 rounded-2xl border bg-white p-5 shadow-[6px_6px_0_0] sm:p-6',
    'border-bq-accent shadow-bq-accent/40' => $accent,
    'border-bq-border shadow-bq-primary/10' => ! $accent,
]) }}>
    <span @class([
        'font-bq-display text-4xl font-semibold leading-none tabular-nums',
        'text-bq-warning-dot' => $accent,
        'text-bq-primary' => ! $accent,
    ]) aria-hidden="true">{{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span>
    <div>
        <h3 class="text-lg font-semibold text-bq-ink"><span class="sr-only">Langkah {{ $number }}: </span>{{ $title }}</h3>
        <p class="mt-1 text-sm text-bq-text-muted">{{ $slot }}</p>
    </div>
</li>
