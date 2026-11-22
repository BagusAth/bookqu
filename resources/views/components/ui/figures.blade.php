{{--
    Figures (BQ-162, prototype ui.angka): one headline figure with its label and context, and supporting
    figures beside it. `more` is a list of ['value' => …, 'label' => …, 'delta' => int|null].
--}}
@props(['value', 'label', 'context' => null, 'delta' => null, 'more' => []])

<div {{ $attributes->class('bq-figures') }}>
    <div class="bq-figures-main min-w-0">
        <b>{{ $value }}</b>
        <span>{{ $label }} @if ($delta !== null)<x-ui.delta :value="$delta" />@endif</span>
        @if ($context)<small>{{ $context }}</small>@endif
    </div>
    @if ($more !== [])
        <div class="bq-figures-more">
            @foreach ($more as $figure)
                <div>
                    <b>{{ $figure['value'] }}</b>
                    <span>{{ $figure['label'] }} @if (($figure['delta'] ?? null) !== null)<x-ui.delta :value="$figure['delta']" />@endif</span>
                </div>
            @endforeach
        </div>
    @endif
    {{ $slot }}
</div>
