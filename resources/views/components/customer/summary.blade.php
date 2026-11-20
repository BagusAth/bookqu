@props(['title' => 'Ringkasan reservasi', 'items' => [], 'total' => null])

<x-ui.panel :title="$title" {{ $attributes }}>
    @if ($items !== [])
        <x-ui.kv :items="$items" />
    @endif
    {{ $slot }}
    @if ($total !== null)
        <div class="mt-4 flex flex-wrap items-baseline justify-between gap-2 border-t border-bq-border pt-3 font-semibold">
            <span>Total</span>
            <span class="bq-num whitespace-nowrap">{{ $total }}</span>
        </div>
    @endif
</x-ui.panel>
