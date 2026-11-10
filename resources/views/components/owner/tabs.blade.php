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
])

@php
    $keys = array_map('strval', array_keys($tabs));
    $requested = $active ?? request()->query($param);
    $current = in_array((string) $requested, $keys, true) ? (string) $requested : ($keys[0] ?? null);
@endphp

<nav role="tablist" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'flex gap-1 overflow-x-auto border-b border-bq-border no-scrollbar']) }}>
    @foreach ($tabs as $key => $tabLabel)
        @php $isActive = (string) $key === $current; @endphp
        <a
            role="tab"
            href="{{ request()->fullUrlWithQuery([$param => $key]) }}"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
            @if ($isActive) aria-current="page" @endif
            class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium {{ $isActive ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted hover:text-bq-text' }}"
        >{{ $tabLabel }}</a>
    @endforeach
</nav>
