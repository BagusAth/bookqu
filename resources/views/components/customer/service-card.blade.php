{{--
    Favourite service card (design A §6, §7.1): photo or icon on a light brand tile, "Favorit" label, category ·
    duration, name, price and an action. Phone: put it in a `sf-scroll-row` with a fixed width (290 px).
    Tablet: compact, three columns. Desktop: three columns with a two-line description.
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
    'favorite' => true,
    'tone' => 0,
])

@if ($href)
<a href="{{ $href }}" {{ $attributes->class('sf-card group block overflow-hidden') }}>
@else
<button type="submit" name="service_id" value="{{ $value }}" aria-label="Pilih {{ $name }}" {{ $attributes->class('sf-card group block overflow-hidden w-full cursor-pointer text-left') }}>
@endif
    <span @class([
        'relative flex h-[168px] items-center justify-center md:h-[120px] lg:h-[168px]',
        'bg-sf-tint' => ! $image && $tone % 2 === 0,
        'bg-sf-tint-strong' => ! $image && $tone % 2 === 1,
        'bg-sf-divider' => $image,
    ])>
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
        @else
            <span class="grid size-16 place-items-center rounded-sf-card-small bg-sf-surface text-sf-primary-text md:size-14 lg:size-16">
                <x-customer.icon :name="$icon" :size="30" :stroke="1.5" />
            </span>
        @endif
        @if ($favorite)
            <span class="absolute left-3 top-3 rounded-sf-chip bg-sf-surface px-2.5 py-1 text-xs font-bold text-sf-text">Favorit</span>
        @endif
    </span>
    <span class="block px-4 pb-4 pt-3.5 md:px-3.5 md:pb-3.5 md:pt-3 lg:px-[18px] lg:pb-[18px] lg:pt-4">
        @if ($category || $duration)
            <span class="block text-xs font-semibold text-sf-muted md:hidden lg:block">{{ collect([$category, $duration])->filter()->implode(' · ') }}</span>
        @endif
        <span class="mt-1 line-clamp-2 block min-h-11 text-base font-bold leading-[22px] md:mt-0 md:min-h-10 md:text-[15px] md:leading-5 lg:mt-1 lg:min-h-[46px] lg:text-[17px] lg:leading-[23px]">{{ $name }}</span>
        @if (filled($description))
            <span class="mt-1.5 hidden text-[13px] leading-[19px] text-sf-muted lg:line-clamp-2">{{ $description }}</span>
        @endif
        <span class="mt-3 flex items-center justify-between gap-3 md:mt-2 lg:mt-3.5">
            <span class="hidden text-[13px] text-sf-muted md:inline lg:hidden">{{ $duration }}</span>
            <span class="sf-num text-[17px] font-bold md:text-[13px] lg:text-lg">{{ $price }}</span>
            <span class="sf-btn sf-btn-sm md:hidden lg:inline-flex" aria-hidden="true"><span class="lg:hidden">Pilih</span><span class="hidden lg:inline">Pilih jadwal</span></span>
        </span>
    </span>
@if ($href)</a>@else</button>@endif
