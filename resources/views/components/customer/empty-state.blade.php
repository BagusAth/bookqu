{{--
    Empty or error box (design A §6, §7.1, §7.9): icon, optional small error code, title, explanation and one or
    two actions in the `actions` slot. Tones: brand (empty search), warning (bookings closed), danger (errors).
--}}
@props(['title', 'description' => null, 'icon' => 'search-off', 'tone' => 'brand', 'code' => null, 'headingLevel' => 2])

@php
    $tag = 'h'.max(1, min(6, (int) $headingLevel));
@endphp

<section role="status" {{ $attributes->class('sf-card px-6 py-8 text-center') }}>
    <span @class([
        'mx-auto grid size-14 place-items-center rounded-sf-card-small',
        'bg-sf-tint text-sf-primary-text' => $tone === 'brand',
        'bg-sf-warning-bg text-sf-warning' => $tone === 'warning',
        'bg-sf-danger-bg text-sf-danger' => $tone === 'danger',
    ])>
        <x-customer.icon :name="$icon" :size="26" />
    </span>
    @if ($code)
        <p class="sf-num mt-3.5 text-xs font-semibold text-sf-muted">{{ $code }}</p>
    @endif
    <{{ $tag }} @class(['text-lg font-bold', 'mt-3.5' => ! $code, 'mt-1' => $code])>{{ $title }}</{{ $tag }}>
    @if ($description)
        <p class="mx-auto mt-1.5 max-w-[340px] text-sm leading-[21px] text-sf-muted">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-4 flex flex-wrap justify-center gap-2">{{ $actions }}</div>
    @endisset
</section>
