@props(['label', 'tone' => 'neutral'])

<x-ui.status-dot :tone="$tone" {{ $attributes }}>{{ $label }}</x-ui.status-dot>
