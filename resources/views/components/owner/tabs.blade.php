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
    'counts' => [],
    'attentionTabs' => ['action'],
])

@php
    $keys = array_map('strval', array_keys($tabs));
    $requested = $active ?? request()->query($param);
    $current = in_array((string) $requested, $keys, true) ? (string) $requested : ($keys[0] ?? null);
@endphp

{{-- BQ-161: on phones the row fades at its right edge to show it scrolls, and the active tab scrolls into view. --}}
<nav role="tablist" aria-label="{{ $label }}" x-data x-init="$el.querySelector('[aria-current]')?.scrollIntoView({ inline: 'center', block: 'nearest' })" {{ $attributes->merge(['class' => 'flex gap-5 overflow-x-auto border-b border-bq-border no-scrollbar max-md:pr-8 max-md:[mask-image:linear-gradient(to_right,black_calc(100%-2.5rem),transparent)]']) }}>
    @foreach ($tabs as $key => $tabLabel)
        @php $isActive = (string) $key === $current; @endphp
        <a
            role="tab"
            href="{{ $baseUrl ? url()->query($baseUrl, [$param => $key]) : request()->fullUrlWithQuery([$param => $key, 'page' => null]) }}"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
            @if ($isActive) aria-current="page" @endif
            class="-mb-px flex shrink-0 items-center gap-1.5 whitespace-nowrap border-b-2 pt-2 pb-2.5 text-bq-body {{ $isActive ? 'border-bq-primary font-semibold text-bq-primary' : 'border-transparent font-medium text-bq-text-muted hover:text-bq-ink' }}"
        >
            {{ $tabLabel }}
            @isset($counts[$key])
                {{-- BQ-162 (prototype .tabs): plain muted counts; marigold only for what needs the owner. --}}
                @if (in_array($key, $attentionTabs, true) && $counts[$key] > 0)
                    <span class="bq-count">{{ $counts[$key] }}</span>
                @else
                    <span class="text-bq-small font-medium tabular-nums text-bq-text-muted">{{ $counts[$key] }}</span>
                @endif
            @endisset
        </a>
    @endforeach
</nav>
