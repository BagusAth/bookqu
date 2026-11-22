{{--
    Coloured note (design A §2.3, §6): status colours never follow the brand. Tones: info, success, warning,
    danger, and brand (the light brand tint for help boxes). Pass role="status" or role="alert" when it
    announces a change.
--}}
@props(['tone' => 'info', 'title' => null, 'icon' => null])

@php
    $icon ??= match ($tone) {
        'success' => 'check',
        'warning', 'danger' => 'alert',
        default => 'info',
    };
@endphp

<div data-tone="{{ $tone }}" {{ $attributes->class('sf-note') }}>
    <x-customer.icon :name="$icon" :size="18" :stroke="2" class="mt-px" />
    <div class="min-w-0">
        @if ($title)
            <p class="font-bold">{{ $title }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
        @endif
    </div>
</div>
