@extends('layouts.owner-layout')

@section('title', 'Schedule')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Flash Message ── --}}
    @if (session('sukses'))
        <div x-data="{ tampil: true }" x-show="tampil" x-init="setTimeout(() => tampil = false, 4000)"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3" id="flash-success">
            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium text-emerald-800">{{ session('sukses') }}</p>
        </div>
    @endif

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-bq-text sm:text-3xl">Schedule Management</h1>
            <p class="mt-1 text-sm text-bq-text-muted">Manage your weekly availability and pricing per slot.</p>
        </div>
        <div class="flex items-center gap-3">
            <button class="inline-flex items-center gap-2 rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-text transition-all hover:border-bq-border-strong hover:shadow-sm" id="btn-default-pricing">
                <svg class="h-4 w-4 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Set Default Pricing
            </button>
            <button @click="$dispatch('open-add-bulk-slots')" class="inline-flex items-center gap-2 rounded-lg bg-bq-primary px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5" id="btn-add-slots">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Bulk Slots
            </button>
        </div>
    </div>

    {{-- ── Stats ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Total Slots</p>
            <p class="mt-1 text-2xl font-bold text-bq-text">{{ $totalslot }}</p>
            <p class="mt-1 text-xs text-emerald-600">This week</p>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Booked Ratio</p>
            <p class="mt-1 text-2xl font-bold text-bq-text">{{ $rasioterboking }}%</p>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-bq-background">
                <div class="h-full rounded-full bg-bq-primary transition-all duration-500" style="width: {{ $rasioterboking }}%"></div>
            </div>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Est. Revenue</p>
            <p class="mt-1 text-2xl font-bold text-bq-text">Rp {{ number_format($estimasirevenue, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-bq-text-muted">For current week</p>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Real-Time Status</p>
            <div class="mt-1 flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                </span>
                <span class="text-2xl font-bold text-bq-text">Live</span>
            </div>
            <p class="mt-1 text-xs text-bq-text-muted">
                @if ($bookingberikutnya)
                    Next booking {{ $bookingberikutnya->tanggalbooking->format('d M') }} at {{ $bookingberikutnya->jam }}
                @else
                    No upcoming bookings
                @endif
            </p>
        </div>
    </div>

    {{-- ── Weekly Calendar ── --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="schedule-calendar">
        {{-- Calendar Header --}}
        <div class="flex items-center justify-between border-b border-bq-border px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1">
                    <a href="/owner/schedule?minggu={{ $offsetminggu - 1 }}" class="rounded-lg p-1.5 text-bq-text-muted transition-colors hover:bg-bq-background hover:text-bq-text" id="btn-prev-week">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <a href="/owner/schedule?minggu={{ $offsetminggu + 1 }}" class="rounded-lg p-1.5 text-bq-text-muted transition-colors hover:bg-bq-background hover:text-bq-text" id="btn-next-week">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <h2 class="text-base font-semibold text-bq-text">
                    {{ $awalminggu->format('F d') }} – {{ $akhirminggu->format('d, Y') }}
                </h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-xs text-bq-text-muted">
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-bq-primary/20"></span> Available</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-bq-text-subtle/20"></span> Booked</span>
                </div>
                <span class="rounded-lg border border-bq-border bg-bq-background px-3 py-1.5 text-xs font-medium text-bq-text-muted">Weekly View</span>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="overflow-x-auto">
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
                    <div class="min-h-[260px] space-y-1.5 p-2">
                        @php
                            $tanggalkey = $hari->format('Y-m-d');
                            $slothari = $jadwalminggu->get($tanggalkey, collect());
                        @endphp

                        @forelse ($slothari->take(5) as $slot)
                            @php
                                $adabooking = $slot->bookings->where('status', '!=', 'cancelled')->count() > 0;
                                $bookingnya = $slot->bookings->where('status', '!=', 'cancelled')->first();
                            @endphp
                            <div class="rounded-lg border p-2 text-xs transition-all hover:shadow-sm
                                {{ $adabooking
                                    ? 'border-bq-border-strong bg-bq-background'
                                    : 'border-bq-primary/30 bg-bq-primary/5 hover:border-bq-primary/50'
                                }}
                            ">
                                <p class="font-bold {{ $adabooking ? 'text-bq-text-muted' : 'text-bq-primary' }}">
                                    Rp {{ number_format($slot->layanan->harga ?? 0, 0, ',', '.') }}
                                </p>
                                <p class="mt-0.5 truncate {{ $adabooking ? 'text-bq-text-subtle' : 'text-bq-text-muted' }}">
                                    {{ $adabooking ? 'Booked: ' . ($bookingnya->namapelanggan ?? '') : 'Available' }}
                                </p>
                                <p class="mt-0.5 text-bq-text-subtle">{{ $slot->jam_mulai }} - {{ $slot->jam_selesai }}</p>
                            </div>
                        @empty
                            <div class="flex h-full items-center justify-center text-xs text-bq-text-subtle">
                                No slots
                            </div>
                        @endforelse

                        @if ($slothari->count() > 5)
                            <p class="text-center text-xs font-medium text-bq-primary">+{{ $slothari->count() - 5 }} more</p>
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
                    <button class="rounded-lg border border-bq-border bg-bq-surface px-3.5 py-1.5 text-xs font-medium text-bq-text transition-all hover:border-bq-border-strong hover:shadow-sm">Configure</button>
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
                    <button class="rounded-lg border border-bq-border bg-bq-surface px-3.5 py-1.5 text-xs font-medium text-bq-text transition-all hover:border-bq-border-strong hover:shadow-sm">Configure</button>
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
@endsection
