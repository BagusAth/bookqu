{{-- Pager (BQ-162, prototype ui.hal): "1–25 dari 270 booking" with previous and next. Pass a LengthAwarePaginator. --}}
@props(['paginator', 'noun' => 'baris'])

<div {{ $attributes->class('bq-pager') }}>
    <span>
        @if ($paginator->total() > 0)
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} {{ $noun }}
        @else
            0 {{ $noun }}
        @endif
    </span>
    @if ($paginator->hasPages())
        <span class="ml-auto flex gap-1">
            @if ($paginator->onFirstPage())
                <span class="bq-btn bq-btn-secondary bq-btn-sm bq-btn-icon !min-h-8 !min-w-8 opacity-50" aria-disabled="true"><x-owner.icon name="chevron-left" class="h-4 w-4" /><span class="sr-only">Sebelumnya</span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="bq-btn bq-btn-secondary bq-btn-sm bq-btn-icon !min-h-8 !min-w-8"><x-owner.icon name="chevron-left" class="h-4 w-4" /><span class="sr-only">Sebelumnya</span></a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="bq-btn bq-btn-secondary bq-btn-sm bq-btn-icon !min-h-8 !min-w-8"><x-owner.icon name="chevron-right" class="h-4 w-4" /><span class="sr-only">Berikutnya</span></a>
            @else
                <span class="bq-btn bq-btn-secondary bq-btn-sm bq-btn-icon !min-h-8 !min-w-8 opacity-50" aria-disabled="true"><x-owner.icon name="chevron-right" class="h-4 w-4" /><span class="sr-only">Berikutnya</span></span>
            @endif
        </span>
    @endif
</div>
