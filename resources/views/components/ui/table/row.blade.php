@props(['group' => false, 'muted' => false, 'twoLines' => false])

<div role="row" {{ $attributes->class([
    'bq-table-row',
    'bq-table-group' => $group,
    'bq-table-double' => $twoLines,
    'text-bq-text-muted' => $muted,
]) }}>
    @if ($group)
        <div role="rowheader" class="col-span-full">{{ $slot }}</div>
    @else
        @isset($selection)
            <div role="cell" class="bq-table-choice">{{ $selection }}</div>
        @endisset
        {{ $slot }}
        @isset($action)
            <div role="cell" class="bq-table-action">{{ $action }}</div>
        @endisset
    @endif
</div>
