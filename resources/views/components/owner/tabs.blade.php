{{--
    Tabs with the active tab in the URL (BQ-129, owner-portal.md §9). Each tab is a link to the
    same page with `?{param}={key}`, keeping the other query values; an unknown or missing value
    selects the first tab. The page reads the same parameter to render the active panel.
--}}
@props([
    'tabs' => [],
    'param' => 'tab',
    'active' => null,
    'label' => 'Bagian halaman',
    'baseUrl' => null,
])

@php
    $keys = array_map('strval', array_keys($tabs));
    $requested = $active ?? request()->query($param);
    $current = in_array((string) $requested, $keys, true) ? (string) $requested : ($keys[0] ?? null);
@endphp

{{-- BQ-161: on phones the row fades at its right edge to show it scrolls, and the active tab scrolls into view. --}}
<nav role="tablist" aria-label="{{ $label }}" x-data x-init="$el.querySelector('[aria-current]')?.scrollIntoView({ inline: 'center', block: 'nearest' })" {{ $attributes->merge(['class' => 'flex gap-1 overflow-x-auto border-b border-bq-border no-scrollbar max-md:pr-8 max-md:[mask-image:linear-gradient(to_right,black_calc(100%-2.5rem),transparent)]']) }}>
    @foreach ($tabs as $key => $tabLabel)
        @php $isActive = (string) $key === $current; @endphp
        <a
            role="tab"
            href="{{ $baseUrl ? url()->query($baseUrl, [$param => $key]) : request()->fullUrlWithQuery([$param => $key]) }}"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
            @if ($isActive) aria-current="page" @endif
            class="shrink-0 whitespace-nowrap border-b-2 px-3 py-3 text-sm {{ $isActive ? 'border-bq-primary font-semibold text-bq-primary' : 'border-transparent font-medium text-bq-text-muted hover:border-bq-border-strong hover:text-bq-ink' }}"
        >{{ $tabLabel }}</a>
    @endforeach
</nav>
