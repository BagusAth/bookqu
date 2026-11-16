{{--
    Stat (BQ-161): one figure with its label and one line of context. `hero` makes it the page's headline
    figure (Display size); use at most one hero per page.
--}}
@props([
    'label',
    'value',
    'context' => null,
    'hero' => false,
    'tone' => null,
    'href' => null,
])

@php
    $valueClass = $hero ? 'text-3xl font-bold' : 'text-xl font-bold';
    $toneClass = match ($tone) {
        'success' => 'text-bq-success', 'warning' => 'text-bq-warning', 'danger' => 'text-bq-danger', default => 'text-bq-ink',
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['bq-panel bq-panel-pad block min-w-0', 'hover:border-bq-primary' => $href]) }}>
    <p class="text-sm text-bq-text-muted">{{ $label }}</p>
    <p class="bq-num mt-1 leading-tight {{ $valueClass }} {{ $toneClass }}">{{ $value }}</p>
    @if ($context)<p class="mt-1 text-xs text-bq-text-muted">{{ $context }}</p>@endif
    {{ $slot }}
</{{ $tag }}>
