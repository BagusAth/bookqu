@extends('customer.layouts.booking-shell')

@section('title', 'Pilih layanan')
@section('current_step', 1)

@section('content')
    @if ($landing)
        @include('customer.booking.partials.landing')
    @endif

    <div id="booking-program-root" data-tenant-slug="{{ $tenant->slug }}" x-data="bookingProgram()" @booking-reset-submitting.window="isSubmitting = false" class="mx-auto max-w-5xl space-y-4">
        <x-customer.page-header title="Pilih layanan" description="Pilih salah satu layanan untuk memulai pemesanan." />
        @if ($pendingCheckout)
            <x-customer.pending-checkout-banner :pending="$pendingCheckout" />
        @endif
        @if (!$acceptsOnlineBookings)
            <x-ui.banner title="Bisnis ini belum menerima booking online saat ini." tone="warning" />
        @endif
        @if ($errors->any())
            <x-ui.banner :title="$errors->first()" tone="danger" />
        @endif

        @if ($acceptsOnlineBookings)
        <form id="booking-program-form" x-ref="confirmForm" method="POST"
            action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.select-program', $tenant->slug) }}"
            @submit="guardSubmit($event)" class="space-y-4">
            @csrf
            <input type="hidden" name="service_id" :value="selectedServiceId">
        @else
            <div class="space-y-4">
        @endif

            @if ($services->isNotEmpty())
                <div class="space-y-3" x-cloak>
                    <label for="service-search" class="sr-only">Cari layanan</label>
                    <input id="service-search" type="search" x-model="searchQuery" @keydown.enter="keepSearchEnterLocal($event)"
                        placeholder="Cari layanan..." class="bq-input w-full max-w-md">
                    @if ($categories->isNotEmpty())
                        <div role="group" aria-label="Kategori layanan" class="flex flex-wrap gap-2">
                            <button type="button" class="bq-btn bq-btn-secondary min-h-11" @click="setCategory('all')"
                                :aria-pressed="activeCategory === 'all'" :class="{ 'bg-bq-primary-light': activeCategory === 'all' }">Semua</button>
                            @foreach ($categories as $category)
                                <button type="button" class="bq-btn bq-btn-secondary min-h-11" @click="setCategory(@js($category->id))"
                                    :aria-pressed="activeCategory === @js($category->id)"
                                    :class="{ 'bg-bq-primary-light': activeCategory === @js($category->id) }">{{ $category->name }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($services as $service)
                    <article x-show="isCardVisible(@js($service->category?->id ?? ''), @js($service->namalayanan))"
                        class="booking-card bq-panel flex min-w-0 flex-col overflow-hidden">
                        @if ($serviceImages[$service->id] ?? null)
                            <img src="{{ $serviceImages[$service->id] }}" alt="" class="aspect-video w-full object-cover" loading="lazy">
                        @endif
                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-bq-section">{{ $service->namalayanan }}</h2>
                                @if ($service->is_popular)
                                    <x-ui.badge tone="neutral">Favorit</x-ui.badge>
                                @endif
                            </div>
                            @if ($service->deskripsi)
                                <p class="line-clamp-2 text-bq-body text-bq-text-muted" title="{{ $service->deskripsi }}">{{ $service->deskripsi }}</p>
                            @endif
                            <p class="flex flex-wrap gap-x-3 text-bq-body text-bq-text-muted">
                                <span class="bq-num font-semibold text-bq-text">{{ $servicePrices[$service->id] }} / {{ $service->satuan_harga ?: 'sesi' }}</span>
                                <span class="bq-num">{{ $service->durasi }} {{ $service->satuan_durasi ?: 'menit' }}</span>
                            </p>
                            @if ($acceptsOnlineBookings)
                                <button type="submit" name="service_id" value="{{ $service->id }}" aria-label="Pilih {{ $service->namalayanan }}"
                                    class="bq-btn bq-btn-secondary mt-auto min-h-11 self-start" x-on:click.prevent="selectServiceById(@js($service->id))">
                                    Pilih layanan ini
                                </button>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-ui.empty class="col-span-full" title="Belum ada layanan"
                        description="Hubungi bisnis untuk menanyakan layanan yang bisa dipesan." />
                @endforelse
            </div>

            @if ($services->isNotEmpty())
                <div x-show="!services.some(service => isCardVisible(service.category_id || '', service.name))" x-cloak>
                    <x-ui.empty title="Tidak ada layanan yang cocok" description="Coba kata kunci atau kategori lain.">
                        <x-slot:action>
                            <x-ui.button x-on:click="searchQuery = ''; setCategory('all')">Bersihkan filter</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty>
                </div>
            @endif
        @if ($acceptsOnlineBookings)</form>@else</div>@endif
    </div>

    <script type="application/json" id="booking-services-data">@json($servicesPayload)</script>
@endsection

@section('scripts')
    @if ($tracking ?? null)
        @include('customer.booking.partials.tracking')
    @endif
    <script defer src="{{ asset('js/booking-program.js') }}"></script>
@endsection
