{{--
    Feature card of the early access hero (BQ-175): a small illustration, an eyebrow and a short promise.
    `tilt` (left | none | right) angles the card on wide screens only; the `illustration` slot is decorative.
--}}
@props([
    'eyebrow',
    'tilt' => 'none',
])

<li {{ $attributes->class([
    'flex flex-col gap-5 rounded-3xl border border-bq-border bg-white p-5 shadow-[0_12px_32px_-12px_rgba(56,33,134,0.25)] lg:p-6',
    'lg:-rotate-3' => $tilt === 'left',
    'lg:rotate-3' => $tilt === 'right',
]) }}>
    <div class="rounded-2xl bg-bq-background p-4" aria-hidden="true">
        {{ $illustration }}
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wide text-bq-text-muted">{{ $eyebrow }}</p>
        <h3 class="mt-1.5 text-base leading-snug text-bq-ink [&_strong]:font-bold">{{ $slot }}</h3>
    </div>
</li>
