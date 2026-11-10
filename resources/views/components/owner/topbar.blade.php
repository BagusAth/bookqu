{{--
    Owner top bar (BQ-128, owner-portal.md §4.2): search, + Booking, the public booking page link,
    the notification bell and the account menu. Data comes from GetOwnerShell; behaviour from
    public/js/owner-ui.js (ownerSearch, ownerBell).
--}}
<header class="sticky top-0 z-40 flex h-16 items-center gap-2 border-b border-bq-border bg-bq-surface px-3 sm:gap-3 sm:px-5">
    <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-2 md:hidden" aria-label="Beranda {{ $shell['business'] }}">
        <img src="{{ asset('logo_icon.png') }}" alt="" class="h-8 w-8 rounded-lg" width="32" height="32">
    </a>

    @if ($shell['canSearch'])
        <div
            class="relative min-w-0 flex-1 sm:max-w-md"
            x-data="ownerSearch(@js(route('owner.search')))"
            x-on:keydown.escape="close()"
            x-on:click.outside="close()"
        >
            <label for="owner-search" class="sr-only">Cari booking atau pelanggan</label>
            <div class="flex items-center gap-2 rounded-lg border border-bq-border bg-bq-background px-3 focus-within:border-bq-primary focus-within:ring-2 focus-within:ring-bq-primary/20">
                <x-owner.icon name="search" class="h-4 w-4 text-bq-text-muted" />
                <input
                    id="owner-search"
                    type="search"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="owner-search-results"
                    x-bind:aria-expanded="open ? 'true' : 'false'"
                    autocomplete="off"
                    maxlength="100"
                    placeholder="Cari kode booking, nama, atau nomor HP"
                    x-model="query"
                    x-on:input.debounce.300ms="search()"
                    x-on:keydown.enter.prevent="openFirst()"
                    class="h-9 w-full min-w-0 border-0 bg-transparent p-0 text-sm text-bq-text placeholder:text-bq-text-subtle focus:outline-none focus:ring-0"
                >
            </div>

            <div
                id="owner-search-results"
                x-show="open"
                x-cloak
                role="listbox"
                aria-label="Hasil pencarian"
                class="absolute left-0 right-0 top-full z-50 mt-2 max-h-96 overflow-y-auto rounded-xl border border-bq-border bg-bq-surface p-2 shadow-lg"
            >
                <p x-show="loading" class="px-3 py-2 text-sm text-bq-text-muted" role="status">Mencari…</p>
                <p x-show="!loading && failed" class="px-3 py-2 text-sm text-bq-danger" role="alert">Pencarian gagal. Coba lagi.</p>
                <p x-show="!loading && !failed && results.bookings.length === 0 && results.customers.length === 0" class="px-3 py-2 text-sm text-bq-text-muted">Tidak ada yang cocok.</p>

                <template x-if="results.bookings.length">
                    <div>
                        <p class="px-3 pb-1 pt-2 text-xs font-medium text-bq-text-muted">Booking</p>
                        <template x-for="booking in results.bookings" x-bind:key="booking.code">
                            <button type="button" role="option" x-on:click="choose(booking, 'booking')" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm hover:bg-bq-background">
                                <span class="min-w-0">
                                    <span class="block font-medium text-bq-text" x-text="booking.code"></span>
                                    <span class="block truncate text-bq-text-muted"><span x-text="booking.customer"></span> · <span x-text="booking.date"></span> <span x-text="booking.time"></span></span>
                                </span>
                                <span class="shrink-0 text-xs text-bq-text-muted" x-text="booking.status.label"></span>
                            </button>
                        </template>
                    </div>
                </template>

                <template x-if="results.customers.length">
                    <div>
                        <p class="px-3 pb-1 pt-2 text-xs font-medium text-bq-text-muted">Pelanggan</p>
                        <template x-for="customer in results.customers" x-bind:key="customer.name + customer.phone">
                            <button type="button" role="option" x-on:click="choose(customer, 'customer')" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-bq-background">
                                <span class="block font-medium text-bq-text" x-text="customer.name"></span>
                                <span class="block text-bq-text-muted" x-text="customer.phone"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    @else
        <div class="flex-1"></div>
    @endif

    <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
        @if ($shell['canWalkIn'])
            <button
                type="button"
                id="owner-new-booking"
                x-data
                x-on:click="$dispatch('open-dialog', { id: 'owner-walkin-dialog', trigger: $el })"
                class="hidden items-center gap-1.5 rounded-lg bg-bq-primary px-3 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover md:inline-flex"
            >
                <x-owner.icon name="plus" class="h-4 w-4" />
                Booking
            </button>
        @endif

        @if ($shell['storefrontUrl'])
            <a href="{{ $shell['storefrontUrl'] }}" target="_blank" rel="noopener" class="hidden rounded-lg p-2 text-bq-text-muted hover:bg-bq-background hover:text-bq-text lg:inline-flex" title="Buka halaman booking">
                <x-owner.icon name="external" />
                <span class="sr-only">Buka halaman booking (tab baru)</span>
            </a>
        @endif

        <div
            id="owner-notification-bell"
            class="relative"
            x-data="ownerBell({ listUrl: @js(route('owner.notifications')), readAllUrl: @js(route('owner.notifications.read-all')), readUrl: @js(route('owner.notifications.read', ['id' => '__ID__'])), unread: {{ (int) $shell['unreadCount'] }} })"
            x-on:keydown.escape="open = false"
            x-on:click.outside="open = false"
        >
            <button type="button" x-on:click="toggle()" x-bind:aria-expanded="open ? 'true' : 'false'" aria-controls="owner-notification-list" class="relative rounded-lg p-2 text-bq-text-muted hover:bg-bq-background hover:text-bq-text">
                <x-owner.icon name="bell" />
                <span class="sr-only">Notifikasi</span>
                <span x-show="unread > 0" x-cloak class="absolute right-1 top-1 min-w-4 rounded-full bg-bq-danger px-1 text-center text-[11px] font-semibold leading-4 text-white tabular-nums" x-text="unread > 99 ? '99+' : unread"></span>
                <span class="sr-only" x-text="unread + ' belum dibaca'"></span>
            </button>

            <div id="owner-notification-list" x-show="open" x-cloak class="absolute right-0 top-full z-50 mt-2 w-80 max-w-[calc(100vw-1.5rem)] rounded-xl border border-bq-border bg-bq-surface shadow-lg">
                <div class="flex items-center justify-between border-b border-bq-border px-4 py-3">
                    <p class="text-sm font-semibold text-bq-text">Notifikasi</p>
                    <button type="button" x-show="unread > 0" x-on:click="markAllRead()" class="text-xs font-medium text-bq-primary hover:text-bq-primary-hover">Tandai semua dibaca</button>
                </div>
                <p x-show="loading" class="px-4 py-6 text-center text-sm text-bq-text-muted" role="status">Memuat…</p>
                <p x-show="!loading && items.length === 0" class="px-4 py-6 text-center text-sm text-bq-text-muted">Belum ada notifikasi.</p>
                <ul class="max-h-80 overflow-y-auto">
                    <template x-for="item in items" x-bind:key="item.id">
                        <li>
                            <button type="button" x-on:click="openItem(item)" class="block w-full border-b border-bq-border px-4 py-3 text-left hover:bg-bq-background" x-bind:class="item.is_read ? '' : 'bg-bq-primary-light/50'">
                                <span class="block text-sm font-medium text-bq-text" x-text="item.title"></span>
                                <span class="mt-0.5 block text-sm text-bq-text-muted" x-text="item.message"></span>
                                <span class="mt-0.5 block text-xs text-bq-text-subtle" x-text="item.created_at"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                <a href="{{ route('owner.notifications') }}" class="block px-4 py-3 text-center text-sm font-medium text-bq-primary hover:text-bq-primary-hover">Lihat semua</a>
            </div>
        </div>

        <div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false">
            <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open ? 'true' : 'false'" aria-controls="owner-account-menu" class="flex items-center gap-2 rounded-lg p-1 hover:bg-bq-background">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bq-primary-light text-sm font-semibold text-bq-primary" aria-hidden="true">{{ $shell['user']['initial'] }}</span>
                <span class="hidden text-left lg:block">
                    <span class="block max-w-[10rem] truncate text-sm font-medium text-bq-text">{{ $shell['user']['name'] }}</span>
                    <span class="block text-xs text-bq-text-muted">{{ $shell['user']['role'] }}</span>
                </span>
                <span class="sr-only">Menu akun</span>
            </button>
            <div id="owner-account-menu" x-show="open" x-cloak class="absolute right-0 top-full z-50 mt-2 w-60 rounded-xl border border-bq-border bg-bq-surface p-2 shadow-lg">
                <div class="border-b border-bq-border px-3 pb-2 pt-1">
                    <p class="truncate text-sm font-medium text-bq-text">{{ $shell['user']['name'] }}</p>
                    <p class="text-xs text-bq-text-muted">{{ $shell['user']['role'] }} · {{ $shell['business'] }}</p>
                </div>
                @if ($shell['canManageAccount'])
                    <a href="{{ route('owner.settings') }}#account-settings" class="mt-1 block rounded-lg px-3 py-2 text-sm text-bq-text hover:bg-bq-background">Pengaturan akun</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-bq-danger hover:bg-bq-danger-bg">
                        <x-owner.icon name="logout" class="h-4 w-4" />
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
