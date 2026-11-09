{{--
    Accessible dialog (BQ-129, owner-portal.md §9). Open with
    $dispatch('open-dialog', { id: '…', trigger: $el }); close with $dispatch('close-dialog', { id: '…' }).
    Focus moves in, Tab stays inside, Escape closes, focus returns to the trigger (public/js/owner-ui.js).
--}}
@props([
    'id',
    'title',
    'description' => null,
    'size' => 'md',
])

@php
    $width = match ($size) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-2xl',
        default => 'max-w-md',
    };
@endphp

<div
    id="{{ $id }}"
    x-data="ownerDialog(@js($id))"
    x-on:open-dialog.window="onOpenEvent($event)"
    x-on:close-dialog.window="onCloseEvent($event)"
    {{ $attributes->except(['x-data']) }}
>
    <div x-show="open" x-cloak class="fixed inset-0 z-[100] bg-bq-ink/40" x-on:click="hide()" aria-hidden="true"></div>

    <div x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-end justify-center p-0 sm:items-center sm:p-4 pointer-events-none">
        <div
            x-ref="panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $id }}-title"
            @if ($description) aria-describedby="{{ $id }}-description" @endif
            tabindex="-1"
            x-on:keydown="trap($event)"
            x-on:keydown.escape.stop="hide()"
            class="pointer-events-auto w-full {{ $width }} max-h-[90vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl border border-bq-border bg-bq-surface shadow-xl focus:outline-none"
        >
            <div class="flex items-start justify-between gap-4 border-b border-bq-border px-5 py-4">
                <div>
                    <h2 id="{{ $id }}-title" class="text-base font-semibold text-bq-text">{{ $title }}</h2>
                    @if ($description)
                        <p id="{{ $id }}-description" class="mt-1 text-sm text-bq-text-muted">{{ $description }}</p>
                    @endif
                </div>
                <button type="button" x-on:click="hide()" aria-label="Tutup" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-bq-background hover:text-bq-text">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-5 py-4 text-sm text-bq-text">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-col-reverse gap-2 border-t border-bq-border px-5 py-4 sm:flex-row sm:justify-end">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
