{{--
    Line icon for customer pages (design A §4): 1.5–2 px stroke, always aria-hidden.
    An icon-only control names itself with aria-label.
--}}
@props(['name', 'size' => 20, 'stroke' => 1.8])

@php
    $paths = [
        'arrow-right' => 'M5 12h14M13 6l6 6-6 6',
        'calendar' => 'M3 5h18v16H3zM3 10h18M8 3v4M16 3v4',
        'calendar-add' => 'M3 5h18v16H3zM3 10h18M8 3v4M16 3v4M12 13v5M9.5 15.5h5',
        'camera' => 'M3 8h3l2-3h8l2 3h3v11H3zM12 9a4 4 0 1 0 0 8a4 4 0 1 0 0-8',
        'check' => 'M5 12l5 5 9-10',
        'chevron-left' => 'M15 6l-6 6 6 6',
        'chevron-right' => 'M9 6l6 6-6 6',
        'chevron-down' => 'M6 9l6 6 6-6',
        'clock' => 'M12 3a9 9 0 1 0 0 18a9 9 0 1 0 0-18M12 7v5l3 2',
        'closed' => 'M3 5h18v16H3zM3 10h18M8 3v4M16 3v4M9 14l6 4M15 14l-6 4',
        'copy' => 'M9 9h11v11H9zM5 15V5a1 1 0 0 1 1-1h10',
        'download' => 'M12 4v11M7 10l5 5 5-5M5 20h14',
        'info' => 'M12 3a9 9 0 1 0 0 18a9 9 0 1 0 0-18M12 11v5M12 8h.01',
        'alert' => 'M12 3l10 18H2zM12 10v4M12 17h.01',
        'live' => 'M12 10a2 2 0 1 0 0 4a2 2 0 1 0 0-4M8 8a6 6 0 0 0 0 8M16 8a6 6 0 0 1 0 8M5 5a10 10 0 0 0 0 14M19 5a10 10 0 0 1 0 14',
        'map-pin' => 'M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12zM12 6.5a2.5 2.5 0 1 0 0 5a2.5 2.5 0 1 0 0-5',
        'mic' => 'M9 6a3 3 0 0 1 6 0v5a3 3 0 0 1-6 0zM5 11a7 7 0 0 0 14 0M12 18v3',
        'minus' => 'M5 12h14',
        'plus' => 'M12 5v14M5 12h14',
        'printer' => 'M6 9V3h12v6M6 14h12v7H6zM3 9h18v8h-3M6 17H3',
        'search' => 'M11 4a7 7 0 1 0 0 14a7 7 0 1 0 0-14M20 20l-4-4',
        'search-off' => 'M11 4a7 7 0 1 0 0 14a7 7 0 1 0 0-14M20 20l-4-4M8 11h6',
        'shield' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6zM9 12l2 2 4-4',
        'star' => 'M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z',
        'video' => 'M3 6h13v12H3zM16 10l5-3v10l-5-3z',
        'whatsapp' => 'M4 20l1.5-4A8 8 0 1 1 9 19z',
        'x' => 'M6 6l12 12M18 6L6 18',
    ];
@endphp

<svg aria-hidden="true" focusable="false" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
    {{ $attributes->class('shrink-0') }}><path d="{{ $paths[$name] ?? $paths['calendar'] }}" /></svg>
