{{--
    Bottom sheet (design A §6, §8): handle, title, close button, body and an optional primary action (`footer`).
    `role="dialog"` + `aria-modal`; focus moves in, Tab stays inside, Esc closes and focus returns to the trigger.
    Behaviour comes from `ownerDialog` in public/js/owner-ui.js, so the page loads that script.
    Open with $dispatch('open-dialog', { id: '…', trigger: $el }); close with $dispatch('close-dialog', { id: '…' }).
--}}
@props(['id', 'title', 'description' => null])

<div id="{{ $id }}" x-data="ownerDialog(@js($id))" x-on:open-dialog.window="onOpenEvent($event)" x-on:close-dialog.window="onCloseEvent($event)" {{ $attributes->except(['x-data']) }}>
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="sf-sheet-backdrop" x-on:click="hide()" aria-hidden="true"></div>
    <div x-show="open" x-cloak
        x-transition:enter="transition-transform duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
        x-ref="panel" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title"
        @if ($description) aria-describedby="{{ $id }}-description" @endif
        tabindex="-1" x-on:keydown="trap($event)" x-on:keydown.escape.stop="hide()"
        class="sf-sheet">
        <span class="sf-sheet-handle" aria-hidden="true"></span>
        <div class="flex items-center justify-between gap-3 px-4 pt-3">
            <h2 id="{{ $id }}-title" class="text-lg font-bold">{{ $title }}</h2>
            <button type="button" class="sf-btn-secondary sf-btn-icon" aria-label="Tutup" x-on:click="hide()">
                <x-customer.icon name="x" :stroke="2" />
            </button>
        </div>
        @if ($description)
            <p id="{{ $id }}-description" class="px-4 pt-1 text-sm text-sf-muted">{{ $description }}</p>
        @endif
        <div class="px-4 pb-5 pt-3">
            {{ $slot }}
            @isset($footer)
                <div class="mt-3.5">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
