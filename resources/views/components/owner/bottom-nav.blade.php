{{--
    Phone navigation (BQ-128, owner-portal.md §4.3): below 768 px the sidebar is replaced by
    Beranda, Kalender, + Walk-in, Booking and Lainnya (BQ-161: the centre button is the walk-in action, so
    "Booking" appears once). "Lainnya" opens a sheet with the remaining
    menus the role may open, in sidebar order.
--}}
@php
    $primary = collect($shell['phonePrimary'])->keyBy('id');
    $navItem = 'flex min-h-14 flex-1 flex-col items-center justify-center gap-0.5 py-1.5 text-[11px] font-semibold';
@endphp

<nav id="owner-bottom-nav" aria-label="Navigasi utama" class="fixed inset-x-0 bottom-0 z-40 flex border-t border-bq-border bg-bq-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-sm md:hidden">
    @foreach (['dashboard', 'calendar'] as $id)
        @if ($item = $primary->get($id))
            <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif class="{{ $navItem }} {{ $item['active'] ? 'text-bq-primary' : 'text-bq-text-muted' }}">
                <x-owner.icon :name="$item['icon']" />
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach

    @if ($shell['canWalkIn'])
        <button type="button" x-data x-on:click="$dispatch('open-dialog', { id: 'owner-walkin-dialog', trigger: $el })" class="{{ $navItem }} text-bq-primary">
            <span class="-mt-5 flex h-12 w-12 items-center justify-center rounded-full bg-bq-primary text-white shadow-bq ring-4 ring-bq-accent"><x-owner.icon name="plus" class="h-5 w-5" /></span>
            <span class="sr-only">Catat booking</span> Walk-in
        </button>
    @endif

    @if ($item = $primary->get('bookings'))
        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif class="{{ $navItem }} {{ $item['active'] ? 'text-bq-primary' : 'text-bq-text-muted' }}">
            <span class="relative">
                <x-owner.icon :name="$item['icon']" />
                @if ($item['badge'])
                    <span class="absolute -right-3 -top-1 min-w-4 rounded-full bg-bq-accent px-1 text-center text-[10px] font-bold leading-4 text-bq-ink" title="{{ $item['badge']['label'] ?? 'Booking perlu tindakan' }}">{{ $item['badge']['text'] }}</span>
                @endif
            </span>
            {{ $item['label'] }}
        </a>
    @endif

    @if (count($shell['phoneMore']) > 0)
        <button type="button" x-data x-on:click="$dispatch('open-dialog', { id: 'owner-more-sheet', trigger: $el })" class="{{ $navItem }} text-bq-text-muted">
            <x-owner.icon name="more" />
            Lainnya
        </button>
    @endif
</nav>

@if (count($shell['phoneMore']) > 0)
    <x-owner.dialog id="owner-more-sheet" title="Lainnya" class="md:hidden">
        <ul class="-mx-2 space-y-0.5">
            @foreach ($shell['phoneMore'] as $item)
                <li>
                    @if ($item['locked'])
                        <button type="button" x-on:click="hide(); $dispatch('open-upgrade', { feature: @js($item['label']) }); $dispatch('open-dialog', { id: 'owner-upgrade-dialog' })" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-bq-text-muted hover:bg-bq-surface-2">
                            <x-owner.icon :name="$item['icon']" />
                            <span class="flex-1">{{ $item['label'] }}</span>
                            <x-owner.icon name="lock" class="h-4 w-4" />
                        </button>
                    @else
                        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ $item['active'] ? 'bg-bq-primary-light font-semibold text-bq-primary' : 'text-bq-text hover:bg-bq-surface-2' }}">
                            <x-owner.icon :name="$item['icon']" />
                            <span class="flex-1">{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span class="bq-count">{{ $item['badge']['text'] }}</span>
                            @endif
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($shell['storefrontUrl'])
            <a href="{{ $shell['storefrontUrl'] }}" target="_blank" rel="noopener" class="mt-3 flex items-center gap-2 border-t border-bq-border pt-3 text-sm text-bq-primary">
                <x-owner.icon name="external" class="h-4 w-4" /> Buka halaman booking
            </a>
        @endif
    </x-owner.dialog>
@endif
