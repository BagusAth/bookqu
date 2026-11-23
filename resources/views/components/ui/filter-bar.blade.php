{{--
    Filter bar (BQ-161): the `search` slot is always visible; the default slot holds the other filters, shown
    inline from 768 px and behind a "Filter (n)" toggle on phones so the list starts on the first screen.
    `active` is the number of filters in use (counted by the caller from the request); `actions` holds links
    such as reset or export. Wrap it in the page's GET form.
--}}
@props(['active' => 0])

<div x-data="{ more: false }" {{ $attributes->class('bq-panel p-3 sm:p-4') }}>
    <div class="flex flex-wrap items-end gap-3">
        @isset($search)
            <div class="min-w-0 flex-1 md:w-72 md:flex-none">{{ $search }}</div>
        @endisset
        <button type="button" class="bq-btn bq-btn-secondary md:hidden" x-on:click="more = !more" x-bind:aria-expanded="more ? 'true' : 'false'">
            <x-owner.icon name="filter" class="h-4 w-4" />
            Filter @if ($active > 0)<span class="bq-count">{{ $active }}</span>@endif
        </button>
        <div class="contents max-md:hidden" x-bind:class="more ? 'max-md:!contents' : ''">
            {{ $slot }}
        </div>
    </div>
    @isset($actions)
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-bq-border pt-3 text-sm" x-bind:class="more ? '' : 'max-md:hidden'">{{ $actions }}</div>
    @endisset
</div>
