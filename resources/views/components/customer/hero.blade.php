{{--
    Storefront hero (design A §5, §6, §7.1). Brand colour with the ring ornament, or the business banner under a
    dark scrim. Phone: compact, h1 is the business name. Tablet: two columns with a location card. Desktop: 50 px
    title "Reservasi di {bisnis}" with a raised location card. Every value is prepared by the controller.
--}}
@props([
    'name',
    'title' => null,
    'description' => null,
    'logo' => null,
    'initials' => null,
    'banner' => null,
    'eyebrow' => 'Reservasi online',
    'badges' => [],
    'area' => null,
    'address' => null,
    'mapsUrl' => null,
    'phoneLabel' => null,
    'phoneUrl' => null,
    'waUrl' => null,
    'homeUrl' => null,
])

@php
    $title ??= 'Reservasi di '.$name;
    $initials ??= mb_strtoupper(mb_substr($name, 0, 2));
@endphp

<header {{ $attributes->class('sf-hero no-print') }} @if ($banner) data-banner @endif>
    @if ($banner)
        <img src="{{ $banner }}" alt="" class="sf-hero-banner">
        <span class="sf-hero-scrim" aria-hidden="true"></span>
    @endif
    <x-customer.hero-ornament class="-right-[110px] -top-[110px] size-[260px] md:-right-[150px] md:-top-[160px] md:size-[420px] lg:-right-[120px] lg:-top-[250px] lg:size-[620px]" />

    <div class="mx-auto max-w-[1240px] px-4 pb-[22px] pt-3 md:px-8 md:pb-7 md:pt-[18px] lg:px-14 lg:pb-11 lg:pt-5">
        <div class="flex items-center gap-3">
            <a href="{{ $homeUrl ?? '#' }}" class="shrink-0 rounded-sf-card-small" aria-label="Beranda {{ $name }}">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="size-10 shrink-0 rounded-sf-card-small bg-sf-surface object-cover md:size-11">
                @else
                    <span aria-hidden="true" class="grid size-10 shrink-0 place-items-center rounded-sf-card-small bg-sf-surface text-[15px] font-bold text-sf-primary-text md:size-11 md:text-base">{{ $initials }}</span>
                @endif
            </a>
            <span class="grow text-xs font-semibold text-sf-hero-soft md:hidden">{{ $eyebrow }}</span>
            <span class="hidden grow truncate text-base font-bold md:block" title="{{ $name }}">{{ $name }}</span>
            @if ($waUrl)
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Bantuan via WhatsApp" class="sf-btn-on-primary sf-btn-icon md:w-auto md:px-4 md:text-sm">
                    <x-customer.icon name="whatsapp" :size="18" />
                    <span class="hidden md:inline lg:hidden">Bantuan</span>
                    <span class="hidden lg:inline">Bantuan via WhatsApp</span>
                </a>
            @endif
        </div>

        <div class="mt-4 md:mt-7 md:grid md:grid-cols-[minmax(0,1fr)_280px] md:items-end md:gap-6 lg:mt-10 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-12">
            <div>
                <h1 class="text-[26px] font-bold leading-8 tracking-[-0.4px] md:text-4xl md:leading-[42px] md:tracking-[-0.6px] lg:max-w-[680px] lg:text-[50px] lg:leading-[56px] lg:tracking-[-1px]">
                    <span class="md:hidden">{{ $name }}</span><span class="hidden md:inline">{{ $title }}</span>
                </h1>
                @if (filled($description))
                    <p class="mt-2 line-clamp-2 text-sm leading-[21px] text-sf-hero-soft md:mt-2.5 md:line-clamp-none md:text-[15px] md:leading-[23px] lg:mt-3.5 lg:max-w-[600px] lg:text-[17px] lg:leading-[27px]">{{ $description }}</p>
                @endif
                @if ($badges !== [] || $area)
                    <ul class="mt-3 flex flex-wrap gap-2 md:hidden" aria-label="Keterangan">
                        @foreach ($badges as $badge)
                            <li class="rounded-sf-chip bg-sf-hero-faint px-2.5 py-1.5 text-xs font-semibold">{{ $badge }}</li>
                        @endforeach
                        @if ($area)
                            <li class="rounded-sf-chip bg-sf-hero-faint px-2.5 py-1.5 text-xs font-semibold">{{ $area }}</li>
                        @endif
                    </ul>
                @endif
                @if ($badges !== [])
                    <ul class="mt-[22px] hidden gap-6 text-sm font-semibold lg:flex" aria-label="Keterangan">
                        @foreach ($badges as $badge)
                            <li>{{ $badge }}</li>
                        @endforeach
                    </ul>
                @endif
                {{ $slot }}
            </div>

            @if ($address)
                <div class="mt-6 hidden rounded-sf-card-small bg-sf-hero-faint px-4 py-3.5 text-[13px] leading-[19px] md:mt-0 md:block lg:rounded-sf-card lg:bg-sf-surface lg:p-5 lg:text-sf-text lg:shadow-sf-raised">
                    <p class="hidden text-[13px] text-sf-muted lg:block">Lokasi</p>
                    <p class="font-bold lg:mt-0.5 lg:text-[15px] lg:leading-[22px]">{{ $address }}</p>
                    <div class="mt-2.5 grid gap-2 lg:mt-3.5 lg:grid-cols-2">
                        @if ($mapsUrl)
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary w-full text-sm">Petunjuk arah</a>
                        @endif
                        @if ($phoneLabel && $phoneUrl)
                            <a href="{{ $phoneUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary sf-num hidden w-full text-sm lg:inline-flex">{{ $phoneLabel }}</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</header>
