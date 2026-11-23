{{--
    One question of the early access FAQ (BQ-175). The answer is always visible; the same texts feed the
    FAQPage structured data of the page.
--}}
@props(['question'])

<article {{ $attributes->class('rounded-2xl border border-bq-border bg-white px-5 py-4 shadow-[6px_6px_0_0] shadow-bq-primary/10') }}>
    <h3 class="text-base font-semibold text-bq-ink">{{ $question }}</h3>
    <p class="mt-1 text-sm text-bq-text-muted">{{ $slot }}</p>
</article>
