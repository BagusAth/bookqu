@props(['href' => null, 'label' => 'Bantuan lewat WhatsApp'])

@if ($href)
    <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}" {{ $attributes->class('sf-btn-secondary sf-btn-sm min-w-11') }}>
        <x-customer.icon name="whatsapp" :size="18" />
        <span class="hidden sm:inline">{{ $slot->isEmpty() ? 'Bantuan' : $slot }}</span>
        @if ($slot->isNotEmpty())
            <span class="sm:hidden">{{ $slot }}</span>
        @endif
    </a>
@endif
