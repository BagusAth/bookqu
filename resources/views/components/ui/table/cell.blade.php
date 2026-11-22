@props(['phone' => null, 'title' => null, 'wrap' => false, 'align' => 'start'])

<div role="cell" @if ($title !== null) title="{{ $title }}" @endif {{ $attributes->class([
    'bq-table-cell min-w-0',
    'bq-table-main font-semibold' => $phone === 'main',
    'bq-table-sub text-bq-small text-bq-text-muted' => $phone === 'subtitle',
    'bq-table-end' => $phone === 'end',
    'text-right' => $align === 'end',
]) }}>
    <div @class(['min-w-0', 'break-words' => $wrap || $title === null, 'truncate' => ! $wrap && $title !== null])>{{ $slot }}</div>
    @isset($subtitle)
        <div class="bq-table-caption text-bq-small text-bq-text-muted" {{ $subtitle->attributes->whereStartsWith('title') }}>{{ $subtitle }}</div>
    @endisset
</div>
