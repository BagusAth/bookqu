{{-- Skeleton (BQ-161): placeholder lines while content loads. --}}
@props(['lines' => 3])

<div {{ $attributes->class('space-y-3') }} aria-hidden="true">
    @for ($i = 0; $i < $lines; $i++)
        <div class="h-3 animate-pulse rounded bg-bq-surface-2" style="width: {{ [92, 70, 84, 60][$i % 4] }}%"></div>
    @endfor
</div>
