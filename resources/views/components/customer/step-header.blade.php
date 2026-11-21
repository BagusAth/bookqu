{{--
    Booking step header (design A §6, §7.2): back button, "Langkah n dari 3", screen title and a three-part
    progress bar on the brand colour. Tablet and desktop also show the chosen service with "Ganti".
    `overlap` leaves room for a card that sits on the header (phone service card, desktop panels).
    The `aside` slot replaces the service card, for example the From → To comparison when rescheduling.
--}}
@props([
    'title',
    'step' => null,
    'backUrl' => null,
    'backLabel' => 'Kembali',
    'service' => null,
    'serviceMeta' => null,
    'serviceIcon' => 'calendar',
    'changeUrl' => null,
    'overlap' => false,
])

<header {{ $attributes->class('sf-hero no-print') }}>
    <x-customer.hero-ornament class="-right-[120px] -top-[150px] size-[300px]" />
    <div class="mx-auto max-w-[1240px] px-4 pt-3 md:px-8 md:pt-[18px] lg:px-14 lg:pt-5">
        <div class="flex items-center gap-3">
            @if ($backUrl)
                <a href="{{ $backUrl }}" aria-label="{{ $backLabel }}" class="sf-btn-on-primary sf-btn-icon">
                    <x-customer.icon name="chevron-left" :stroke="2" />
                </a>
            @endif
            <div class="min-w-0 grow">
                @if ($step)
                    <p class="text-xs font-semibold text-sf-hero-soft">Langkah {{ $step }} dari 3</p>
                @endif
                <h1 class="text-lg font-bold tracking-[-0.3px] md:text-2xl">{{ $title }}</h1>
            </div>
            @isset($aside)
                <div class="hidden md:block">{{ $aside }}</div>
            @elseif ($service)
                <div class="hidden items-center gap-3 rounded-sf-card-small bg-sf-hero-faint py-2 pl-2 pr-3.5 md:flex">
                    <span class="grid size-10 shrink-0 place-items-center rounded-sf-card-small bg-sf-surface text-sf-primary-text">
                        <x-customer.icon :name="$serviceIcon" :stroke="1.6" />
                    </span>
                    <span class="min-w-0">
                        <span class="block max-w-64 truncate text-sm font-bold" title="{{ $service }}">{{ $service }}</span>
                        @if ($serviceMeta)
                            <span class="block text-xs text-sf-hero-soft">{{ $serviceMeta }}</span>
                        @endif
                    </span>
                    @if ($changeUrl)
                        <a href="{{ $changeUrl }}" class="ml-1.5 py-3 text-[13px] font-bold underline">Ganti</a>
                    @endif
                </div>
            @endif
        </div>
        @if ($step)
            <div class="sf-progress mt-3.5 max-w-[420px]" aria-hidden="true">
                @foreach ([1, 2, 3] as $part)
                    <span @if ($part < $step) data-done @elseif ($part === (int) $step) data-current @endif></span>
                @endforeach
            </div>
        @endif
        {{ $slot }}
    </div>
    <div @class(['h-5', 'h-9 lg:h-14' => $overlap])></div>
</header>
