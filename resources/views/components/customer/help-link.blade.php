@props(['href' => null, 'label' => 'Bantuan lewat WhatsApp'])

@if ($href)
    <x-ui.button :href="$href" icon="whatsapp" target="_blank" rel="noopener noreferrer" :aria-label="$label" {{ $attributes->class('min-h-11 min-w-11') }}>
        <span class="hidden sm:inline">{{ $slot->isEmpty() ? 'Bantuan' : $slot }}</span>
        @if ($slot->isNotEmpty())
            <span class="sm:hidden">{{ $slot }}</span>
        @endif
    </x-ui.button>
@endif
