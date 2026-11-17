@props(['columns', 'label', 'selectable' => false, 'actions' => false])

<div {{ $attributes->class('bq-panel min-w-0') }}>
    @isset($note)
        {{ $note }}
    @endisset

    <div role="table" aria-label="{{ $label }}"
        @class(['bq-table', 'bq-table-selectable' => $selectable, 'bq-table-actions' => $actions])
        style="--bq-data-columns: {{ count($columns) }}">
        <div role="row" class="bq-table-row bq-table-header">
            @if ($selectable)
                <div role="columnheader" class="bq-table-choice">
                    @isset($selection){{ $selection }}@else<span class="sr-only">Pilih</span>@endisset
                </div>
            @endif
            @foreach ($columns as $heading)
                <div role="columnheader" class="min-w-0">{{ $heading }}</div>
            @endforeach
            @if ($actions)
                <div role="columnheader" class="bq-table-action"><span class="sr-only">Tindakan</span></div>
            @endif
        </div>

        {{ $slot }}
    </div>
</div>
