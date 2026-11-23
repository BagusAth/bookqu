{{--
    Early access benefit (BQ-175). Render inside a <ul>.
--}}
@props([
    'number',
    'title',
])

<li {{ $attributes->class('rounded-2xl border border-bq-border bg-white px-5 py-4 shadow-[6px_6px_0_0] shadow-bq-primary/15') }}>
    <p class="text-xs font-semibold tracking-wide text-bq-warning">Benefit {{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</p>
    <h3 class="mt-1 text-base font-semibold text-bq-ink">{{ $title }}</h3>
    <p class="mt-0.5 text-sm text-bq-text-muted">{{ $slot }}</p>
</li>
