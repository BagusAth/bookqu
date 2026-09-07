@extends('customer.layouts.booking-shell')

@section('title', 'Pilih Layanan')
@section('current_step', 1)

@section('content')
<div id="booking-program-root" data-tenant-slug="{{ $tenant->slug }}" x-data="bookingProgram()" @pageshow.window="isSubmitting = false" @pagehide.window="isSubmitting = false" @popstate.window="isSubmitting = false">
    {{-- Form Confirmation (Submitted explicitly by user clicking Continue) --}}
    <form
        id="booking-program-form"
        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]"
        method="POST"
        action="{{ route('customer.booking.select-program', $tenant->slug) }}"
        x-ref="confirmForm"
    >
        @csrf
        <input type="hidden" name="service_id" :value="selectedServiceId" />

        {{-- Left Column: Service Cards --}}
        <section>
            <div class="mb-6">
                <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Pilih Layanan</h1>
                <p class="mt-1 text-sm text-[#64748B]">
                    Silakan klik pada salah satu layanan di bawah ini untuk memulai pemesanan.
                </p>

                {{-- Search & Filter Controls --}}
                <div class="mt-6 space-y-3.5">
                    {{-- Precision Search Input --}}
                    <div class="relative flex items-center">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            x-model="searchQuery"
                            placeholder="Cari nama layanan, paket, atau durasi..."
                            class="h-11 sm:h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-11 text-sm text-[#0F172A] placeholder:text-slate-400 shadow-xs transition-all focus:border-[#4F46E5] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#EEF2FF] hover:border-slate-300"
                        />
                        <button
                            type="button"
                            x-show="searchQuery"
                            x-cloak
                            @click="searchQuery = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-700 transition-colors cursor-pointer"
                            title="Hapus pencarian"
                        >
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 transition-colors">
                                <svg class="h-3.5 w-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </span>
                        </button>
                    </div>

                    {{-- Category Filter Pills --}}
                    @if(isset($categories) && count($categories) > 0)
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar text-xs">
                            <button
                                type="button"
                                @click="setCategory('all')"
                                :class="activeCategory === 'all'
                                    ? 'bg-[#4F46E5] text-white shadow-xs font-bold border-[#4F46E5]'
                                    : 'bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50 border-slate-200 font-medium'"
                                class="inline-flex h-8.5 items-center gap-1.5 rounded-full border px-3.5 py-1 shrink-0 transition-all cursor-pointer"
                            >
                                <span>Semua Layanan</span>
                                <span
                                    :class="activeCategory === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                >{{ count($services) }}</span>
                            </button>
                            @foreach($categories as $cat)
                                <button
                                    type="button"
                                    @click="setCategory('{{ $cat->id }}')"
                                    :class="activeCategory === '{{ $cat->id }}'
                                        ? 'bg-[#4F46E5] text-white shadow-xs font-bold border-[#4F46E5]'
                                        : 'bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50 border-slate-200 font-medium'"
                                    class="inline-flex h-8.5 items-center gap-1.5 rounded-full border px-3.5 py-1 shrink-0 transition-all cursor-pointer"
                                >
                                    <span>{{ $cat->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                @forelse ($services as $service)
                    @php
                        $priceUnit = $service->satuan_harga ?: 'sesi';
                        $durationUnit = $service->satuan_durasi ?: 'menit';
                        $priceLabel = number_format($service->harga, 0, ',', '.');
                        $imageUrl = null;

                        if (!empty($service->image_url)) {
                            $imageUrl = \Illuminate\Support\Str::startsWith($service->image_url, ['http://', 'https://', '/'])
                                ? $service->image_url
                                : \Illuminate\Support\Facades\Storage::url($service->image_url);
                        }
                    @endphp
                    <article
                        class="booking-card group relative flex flex-col overflow-hidden rounded-2xl border transition-all cursor-pointer select-none"
                        :class="selectedServiceId === {{ $service->id }} ? 'booking-card--selected border-[#4F46E5] ring-2 ring-[#4F46E5] bg-[#F5F5FF]' : 'border-[#E2E8F0] bg-white hover:border-[#CBD5E1] hover:shadow-md'"
                        x-show="isCardVisible({{ $service->id }}, '{{ $service->category?->id ?? '' }}', '{{ addslashes($service->namalayanan) }}')"
                        @click="selectServiceById({{ $service->id }})"
                        tabindex="0"
                        role="button"
                        :aria-selected="selectedServiceId === {{ $service->id }}"
                        @keydown.enter="selectServiceById({{ $service->id }})"
                        @keydown.space.prevent="selectServiceById({{ $service->id }})"
                    >
                        {{-- Selected Checkmark Badge --}}
                        <div
                            class="absolute top-3 right-3 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-[#4F46E5] text-white shadow-md transition-all"
                            x-show="selectedServiceId === {{ $service->id }}"
                            x-cloak
                            x-transition
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        {{-- Service Image Media with Smart Ambient Backdrop --}}
                        <div class="booking-card__media aspect-square w-full overflow-hidden bg-gradient-to-br from-[#EEF2FF] to-[#E0E7FF] relative flex items-center justify-center">
                            @if ($imageUrl)
                                {{-- Ambient Blurred Layer --}}
                                <div class="absolute inset-0 overflow-hidden select-none pointer-events-none" aria-hidden="true">
                                    <img src="{{ $imageUrl }}" alt="" class="h-full w-full object-cover blur-2xl scale-125 opacity-35 filter brightness-105">
                                    <div class="absolute inset-0 bg-white/15 backdrop-blur-xs"></div>
                                </div>
                                {{-- Sharp Foreground Image --}}
                                <div class="relative z-10 h-full w-full flex items-center justify-center p-3">
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $service->namalayanan }}"
                                        class="max-h-full max-w-full rounded-xl object-contain shadow-xs transition duration-300 group-hover:scale-[1.02]"
                                        loading="lazy"
                                    />
                                </div>
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#EEF2FF] via-white to-[#E0E7FF]">
                                    <svg class="h-10 w-10 text-[#818CF8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                            @endif

                            @if ($service->is_popular)
                                <span class="absolute left-3 top-3 z-20 rounded-full bg-[#4F46E5] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm">
                                    Favorit
                                </span>
                            @endif
                        </div>

                        {{-- Service Details --}}
                        <div class="flex h-full flex-col p-5">
                            @if($service->category)
                                <span class="inline-block rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 mb-1.5 w-fit">
                                    {{ $service->category->name }}
                                </span>
                            @endif
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-base font-bold text-[#0F172A] group-hover:text-[#4F46E5] transition-colors leading-snug">
                                    {{ $service->namalayanan }}
                                </h3>
                                <div class="text-right shrink-0">
                                    <p class="text-sm sm:text-base font-extrabold text-[#4F46E5]">Rp {{ $priceLabel }}</p>
                                    <p class="text-[11px] text-[#64748B]">/ {{ $priceUnit }}</p>
                                </div>
                            </div>

                            @if($service->deskripsi)
                                <p class="mt-2 text-xs text-[#64748B] line-clamp-2 leading-relaxed">
                                    {{ $service->deskripsi }}
                                </p>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-[#64748B]">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-[#E2E8F0] bg-[#F8FAFC] px-2.5 py-1 text-[11px] font-medium">
                                    <svg class="h-3.5 w-3.5 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                                    </svg>
                                    {{ $service->durasi }} {{ $durationUnit }}
                                </span>

                                @if ($service->kapasitas)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-[#E2E8F0] bg-[#F8FAFC] px-2.5 py-1 text-[11px] font-medium">
                                        <svg class="h-3.5 w-3.5 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Maks {{ $service->kapasitas }} orang
                                    </span>
                                @endif
                            </div>

                            {{-- Card Select Indicator Button --}}
                            <div class="mt-5 pt-3 border-t border-[#F1F5F9]">
                                <span
                                    class="w-full flex items-center justify-center gap-1.5 rounded-xl py-2 px-3 text-xs font-bold transition-all"
                                    :class="selectedServiceId === {{ $service->id }}
                                        ? 'bg-[#4F46E5] text-white shadow-xs'
                                        : 'bg-[#F1F5F9] text-[#475569] group-hover:bg-[#EEF2FF] group-hover:text-[#4F46E5]'"
                                >
                                    <template x-if="selectedServiceId === {{ $service->id }}">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Layanan Terpilih
                                        </span>
                                    </template>
                                    <template x-if="selectedServiceId !== {{ $service->id }}">
                                        <span>Pilih Layanan Ini</span>
                                    </template>
                                </span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-[#CBD5E1] bg-white p-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#EEF2FF] text-[#4F46E5] mb-3">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-[#0F172A]">Belum Ada Layanan Tersedia</h4>
                        <p class="text-xs text-[#64748B] mt-1">Layanan sedang dipersiapkan oleh pemilik usaha. Silakan periksa kembali nanti.</p>
                    </div>
                @endforelse

                {{-- Empty Search State --}}
                <div
                    x-cloak
                    x-show="searchQuery && !services.some(s => isCardVisible(s.id, s.category_id || '', s.name))"
                    class="col-span-full rounded-2xl border border-dashed border-[#CBD5E1] bg-white p-8 text-center"
                >
                    <p class="text-sm font-bold text-[#0F172A]">Tidak ada layanan yang sesuai dengan pencarian</p>
                    <p class="text-xs text-[#64748B] mt-1">Coba kata kunci lain atau pilih kategori yang berbeda.</p>
                    <button type="button" @click="searchQuery = ''; setCategory('all')" class="mt-3 inline-flex items-center gap-1 rounded-xl bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-semibold text-[#0F172A] transition">
                        Reset Filter
                    </button>
                </div>
            </div>
        </section>

        {{-- Right Column: Sticky Booking Summary --}}
        <x-customer.booking-sidebar
            buttonLabel="Lanjut Pilih Tanggal"
            buttonEnabledWhen="selectedServiceId"
            onButtonClick="handleConfirm()"
        />
    </form>
</div>

{{-- Data Contract: Render both IDs to guarantee 100% compatibility --}}
<script type="application/json" id="booking-services-data">@json($servicesPayload ?? [])</script>
<script type="application/json" id="booking-service-data">@json($servicesPayload ?? [])</script>
@endsection

@section('scripts')
<script defer src="{{ asset('js/booking-program.js') }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
