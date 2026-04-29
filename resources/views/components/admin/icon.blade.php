@props([
    'name' => 'square',
    'class' => 'h-5 w-5',
])

@php
$baseClass = $class;
@endphp

@if ($name === 'dashboard')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="8" height="8" rx="2"></rect>
        <rect x="13" y="3" width="8" height="5" rx="2"></rect>
        <rect x="13" y="10" width="8" height="11" rx="2"></rect>
        <rect x="3" y="13" width="8" height="8" rx="2"></rect>
    </svg>
@elseif ($name === 'calendar')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="5" width="18" height="16" rx="2"></rect>
        <line x1="16" y1="3" x2="16" y2="7"></line>
        <line x1="8" y1="3" x2="8" y2="7"></line>
        <line x1="3" y1="11" x2="21" y2="11"></line>
    </svg>
@elseif ($name === 'calendar-days')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
        <line x1="8" y1="2.5" x2="8" y2="6"></line>
        <line x1="16" y1="2.5" x2="16" y2="6"></line>
        <line x1="3" y1="9" x2="21" y2="9"></line>
        <line x1="7" y1="13" x2="7.01" y2="13"></line>
        <line x1="12" y1="13" x2="12.01" y2="13"></line>
        <line x1="17" y1="13" x2="17.01" y2="13"></line>
    </svg>
@elseif ($name === 'book-check')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4z"></path>
        <path d="m9 13 2 2 4-4"></path>
    </svg>
@elseif ($name === 'bar-chart')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" y1="20" x2="20" y2="20"></line>
        <rect x="6" y="11" width="3" height="7" rx="1"></rect>
        <rect x="11" y="7" width="3" height="11" rx="1"></rect>
        <rect x="16" y="4" width="3" height="14" rx="1"></rect>
    </svg>
@elseif ($name === 'credit-card')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
        <line x1="3" y1="10" x2="21" y2="10"></line>
        <line x1="7" y1="15" x2="11" y2="15"></line>
    </svg>
@elseif ($name === 'settings')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M19.4 15a1.7 1.7 0 0 0 .33 1.87l.1.1a2 2 0 0 1-2.83 2.83l-.1-.1A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.3V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1.4-1.6 1.7 1.7 0 0 0-1.6.4l-.1.1A2 2 0 1 1 3.7 17l.1-.1a1.7 1.7 0 0 0 .4-1.6 1.7 1.7 0 0 0-1-1.4H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.6-1.4 1.7 1.7 0 0 0-.4-1.6l-.1-.1A2 2 0 1 1 6.6 3.7l.1.1a1.7 1.7 0 0 0 1.6.4A1.7 1.7 0 0 0 9.7 3V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1.4 1.6 1.7 1.7 0 0 0 1.6-.4l.1-.1A2 2 0 1 1 20.3 6l-.1.1a1.7 1.7 0 0 0-.4 1.6 1.7 1.7 0 0 0 1 1.4H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1.4z"></path>
    </svg>
@elseif ($name === 'bell')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"></path>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
    </svg>
@elseif ($name === 'info')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="9"></circle>
        <line x1="12" y1="10" x2="12" y2="16"></line>
        <line x1="12" y1="7" x2="12.01" y2="7"></line>
    </svg>
@elseif ($name === 'trending')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="3 17 9 11 13 15 21 7"></polyline>
        <polyline points="14 7 21 7 21 14"></polyline>
    </svg>
@elseif ($name === 'arrow-right')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <line x1="5" y1="12" x2="19" y2="12"></line>
        <polyline points="13 6 19 12 13 18"></polyline>
    </svg>
@elseif ($name === 'chevron-right')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="9 6 15 12 9 18"></polyline>
    </svg>
@elseif ($name === 'banknote')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="6" width="18" height="12" rx="2"></rect>
        <circle cx="12" cy="12" r="3"></circle>
        <line x1="3" y1="9" x2="6" y2="9"></line>
        <line x1="18" y1="15" x2="21" y2="15"></line>
    </svg>
@elseif ($name === 'puzzle')
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 3a2 2 0 1 1 4 0v2h2a2 2 0 1 1 0 4h-2v2a2 2 0 1 1-4 0V9H7a2 2 0 1 1 0-4h2z"></path>
        <path d="M5 13h4v4a2 2 0 1 0 4 0v-4h4"></path>
    </svg>
@else
    <svg class="{{ $baseClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="4" y="4" width="16" height="16" rx="3"></rect>
    </svg>
@endif
