{{--
    Accessible side panel (BQ-129, owner-portal.md §9): right side on desktop, full height on phones.
    Same behaviour as x-owner.dialog. Pass x-data to replace the default ownerDialog behaviour
    (the booking and customer panels use ownerPanel).
--}}
@props([
    'id',
    'title',
    'behaviour' => null,
])

<div
    id="{{ $id }}"
    x-data="{{ $behaviour ?? 'ownerDialog('.\Illuminate\Support\Js::from($id).')' }}"
    x-on:open-dialog.window="onOpenEvent($event)"
    x-on:close-dialog.window="onCloseEvent($event)"
    {{ $attributes }}
>
    <div x-show="open" x-cloak class="fixed inset-0 z-[90] bg-bq-ink/40" x-on:click="hide()" aria-hidden="true"></div>

    <div
        x-show="open"
        x-cloak
        x-ref="panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        tabindex="-1"
        x-on:keydown="trap($event)"
        x-on:keydown.escape.stop="hide()"
        class="fixed inset-y-0 right-0 z-[95] flex w-full max-w-lg flex-col border-l border-bq-border bg-bq-surface shadow-xl focus:outline-none"
    >
        <div class="flex items-start justify-between gap-4 border-b border-bq-border px-5 py-4">
            <h2 id="{{ $id }}-title" class="text-base font-semibold text-bq-text">
                @isset($heading){{ $heading }}@else{{ $title }}@endisset
            </h2>
            <button type="button" x-on:click="hide()" aria-label="Tutup" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-bq-background hover:text-bq-text">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4 text-sm text-bq-text">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex flex-wrap gap-2 border-t border-bq-border px-5 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
