@extends('customer.layouts.booking-shell')

@section('title', 'Reservasi online')
@section('immersive', true)
@if ($pendingCheckout)
    @section('bottom_bar', 'always')
@endif

@php
    // Display data only: counts per category for the chips, favourites from `is_popular` (design A §7.1).
    $categoryChips = collect([['key' => 'all', 'label' => 'Semua layanan', 'short' => 'Semua', 'count' => $services->count()]])
        ->concat($categories->map(fn ($category) => [
            'key' => $category->id,
            'label' => $category->name,
            'count' => $services->where('idcategory', $category->id)->count(),
        ]))->all();
    $favorites = $services->filter(fn ($service) => (bool) $service->is_popular)->take(3);
    $durationLabel = fn ($service) => ($service->durasi ?? 60).' '.($service->satuan_durasi ?: 'menit');
@endphp

@section('content')
    <x-customer.hero :name="$tenant->namabisnis" :description="$tenant->deskripsi" :logo="$businessLogo ?? null" :initials="$businessInitials ?? null"
        :banner="$businessBanner ?? null" :badges="['Bayar online', 'Invoice otomatis']" :address="$tenant->alamat" :maps-url="$tenant->map_url"
        :phone-label="$tenant->nomorhp" :phone-url="$merchantWaUrl ?? null" :wa-url="$merchantWaUrl ?? null" :home-url="$storefrontUrl ?? null" />

    @if ($landing)
        <div class="px-4 pt-6 md:px-8">
            @include('customer.booking.partials.landing')
        </div>
    @endif

    <div id="booking-program-root" data-tenant-slug="{{ $tenant->slug }}" x-data="bookingProgram()" @booking-reset-submitting.window="isSubmitting = false"
        class="mx-auto max-w-[1240px] md:px-8 lg:px-14">
        @if ($errors->any())
            <x-customer.status-note tone="danger" role="alert" class="mx-4 mt-4 md:mx-0">{{ $errors->first() }}</x-customer.status-note>
        @endif

        @if (! $acceptsOnlineBookings)
            <x-customer.empty-state class="mx-4 mt-6 md:mx-0" tone="warning" icon="closed" title="Reservasi online sedang ditutup"
                description="{{ $tenant->namabisnis }} belum menerima pemesanan lewat halaman ini. Hubungi bisnis untuk mengatur jadwal.">
                @if ($merchantWaUrl ?? null)
                    <x-slot:actions>
                        <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn sf-btn-lg">Hubungi via WhatsApp</a>
                    </x-slot:actions>
                @endif
            </x-customer.empty-state>
        @elseif ($services->isEmpty())
            <x-customer.empty-state class="mx-4 mt-6 md:mx-0" icon="calendar" title="Belum ada layanan"
                description="Hubungi bisnis untuk menanyakan layanan yang bisa dipesan.">
                @if ($merchantWaUrl ?? null)
                    <x-slot:actions>
                        <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary">Tanya via WhatsApp</a>
                    </x-slot:actions>
                @endif
            </x-customer.empty-state>
        @else
            <form id="booking-program-form" x-ref="confirmForm" method="POST"
                action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.select-program', $tenant->slug) }}"
                @submit="guardSubmit($event)" class="lg:grid lg:grid-cols-[250px_minmax(0,1fr)] lg:items-start lg:gap-9 lg:pt-8">
                @csrf
                <input type="hidden" name="service_id" :value="selectedServiceId">

                @if ($categories->isNotEmpty())
                    <div class="hidden lg:sticky lg:top-6 lg:block">
                        <x-customer.category-chips variant="nav" model="activeCategory" active="all" label="Kategori" :categories="$categoryChips" />
                        @if ($merchantWaUrl ?? null)
                            <p class="sf-card-small mt-5 p-4 text-[13px] leading-[19px] text-sf-muted">
                                Ragu memilih? <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="font-bold text-sf-primary-text underline">Tanya lewat WhatsApp</a>
                            </p>
                        @endif
                    </div>
                @endif

                <div class="min-w-0 lg:col-start-2">
                    {{-- Search and chips: sticky on phone, in flow from tablet up. --}}
                    <div class="sticky top-0 z-20 bg-sf-page pb-1 pt-3 shadow-[0_1px_0_var(--sf-divider)] md:static md:pt-5 md:shadow-none lg:pt-0">
                        <div class="flex items-center gap-4 px-4 md:px-0">
                            <label class="sf-field sf-field-group grow md:min-h-[52px]">
                                <x-customer.icon name="search" />
                                <span class="sr-only">Cari layanan</span>
                                <input id="service-search" type="search" x-model="searchQuery" @keydown.enter="keepSearchEnterLocal($event)" placeholder="Cari layanan">
                            </label>
                            <span class="sf-num hidden whitespace-nowrap text-sm text-sf-muted lg:inline" aria-live="polite"
                                x-text="visibleCount() + (isFiltering() ? ' hasil' : ' layanan')">{{ $services->count() }} layanan</span>
                        </div>
                        @if ($categories->isNotEmpty())
                            <x-customer.category-chips class="mt-1 md:mt-3 lg:hidden" model="activeCategory" active="all" label="Kategori" :categories="$categoryChips" />
                        @endif
                    </div>

                    <div x-show="visibleCount() > 0">
                        @if ($favorites->isNotEmpty())
                            <section x-show="!isFiltering()" aria-labelledby="judul-favorit">
                                <div class="flex items-baseline justify-between gap-3 px-4 pb-2.5 pt-[18px] md:px-0 md:pt-[26px] lg:pt-7">
                                    <h2 id="judul-favorit" class="text-lg font-bold md:text-xl lg:text-[22px]">Favorit</h2>
                                    @if ($favorites->count() > 1)
                                        <span class="text-[13px] text-sf-muted md:hidden">Geser untuk lihat {{ $favorites->count() }} layanan</span>
                                    @endif
                                </div>
                                <div class="sf-scroll-row gap-3 px-4 pb-1.5 md:grid md:grid-cols-3 md:gap-3.5 md:overflow-visible md:px-0 lg:gap-4">
                                    @foreach ($favorites as $service)
                                        <x-customer.service-card class="w-[290px] shrink-0 md:w-auto" :value="$service->id" :name="$service->namalayanan"
                                            :price="$servicePrices[$service->id]" :duration="$durationLabel($service)" :category="$service->category?->name"
                                            :description="$service->deskripsi" :image="$serviceImages[$service->id] ?? null" :tone="$loop->index" x-on:click.prevent="selectServiceById({{ Js::from($service->id) }})" />
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        <section aria-labelledby="judul-semua">
                            <div class="flex items-baseline justify-between gap-3 px-4 pb-2.5 pt-6 md:px-0 md:pt-7 lg:pt-[34px]">
                                <h2 id="judul-semua" class="text-lg font-bold md:text-xl lg:text-[22px]">Semua layanan</h2>
                                <span class="sf-num text-[13px] text-sf-muted lg:hidden" x-text="visibleCount() + ' layanan'">{{ $services->count() }} layanan</span>
                            </div>
                            <div class="grid gap-2.5 px-4 md:grid-cols-2 md:gap-3 md:px-0">
                                @foreach ($services as $service)
                                    <x-customer.service-row x-show="isCardVisible({{ Js::from($service->category?->id ?? '') }}, {{ Js::from($service->namalayanan) }})"
                                        :value="$service->id" :name="$service->namalayanan" :price="$servicePrices[$service->id]"
                                        :duration="$durationLabel($service)" :category="$service->category?->name" :description="$service->deskripsi"
                                        :image="$serviceImages[$service->id] ?? null" :tone="$loop->index"
                                        x-on:click.prevent="selectServiceById({{ Js::from($service->id) }})" />
                                @endforeach
                            </div>
                        </section>
                    </div>

                    <div x-show="visibleCount() === 0" x-cloak class="px-4 pt-6 md:px-0">
                        <section role="status" class="sf-card px-6 py-8 text-center">
                            <span class="mx-auto grid size-14 place-items-center rounded-sf-card-small bg-sf-tint text-sf-primary-text">
                                <x-customer.icon name="search-off" :size="26" />
                            </span>
                            <h2 class="mt-3.5 text-lg font-bold">
                                <span x-show="searchQuery.trim() !== ''">Tidak ada layanan “<span x-text="searchQuery.trim()"></span>”</span>
                                <span x-show="searchQuery.trim() === ''">Tidak ada layanan di kategori ini</span>
                            </h2>
                            <p class="mx-auto mt-1.5 max-w-[320px] text-sm leading-[21px] text-sf-muted">Coba kata lain atau lihat semua layanan. Bisnis juga bisa ditanya langsung.</p>
                            <div class="mt-4 flex flex-wrap justify-center gap-2">
                                <button type="button" class="sf-btn" x-on:click="clearFilters()">Lihat semua layanan</button>
                                @if ($merchantWaUrl ?? null)
                                    <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary">Tanya via WhatsApp</a>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>
            </form>
        @endif
    </div>

    @if ($pendingCheckout)
        <div role="status" class="no-print sf-card-raised fixed inset-x-3 bottom-4 z-40 mx-auto flex max-w-[720px] items-center gap-3 py-3 pl-4 pr-3 md:inset-x-8">
            <span class="size-2.5 shrink-0 rounded-full bg-sf-warning-dot shadow-[0_0_0_5px_var(--sf-warning-bg)]" aria-hidden="true"></span>
            <span class="min-w-0 grow">
                <span class="block text-sm font-bold">Pembayaran belum selesai</span>
                <span class="block truncate text-[13px] text-sf-muted">{{ $pendingCheckout['service'] }} · bayar sebelum <span class="sf-num">{{ $pendingCheckout['deadline'] }}</span></span>
            </span>
            <a href="{{ $pendingCheckout['url'] }}" class="sf-btn shrink-0 text-sm">Lanjutkan</a>
        </div>
    @endif

    <script type="application/json" id="booking-services-data">@json($servicesPayload)</script>
@endsection

@section('scripts')
    @if ($tracking ?? null)
        @include('customer.booking.partials.tracking')
    @endif
    <script defer src="{{ asset('js/booking-program.js') }}?v={{ filemtime(public_path('js/booking-program.js')) }}"></script>
@endsection
