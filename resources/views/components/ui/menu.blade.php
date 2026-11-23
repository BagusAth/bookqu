@props(['id', 'label' => 'Lainnya', 'icon' => 'more'])

<div x-data="ownerMenu" x-on:click.outside="close()" {{ $attributes->class('relative inline-flex') }}>
    <x-ui.button icon-only variant="ghost" :icon="$icon" x-ref="trigger" :aria-label="$label" :title="$label"
        aria-haspopup="menu" :aria-controls="$id" x-bind:aria-expanded="open"
        x-on:click="open ? close() : show()" x-on:keydown.down.prevent="show()" x-on:keydown.up.prevent="show(true)" />
    <div id="{{ $id }}" x-ref="items" x-show="open" x-cloak role="menu" aria-label="{{ $label }}"
        x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)"
        x-on:keydown.home.prevent="focusItem()" x-on:keydown.end.prevent="focusItem(true)"
        x-on:keydown.escape.stop.prevent="close(true)" x-on:focusout="leave($event)"
        x-on:click="if ($event.target.closest('[role=menuitem]')) close(true)"
        class="absolute right-0 top-full z-30 mt-1 grid min-w-44 rounded-bq-panel border border-bq-border bg-bq-surface p-1 shadow-bq [&_[role=menuitem]]:rounded-bq-control [&_[role=menuitem]]:px-3 [&_[role=menuitem]]:py-2 [&_[role=menuitem]]:text-left [&_[role=menuitem]]:hover:bg-bq-background">
        {{ $slot }}
    </div>
</div>
