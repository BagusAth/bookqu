@extends('layouts.owner-layout')

@section('title', 'Schedule')

@section('content')
@php
    $daftarHariCol = collect($daftarhari);
    $activeDayTab = ($daftarHariCol->first(fn($h) => $h->isToday()) ?? $daftarHariCol->first())?->format('Y-m-d') ?? now()->format('Y-m-d');
@endphp
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    selectedDayTab: '{{ $activeDayTab }}'
}">

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-bq-text sm:text-3xl">Schedule Management</h1>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                <button @click="$dispatch('open-default-pricing')" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs sm:text-sm font-semibold text-bq-text transition-all hover:border-bq-border-strong hover:shadow-2xs active:scale-95 cursor-pointer" id="btn-default-pricing">
                    <svg class="h-4 w-4 text-bq-text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="truncate">Set Default Pricing</span>
                </button>
                <button @click="$dispatch('open-configure-availability')" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs sm:text-sm font-semibold text-bq-text transition-all hover:border-bq-border-strong hover:shadow-2xs active:scale-95 cursor-pointer" id="btn-configure-availability">
                    <svg class="h-4 w-4 text-bq-text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m-7 5h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Configure</span>
                </button>
            </div>
            <button @click="$dispatch('open-add-bulk-slots')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 cursor-pointer" id="btn-add-slots">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Bulk Slots</span>
            </button>
        </div>
    </div>

    {{-- ── Compact Metric Summary Bar (2 cols on mobile, 4 on desktop) ── --}}
    <div class="grid grid-cols-2 gap-2.5 sm:gap-3.5 lg:grid-cols-4">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 shadow-2xs">
            <div class="flex h-8.5 w-8.5 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#b499ff]/20">
                <svg class="h-4 sm:h-4.5 w-4 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-bold text-bq-text-muted truncate">Total Slots</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-base sm:text-lg font-black text-bq-text">{{ $totalslot }}</span>
                    <span class="text-[9px] sm:text-[10px] font-semibold text-emerald-600 hidden xs:inline">Minggu ini</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 shadow-2xs">
            <div class="flex h-8.5 w-8.5 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200/50">
                <svg class="h-4 sm:h-4.5 w-4 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-bold text-bq-text-muted truncate">Rasio Terbooking</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-base sm:text-lg font-black text-bq-text">{{ $rasioterboking }}%</span>
                </div>
                <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-indigo-600 transition-all duration-500" style="width: {{ $rasioterboking }}%"></div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 shadow-2xs">
            <div class="flex h-8.5 w-8.5 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/50">
                <svg class="h-4 sm:h-4.5 w-4 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-bold text-bq-text-muted truncate">Est. Omzet</p>
                <p class="text-xs sm:text-lg font-black text-bq-text truncate mt-0.5">Rp {{ number_format($estimasirevenue, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 shadow-2xs">
            <div class="flex h-8.5 w-8.5 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 border border-purple-200/50">
                <span class="relative flex h-2 w-2 sm:h-2.5 sm:w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 sm:h-2.5 sm:w-2.5 bg-emerald-500"></span>
                </span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-bold text-bq-text-muted truncate">Status Live</p>
                <p class="text-[11px] sm:text-xs font-bold text-bq-text truncate mt-0.5">
                    @if ($bookingberikutnya)
                        {{ $bookingberikutnya->tanggalbooking->format('d M') }} &bull; {{ substr($bookingberikutnya->jam, 0, 5) }}
                    @else
                        Siap terima booking
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ── Weekly Calendar ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface overflow-hidden shadow-xs" id="schedule-calendar">
        {{-- Calendar Header --}}
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between border-b border-bq-border px-4 sm:px-5 py-3.5 sm:py-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1">
                    <a href="/owner/schedule?minggu={{ $offsetminggu - 1 }}" class="rounded-xl p-1.5 text-bq-text-muted transition-colors hover:bg-bq-background hover:text-bq-text active:scale-95" id="btn-prev-week" aria-label="Previous week">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <a href="/owner/schedule?minggu={{ $offsetminggu + 1 }}" class="rounded-xl p-1.5 text-bq-text-muted transition-colors hover:bg-bq-background hover:text-bq-text active:scale-95" id="btn-next-week" aria-label="Next week">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-bq-text">
                    {{ $awalminggu->format('F d') }} – {{ $akhirminggu->format('d, Y') }}
                </h2>
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-3 text-xs text-bq-text-muted">
                <div class="flex items-center gap-2">
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-bq-primary/20"></span> Available</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-bq-text-subtle/20"></span> Booked</span>
                </div>
            </div>
        </div>

        {{-- Mobile Day Tabs Strip (Screen < sm) --}}
        <div class="block sm:hidden border-b border-bq-border bg-slate-50/70 p-2 overflow-x-auto no-scrollbar">
            <div class="flex items-center gap-1.5 min-w-full">
                @foreach ($daftarhari as $hari)
                    @php
                        $tglStr = $hari->format('Y-m-d');
                        $isToday = $hari->isToday();
                        $slotCount = $jadwalminggu->get($tglStr, collect())->count();
                    @endphp
                    <button
                        type="button"
                        @click="selectedDayTab = '{{ $tglStr }}'"
                        :class="selectedDayTab === '{{ $tglStr }}'
                            ? 'bg-bq-primary text-white shadow-xs ring-2 ring-bq-primary/30'
                            : 'bg-white text-bq-text border border-bq-border hover:bg-slate-100'"
                        class="flex-1 min-w-[50px] py-2 px-1 rounded-xl text-center transition-all cursor-pointer flex flex-col items-center justify-center"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">{{ $hari->format('D') }}</span>
                        <span class="text-sm font-extrabold mt-0.5">{{ $hari->format('d') }}</span>
                        <span class="inline-block mt-1 text-[9px] font-semibold px-1.5 rounded-full"
                            :class="selectedDayTab === '{{ $tglStr }}' ? 'bg-white/20 text-white' : 'bg-indigo-50 text-bq-primary'">
                            {{ $slotCount }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Mobile Selected Day Slots List (Screen < sm) --}}
        <div class="block sm:hidden p-4 space-y-2.5">
            @foreach ($daftarhari as $hari)
                @php
                    $tglStr = $hari->format('Y-m-d');
                    $slothari = $jadwalminggu->get($tglStr, collect());
                @endphp
                <div x-show="selectedDayTab === '{{ $tglStr }}'" class="space-y-2.5">
                    <div class="flex items-center justify-between pb-1">
                        <p class="text-xs font-bold text-[#231a3d] uppercase tracking-wide">
                            Jadwal {{ $hari->translatedFormat('l, d F Y') }}
                        </p>
                        <span class="text-xs text-bq-text-muted font-medium">{{ $slothari->count() }} slot operasional</span>
                    </div>

                    @forelse ($slothari as $slot)
                        @php
                            $adabooking = $slot->bookings->where('status', '!=', 'cancelled')->count() > 0;
                            $bookingnya = $slot->bookings->where('status', '!=', 'cancelled')->first();
                        @endphp
                        <div class="rounded-xl border p-2.5 sm:p-3 shadow-2xs transition-all flex items-center justify-between gap-2.5
                            {{ $adabooking ? 'border-bq-border bg-slate-50' : 'border-indigo-100 bg-[#f8f6ff]' }}">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-black text-bq-primary">
                                        {{ substr($slot->jam_mulai, 0, 5) }} - {{ substr($slot->jam_selesai, 0, 5) }}
                                    </span>
                                    @if ($adabooking)
                                        <span class="rounded-md bg-emerald-100 px-1.5 py-0.2 text-[9px] font-bold text-emerald-800 uppercase">Booked</span>
                                    @else
                                        <span class="rounded-md bg-[#e7e2f7] px-1.5 py-0.2 text-[9px] font-bold text-[#382186] uppercase">Available</span>
                                    @endif
                                </div>
                                <p class="text-xs font-bold text-bq-text mt-0.5 truncate">
                                    {{ $slot->layanan->namalayanan ?? 'Layanan' }}
                                </p>
                                <p class="text-[11px] text-bq-text-muted font-semibold">
                                    Rp {{ number_format($slot->harga_override ?? $slot->layanan->harga ?? 0, 0, ',', '.') }}
                                    @if ($adabooking)
                                        &bull; <span class="text-[#231a3d] font-bold">{{ $bookingnya->namapelanggan ?? '' }}</span>
                                    @endif
                                </p>
                            </div>

                            {{-- Visible Mobile Action Buttons --}}
                            <div class="shrink-0 flex items-center gap-1">
                                @if (!$adabooking)
                                    <form method="POST" action="/owner/schedule/slots/{{ $slot->id }}" id="form-delete-mobile-slot-{{ $slot->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            class="rounded-xl p-2 text-rose-600 bg-rose-50 border border-rose-200 active:scale-95 transition"
                                            @click="$dispatch('open-confirm', { title: 'Hapus Slot?', message: 'Apakah Anda yakin ingin menghapus slot jadwal ini?', formId: 'form-delete-mobile-slot-{{ $slot->id }}' })"
                                            aria-label="Delete slot"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <button
                                        type="button"
                                        class="rounded-xl p-2 text-bq-primary bg-indigo-50 border border-indigo-200 active:scale-95 transition"
                                        @click="$dispatch('open-view-booking', { booking: {{ json_encode($bookingnya) }} })"
                                        title="View Details"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-bq-border bg-slate-50/50 p-6 text-center space-y-2">
                            <p class="text-xs font-semibold text-bq-text-muted">Tidak ada slot untuk {{ $hari->translatedFormat('l, d F') }}</p>
                            <button
                                type="button"
                                @click="$dispatch('open-add-bulk-slots')"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-bq-primary hover:underline"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Tambah Slot Sekarang</span>
                            </button>
                        </div>
                    @endforelse
                </div>
            @endforeach
        </div>

        {{-- Desktop Calendar Grid (Screen >= sm) --}}
        <div class="hidden sm:block overflow-x-auto">
            <div class="grid min-w-[700px] grid-cols-7 divide-x divide-bq-border">
                {{-- Day headers --}}
                @foreach ($daftarhari as $hari)
                    @php
                        $istoday = $hari->isToday();
                    @endphp
                    <div class="border-b border-bq-border px-2 py-3 text-center {{ $istoday ? 'bg-bq-primary-light' : '' }}">
                        <p class="text-xs font-semibold uppercase {{ $istoday ? 'text-bq-primary' : 'text-bq-text-muted' }}">{{ $hari->format('D') }}</p>
                        <p class="mt-0.5 text-lg font-bold {{ $istoday ? 'text-bq-primary' : 'text-bq-text' }}">{{ $hari->format('d') }}</p>
                    </div>
                @endforeach

                {{-- Time slots per day --}}
                @foreach ($daftarhari as $hari)
                    <div class="min-h-[260px] space-y-1.5 p-2" x-data="{ expanded: false }">
                        @php
                            $tanggalkey = $hari->format('Y-m-d');
                            $slothari = $jadwalminggu->get($tanggalkey, collect());
                        @endphp

                        @forelse ($slothari as $index => $slot)
                            @php
                                $adabooking = $slot->bookings->where('status', '!=', 'cancelled')->count() > 0;
                                $bookingnya = $slot->bookings->where('status', '!=', 'cancelled')->first();
                            @endphp
                            <div
                                x-show="{{ $index }} < 5 || expanded"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="group rounded-lg border p-2 text-xs transition-all hover:shadow-sm
                                {{ $adabooking
                                    ? 'border-bq-border-strong bg-bq-background'
                                    : 'border-bq-primary/30 bg-bq-primary/5 hover:border-bq-primary/50'
                                }}
                            ">
                                <p class="font-bold {{ $adabooking ? 'text-bq-text-muted' : 'text-bq-primary' }}">
                                    Rp {{ number_format($slot->harga_override ?? $slot->layanan->harga ?? 0, 0, ',', '.') }}
                                </p>
                                <p class="mt-0.5 truncate {{ $adabooking ? 'text-bq-text-subtle' : 'text-bq-text-muted' }}">
                                    {{ $adabooking ? 'Booked: ' . ($bookingnya->namapelanggan ?? '') : 'Available' }}
                                </p>
                                <div class="mt-0.5 flex items-center justify-between gap-2 text-bq-text-subtle">
                                    <span>{{ substr($slot->jam_mulai, 0, 5) }} - {{ substr($slot->jam_selesai, 0, 5) }}</span>
                                    @if (!$adabooking)
                                        <form method="POST" action="/owner/schedule/slots/{{ $slot->id }}" class="opacity-0 transition-all group-hover:opacity-100" id="form-delete-slot-{{ $slot->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="rounded-md p-1 text-bq-text-subtle transition-all hover:bg-rose-50 hover:text-rose-600"
                                                @click="$dispatch('open-confirm', { title: 'Hapus Slot?', message: 'Apakah Anda yakin ingin menghapus slot jadwal ini?', formId: 'form-delete-slot-{{ $slot->id }}' })"
                                                aria-label="Delete slot"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <button 
                                            type="button" 
                                            class="rounded-md p-1 text-bq-primary opacity-0 transition-all group-hover:opacity-100 hover:bg-bq-primary/10" 
                                            @click="$dispatch('open-view-booking', { booking: {{ json_encode($bookingnya) }} })"
                                            title="View Details"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="flex h-full items-center justify-center text-xs text-bq-text-subtle">
                                No slots
                            </div>
                        @endforelse

                        @if ($slothari->count() > 5)
                            <button
                                type="button"
                                @click="expanded = !expanded"
                                class="mt-1.5 w-full flex items-center justify-between gap-1.5 rounded-xl border border-[#b499ff]/30 bg-[#f3effe]/60 hover:bg-[#f3effe] px-2.5 py-1.5 text-left text-[11px] font-bold text-[#382186] transition-all shadow-2xs hover:shadow-xs active:scale-[0.98] cursor-pointer group/btn"
                            >
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="inline-flex items-center justify-center rounded-md bg-[#382186] text-white px-1.5 py-0.5 text-[9px] font-black" x-show="!expanded">
                                        +{{ $slothari->count() - 5 }}
                                    </span>
                                    <span class="truncate" x-show="!expanded">Lihat slot lainnya</span>
                                    <span class="truncate" x-show="expanded" x-cloak>Tutup slot</span>
                                </div>
                                <svg
                                    class="h-3.5 w-3.5 shrink-0 text-[#382186] transition-transform duration-200"
                                    :class="expanded ? 'rotate-180' : ''"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Bottom Section ── --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Bulk Price Adjustments --}}
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5" id="price-adjustments">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-bq-text">Bulk Price Adjustments</h2>
                <button class="rounded-lg p-1.5 text-bq-text-subtle hover:bg-bq-background hover:text-bq-text">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                </button>
            </div>
            <div class="mt-4 space-y-4">
                <div class="flex items-center justify-between rounded-lg border border-bq-border bg-bq-background/50 p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
                            <svg class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-bq-text">Surge Pricing (Weekend)</p>
                            <p class="text-xs text-bq-text-muted">Apply +20% during peak weekend hours</p>
                        </div>
                    </div>
                    <button @click="$dispatch('open-configure-availability')" class="rounded-lg border border-bq-border bg-bq-surface px-3.5 py-1.5 text-xs font-medium text-bq-text transition-all hover:border-bq-border-strong hover:shadow-sm">Configure</button>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-bq-border bg-bq-background/50 p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50">
                            <svg class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-bq-text">Early Bird Discounts</p>
                            <p class="text-xs text-bq-text-muted">Apply -15% for slots booked 30 days in advance</p>
                        </div>
                    </div>
                    <button @click="$dispatch('open-configure-availability')" class="rounded-lg border border-bq-border bg-bq-surface px-3.5 py-1.5 text-xs font-medium text-bq-text transition-all hover:border-bq-border-strong hover:shadow-sm">Configure</button>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5" id="schedule-recent-activity">
            <h2 class="text-base font-semibold text-bq-text">Recent Activity</h2>
            <div class="mt-4 space-y-4">
                @foreach ($aktivitasjadwal as $aktivitas)
                    <div class="flex items-start gap-3">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $aktivitas->status === 'paid' ? 'bg-bq-primary' : ($aktivitas->status === 'completed' ? 'bg-emerald-500' : 'bg-bq-text-subtle') }}"></span>
                        <div>
                            <p class="text-sm font-medium text-bq-text">
                                {{ $aktivitas->status === 'paid' ? 'New Booking' : ($aktivitas->status === 'completed' ? 'Completed' : ucfirst($aktivitas->status)) }}
                            </p>
                            <p class="text-xs text-bq-text-muted">
                                {{ $aktivitas->namapelanggan }} booked {{ $aktivitas->layanan->namalayanan ?? '' }}, {{ $aktivitas->tanggalbooking->format('M d') }} at {{ $aktivitas->jam }}
                            </p>
                            <p class="text-xs text-bq-text-subtle">{{ $aktivitas->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

{{-- Add Bulk Slots Modal --}}
@include('components.owner.modal-add-bulk-slots', ['daftarlayanan' => $daftarlayanan])
{{-- Default Pricing Modal --}}
@include('components.owner.modal-default-pricing', ['daftarlayanan' => $daftarlayanan])
{{-- Configure Availability Modal --}}
@include('components.owner.modal-configure-availability', ['blockedDates' => $blockedDates, 'tenant' => $tenant])
{{-- View Booking Modal --}}
@include('components.owner.modal-view-booking')
{{-- Reschedule Booking Modal --}}
@include('components.owner.modal-reschedule-booking')
@endsection
