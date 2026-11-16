{{--
    Button (BQ-161): the one button of the owner portal.
    variant: primary | secondary | ghost | danger | danger-solid | accent; size: md | sm; icon: an x-owner.icon name.
    Pass `href` to render a link. One primary button per view (owner-portal.md §9 page header rule).
--}}
@props([
    'variant' => 'secondary',
    'size' => 'md',
    'icon' => null,
    'iconOnly' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $classes = 'bq-btn bq-btn-'.$variant.($size === 'sm' ? ' bq-btn-sm' : '').($iconOnly ? ' bq-btn-icon' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-owner.icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-owner.icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </button>
@endif
