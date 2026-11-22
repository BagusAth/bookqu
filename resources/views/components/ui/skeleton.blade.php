{{-- Skeleton (BQ-161): placeholder lines while content loads. --}}
@props(['lines' => 3, 'label' => 'Memuat'])

<div {{ $attributes->class('space-y-3') }} role="status">
    <span class="sr-only">{{ $label }}</span>
    @for ($i = 0; $i < $lines; $i++)
        <div aria-hidden="true" class="h-3 animate-pulse rounded-bq-control bg-bq-surface-2" style="width: {{ [92, 70, 84, 60][$i % 4] }}%"></div>
    @endfor
</div>
