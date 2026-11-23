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
    'supporting' => [],
])

@php
    $valueClass = $hero ? 'text-bq-figure' : 'text-bq-figure-small';
    $toneClass = match ($tone) {
        'success' => 'text-bq-success', 'warning' => 'text-bq-warning', 'danger' => 'text-bq-danger', default => 'text-bq-ink',
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['bq-panel bq-panel-pad block min-w-0', 'hover:border-bq-primary' => $href]) }}>
    <p class="text-bq-body text-bq-text-muted">{{ $label }}</p>
    <p class="bq-num mt-1 font-bq-display font-semibold {{ $valueClass }} {{ $toneClass }}">{{ $value }}</p>
    @if ($context)<p class="mt-1 text-bq-small text-bq-text-muted">{{ $context }}</p>@endif
    @if ($supporting !== [])
        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-3 border-t border-bq-border pt-3">
            @foreach ($supporting as $name => $figure)
                <div class="min-w-0">
                    <dt class="text-bq-small text-bq-text-muted">{{ $name }}</dt>
                    <dd class="bq-num text-bq-form font-semibold">{{ $figure }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    {{ $slot }}
</{{ $tag }}>
