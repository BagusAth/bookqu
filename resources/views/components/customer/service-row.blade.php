{{--
    Service row (design A §6, §7.1). Phone: a list row with icon, name, duration · category, price and chevron.
    Tablet and desktop: a card in a two-column grid with a two-line description.
    Without `href`, pass `value` to render a submit button (`name="service_id"`) inside the selection form.
--}}
@props([
    'href' => null,
    'value' => null,
    'name',
    'price',
    'duration' => null,
    'category' => null,
    'description' => null,
    'image' => null,
    'icon' => 'calendar',
    'tone' => 0,
])

@if ($href)
<a href="{{ $href }}" {{ $attributes->class('sf-card-small flex items-center gap-3.5 p-3 md:items-stretch md:p-3.5 lg:gap-4 lg:p-4') }}>
@else
<button type="submit" name="service_id" value="{{ $value }}" aria-label="Pilih {{ $name }}" {{ $attributes->class('sf-card-small flex items-center gap-3.5 p-3 md:items-stretch md:p-3.5 lg:gap-4 lg:p-4 w-full cursor-pointer text-left') }}>
@endif
    <span @class([
        'relative grid size-16 shrink-0 place-items-center overflow-hidden rounded-sf-card-small text-sf-primary-text md:size-[88px]',
        'bg-sf-tint' => ! $image && $tone % 2 === 0,
        'bg-sf-tint-strong' => ! $image && $tone % 2 === 1,
        'bg-sf-divider' => $image,
    ])>
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
        @else
            <x-customer.icon :name="$icon" :size="26" :stroke="1.5" class="md:size-7" />
        @endif
    </span>
    <span class="flex min-w-0 grow flex-col">
        <span class="text-[15px] font-bold leading-5">{{ $name }}</span>
        <span class="mt-0.5 text-[13px] text-sf-muted md:hidden">{{ collect([$duration, $category])->filter()->implode(' · ') }}</span>
        <span class="sf-num mt-1 text-[15px] font-bold text-sf-primary-text md:hidden">{{ $price }}</span>
        @if (filled($description))
            <span class="mt-1 hidden text-[13px] leading-[19px] text-sf-muted md:line-clamp-2">{{ $description }}</span>
        @endif
        <span class="mt-auto hidden justify-between gap-3 pt-2 text-[13px] md:flex">
            <span class="text-sf-muted">{{ $duration }}</span>
            <span class="sf-num text-[15px] font-bold text-sf-primary-text">{{ $price }}</span>
        </span>
    </span>
    <x-customer.icon name="chevron-right" :stroke="2" class="text-sf-muted md:hidden" />
@if ($href)</a>@else</button>@endif
