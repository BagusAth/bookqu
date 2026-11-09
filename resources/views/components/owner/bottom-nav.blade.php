{{--
    Phone navigation (BQ-128, owner-portal.md §4.3): below 768 px the sidebar is replaced by
    Beranda, Kalender, + Booking, Booking and Lainnya. "Lainnya" opens a sheet with the remaining
    menus the role may open, in sidebar order.
--}}
@php
    $primary = collect($shell['phonePrimary'])->keyBy('id');
    $navItem = 'flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium';
@endphp

<nav id="owner-bottom-nav" aria-label="Navigasi utama" class="fixed inset-x-0 bottom-0 z-40 flex border-t border-bq-border bg-bq-surface pb-[env(safe-area-inset-bottom)] md:hidden">
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
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bq-primary text-white"><x-owner.icon name="plus" class="h-4 w-4" /></span>
            <span class="sr-only">Tambah</span> Booking
        </button>
    @endif

    @if ($item = $primary->get('bookings'))
        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif class="{{ $navItem }} {{ $item['active'] ? 'text-bq-primary' : 'text-bq-text-muted' }}">
            <x-owner.icon :name="$item['icon']" />
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
                        <button type="button" x-on:click="hide(); $dispatch('open-upgrade', { feature: @js($item['label']) }); $dispatch('open-dialog', { id: 'owner-upgrade-dialog' })" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-bq-text-muted hover:bg-bq-background">
                            <x-owner.icon :name="$item['icon']" />
                            <span class="flex-1">{{ $item['label'] }}</span>
                            <x-owner.icon name="lock" class="h-4 w-4" />
                        </button>
                    @else
                        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ $item['active'] ? 'bg-bq-primary-light text-bq-primary' : 'text-bq-text hover:bg-bq-background' }}">
                            <x-owner.icon :name="$item['icon']" />
                            <span class="flex-1">{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span class="rounded-full bg-bq-warning-bg px-2 py-0.5 text-xs font-medium text-bq-warning">{{ $item['badge']['text'] }}</span>
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
