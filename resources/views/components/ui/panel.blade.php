{{--
    Panel (BQ-161): groups related rows. Panels have a border and no shadow; only overlays cast a shadow.
    `title`, `description` and an `actions` slot form the header; `flush` removes the body padding (tables, lists).
--}}
@props([
    'title' => null,
    'description' => null,
    'flush' => false,
])

@php $hasHeader = $title || isset($actions); @endphp

<section {{ $attributes->class('bq-panel min-w-0') }}>
    @if ($hasHeader)
        <div class="flex flex-wrap items-start justify-between gap-3 {{ $flush ? 'border-b border-bq-border px-4 py-3 sm:px-5' : 'px-4 pt-4 sm:px-5 sm:pt-5' }}">
            <div class="min-w-0">
                @if ($title)<h2 class="text-base font-semibold text-bq-ink">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-sm text-bq-text-muted">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : ($hasHeader ? 'px-4 pt-3 pb-4 sm:px-5 sm:pb-5' : 'bq-panel-pad') }}">{{ $slot }}</div>
</section>
