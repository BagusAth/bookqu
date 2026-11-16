{{--
    List row (BQ-161): a row inside a panel, not a card. Slots: `leading` (time, avatar), default (title and meta),
    `trailing` (badge, amount, action). Pass `href` to make the whole row a link.
--}}
@props(['href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['flex min-w-0 items-center gap-3 px-4 py-3 sm:px-5', 'hover:bg-bq-primary-light/60' => $href]) }}>
    @isset($leading)<div class="shrink-0">{{ $leading }}</div>@endisset
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @isset($trailing)<div class="flex shrink-0 items-center gap-2">{{ $trailing }}</div>@endisset
</{{ $tag }}>
