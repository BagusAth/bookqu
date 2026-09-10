@extends('layouts.owner-layout')

@section('title', 'Bookings')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    detailOpen: false,
    activeBooking: null,
    viewBooking(b) {
        this.activeBooking = b;
        this.detailOpen = true;
    }
}">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Bookings Management',
        'subjudul' => '',
    ])

    {{-- ── Flash Messages ── --}}
    @if (session('sukses'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('sukses') }}</span>
        </div>
    @endif
    @if ($errors->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 flex items-center gap-2">
            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ $errors->first('error') }}</span>
        </div>
    @endif

    {{-- ── Top Summary Stats ── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @php
            $statbooking = [
                ['label' => 'All',       'nilai' => $totalbooking,      'warna' => 'bg-slate-100 text-slate-700',   'filter' => 'semua'],
                ['label' => 'Today',     'nilai' => $bookinghariini,    'warna' => 'bg-[#EEF2FF] text-[#4F46E5]',     'filter' => 'today'],
                ['label' => 'Pending',   'nilai' => $bookingpending,    'warna' => 'bg-amber-100 text-amber-800',   'filter' => 'pending'],
                ['label' => 'Confirmed', 'nilai' => $bookingkonfirmasi, 'warna' => 'bg-indigo-100 text-indigo-700', 'filter' => 'paid'],
                ['label' => 'Completed', 'nilai' => $bookingselesai,    'warna' => 'bg-emerald-100 text-emerald-800','filter' => 'completed'],
                ['label' => 'Cancelled', 'nilai' => $bookingbatal,      'warna' => 'bg-rose-100 text-rose-700',    'filter' => 'cancelled'],
            ];
        @endphp
        @foreach ($statbooking as $stat)
            <a href="{{ '/owner/bookings?status=' . $stat['filter'] }}"
               class="rounded-xl border border-bq-border bg-bq-surface p-4 text-center transition-all hover:border-bq-border-strong hover:shadow-sm {{ $filterstatus === ($stat['filter'] ?? '') ? 'ring-2 ring-bq-primary ring-offset-1' : '' }}">
                <p class="text-2xl font-bold text-bq-text">{{ number_format($stat['nilai']) }}</p>
                <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stat['warna'] }}">{{ $stat['label'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- ── Search & Filter Controls ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="/owner/bookings" class="relative w-full sm:max-w-xs">
            <input type="hidden" name="status" value="{{ $filterstatus }}">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="katakunci" value="{{ $katakunci }}" placeholder="Search code, name, email, phone..."
                class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs sm:text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                id="input-search-bookings">
        </form>
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1.5 pt-0.5 sm:pb-0 sm:flex-wrap no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
            @foreach (['semua' => 'All', 'today' => 'Today', 'pending' => 'Pending', 'paid' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $kunci => $label)
                <a href="/owner/bookings?status={{ $kunci }}&katakunci={{ $katakunci }}"
                   class="whitespace-nowrap rounded-xl px-3 py-1.5 text-xs font-semibold transition-all shrink-0
                    {{ $filterstatus === $kunci
                        ? 'bg-bq-primary text-white shadow-xs ring-2 ring-bq-primary/30'
                        : 'border border-bq-border bg-bq-surface text-bq-text-muted hover:border-bq-border-strong hover:text-bq-text'
                    }}"
                    id="filter-{{ $kunci }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- ── Bookings Mobile Cards (Screen < sm) ── --}}
    <div class="block sm:hidden space-y-3" id="bookings-mobile-cards">
        @forelse ($daftarbooking as $booking)
            @php
                $paymentStatus = $booking->payment?->status ?? ($booking->status === 'paid' ? 'sukses' : 'pending');
                $staffName = $booking->layanan?->staff?->pluck('name')->join(', ');
                $resourceName = $booking->layanan?->resources?->pluck('name')->join(', ');
                $staffResourceDisplay = $staffName ?: ($resourceName ?: 'General Staff');
                $bookingData = [
                    'id' => $booking->id,
                    'code' => $booking->booking_code ?? ('BKQ-' . $booking->id),
                    'name' => $booking->namapelanggan,
                    'email' => $booking->email,
                    'phone' => $booking->nomorhp,
                    'service' => $booking->layanan->namalayanan ?? 'Standard Service',
                    'price' => $booking->layanan->harga ?? 0,
                    'formatted_price' => 'Rp ' . number_format($booking->layanan->harga ?? 0, 0, ',', '.'),
                    'date' => $booking->tanggalbooking ? $booking->tanggalbooking->format('d M Y') : '-',
                    'time' => $booking->jam,
                    'status' => $booking->status,
                    'payment_status' => $paymentStatus,
                    'staff' => $staffName ?: 'General Staff',
                    'resource' => $resourceName ?: 'General Facility',
                    'notes' => $booking->catatan ?? '-',
                    'order_id' => $booking->payment?->order_id ?? '-',
                    'snap_token' => $booking->payment?->snap_token ?? null,
                    'rescheduled_from_date' => $booking->rescheduled_from_date ? $booking->rescheduled_from_date->format('d M Y') : null,
                    'rescheduled_from_time' => $booking->rescheduled_from_time ?? null,
                    'manage_url' => $booking->booking_code ? route('booking.manage', $booking->booking_code) : null,
                ];

                $warnastatus = match($booking->status) {
                    'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                    'paid'      => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                    'pending'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                    'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                    default     => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                };
                $statusLabel = match($booking->status) {
                    'paid'      => 'Confirmed',
                    'pending'   => 'Pending',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    default     => ucfirst($booking->status),
                };
            @endphp

            <div class="rounded-2xl border border-bq-border bg-bq-surface p-4 shadow-xs space-y-3">
                {{-- Top Row: Code, Date & Status --}}
                <div class="flex items-center justify-between border-b border-bq-border/60 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-bold text-bq-primary bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                            #{{ $booking->booking_code ?? $booking->id }}
                        </span>
                        <span class="text-xs text-bq-text-muted font-medium">
                            {{ $booking->tanggalbooking ? $booking->tanggalbooking->format('d M') : '-' }} &bull; {{ substr($booking->jam, 0, 5) }}
                        </span>
                    </div>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset {{ $warnastatus }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                {{-- Middle: Customer & Service Info --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <p class="font-bold text-sm text-bq-text truncate">{{ $booking->namapelanggan }}</p>
                            @if($booking->nomorhp)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->nomorhp) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 p-0.5 shrink-0" title="Hubungi via WhatsApp">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.303c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                        <p class="text-xs font-medium text-bq-text-muted mt-0.5 truncate">{{ $booking->layanan->namalayanan ?? 'Standard Service' }}</p>
                        <div class="mt-1 flex items-center gap-2 text-[11px] text-bq-text-subtle">
                            <span>{{ $staffResourceDisplay }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-extrabold text-bq-primary">
                            Rp {{ number_format($booking->layanan->harga ?? 0, 0, ',', '.') }}
                        </p>
                        <span class="inline-block mt-0.5 text-[10px] font-bold px-1.5 py-0.2 rounded {{ $paymentStatus === 'sukses' || $booking->status === 'paid' || $booking->status === 'completed' ? 'text-emerald-700 bg-emerald-50' : 'text-amber-700 bg-amber-50' }}">
                            {{ $paymentStatus === 'sukses' || $booking->status === 'paid' || $booking->status === 'completed' ? 'Paid' : 'Unpaid' }}
                        </span>
                    </div>
                </div>

                {{-- Bottom Row: Direct Actions --}}
                <div class="flex items-center justify-between pt-2.5 border-t border-bq-border/60 gap-2">
                    <button type="button"
                        @click="viewBooking({{ json_encode($bookingData) }})"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-bq-border bg-bq-background/60 py-2 px-3 text-xs font-bold text-bq-text active:bg-slate-200 transition">
                        <svg class="h-3.5 w-3.5 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>Detail</span>
                    </button>

                    @if ($booking->status === 'pending')
                        <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="paid">
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-1 rounded-xl bg-emerald-600 py-2 px-3 text-xs font-bold text-white active:bg-emerald-700 transition shadow-2xs">
                                <span>✓ Lunas</span>
                            </button>
                        </form>
                    @elseif ($booking->status === 'paid')
                        <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-1 rounded-xl bg-indigo-600 py-2 px-3 text-xs font-bold text-white active:bg-indigo-700 transition shadow-2xs">
                                <span>✓ Selesai</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-8 text-center">
                <p class="text-sm font-semibold text-bq-text">No bookings found</p>
                <p class="text-xs text-bq-text-muted mt-1">When customers schedule sessions, they will appear here.</p>
            </div>
        @endforelse
    </div>

    {{-- ── Bookings Desktop Table (Screen >= sm) ── --}}
    <div class="hidden sm:block rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden" id="bookings-table-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="bookings-table">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 text-xs font-bold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Booking ID</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Customer</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Service</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Date &amp; Time</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Staff / Resource</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Amount</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">Payment</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">Status</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border text-xs">
                    @forelse ($daftarbooking as $booking)
                        @php
                            $paymentStatus = $booking->payment?->status ?? ($booking->status === 'paid' ? 'sukses' : 'pending');
                            $staffName = $booking->layanan?->staff?->pluck('name')->join(', ');
                            $resourceName = $booking->layanan?->resources?->pluck('name')->join(', ');
                            $staffResourceDisplay = $staffName ?: ($resourceName ?: 'General Staff / Spot');
                            $bookingData = [
                                'id' => $booking->id,
                                'code' => $booking->booking_code ?? ('BKQ-' . $booking->id),
                                'name' => $booking->namapelanggan,
                                'email' => $booking->email,
                                'phone' => $booking->nomorhp,
                                'service' => $booking->layanan->namalayanan ?? 'Standard Service',
                                'price' => $booking->layanan->harga ?? 0,
                                'formatted_price' => 'Rp ' . number_format($booking->layanan->harga ?? 0, 0, ',', '.'),
                                'date' => $booking->tanggalbooking ? $booking->tanggalbooking->format('d M Y') : '-',
                                'time' => $booking->jam,
                                'status' => $booking->status,
                                'payment_status' => $paymentStatus,
                                'staff' => $staffName ?: 'General Staff',
                                'resource' => $resourceName ?: 'General Facility',
                                'notes' => $booking->catatan ?? '-',
                                'order_id' => $booking->payment?->order_id ?? '-',
                                'snap_token' => $booking->payment?->snap_token ?? null,
                                'rescheduled_from_date' => $booking->rescheduled_from_date ? $booking->rescheduled_from_date->format('d M Y') : null,
                                'rescheduled_from_time' => $booking->rescheduled_from_time ?? null,
                                'manage_url' => $booking->booking_code ? route('booking.manage', $booking->booking_code) : null,
                            ];
                        @endphp
                        <tr class="transition-colors hover:bg-bq-background/40">
                            {{-- Booking ID --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5">
                                <span class="font-mono text-xs font-bold text-bq-primary bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                                    #{{ $booking->booking_code ?? $booking->id }}
                                </span>
                            </td>

                            {{-- Customer --}}
                            <td class="px-3.5 py-3 lg:px-4 lg:py-3.5 min-w-[130px] max-w-[180px]">
                                <div>
                                    <p class="text-xs sm:text-sm font-bold text-bq-text truncate" title="{{ $booking->namapelanggan }}">{{ $booking->namapelanggan }}</p>
                                    <div class="flex items-center gap-1.5 text-[11px] text-bq-text-muted mt-0.5 truncate">
                                        @if($booking->nomorhp)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->nomorhp) }}" target="_blank" class="hover:text-emerald-600 font-mono text-[11px]">
                                                {{ $booking->nomorhp }}
                                            </a>
                                        @elseif($booking->email)
                                            <span class="text-[11px] truncate">{{ $booking->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Service --}}
                            <td class="px-3.5 py-3 lg:px-4 lg:py-3.5">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 max-w-[140px] truncate" title="{{ $booking->layanan->namalayanan ?? 'Standard Service' }}">
                                    {{ $booking->layanan->namalayanan ?? 'Standard Service' }}
                                </span>
                            </td>

                            {{-- Date & Time --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5">
                                <p class="text-xs font-bold text-bq-text">{{ $booking->tanggalbooking ? $booking->tanggalbooking->format('d M Y') : '-' }}</p>
                                <p class="text-[11px] text-bq-text-muted font-mono mt-0.5">{{ substr($booking->jam, 0, 5) }} WIB</p>
                            </td>

                            {{-- Staff / Resource --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-xs text-bq-text-muted">
                                <div class="flex items-center gap-1.5" title="{{ $staffResourceDisplay }}">
                                    <span class="h-2 w-2 rounded-full shrink-0 {{ $staffName ? 'bg-indigo-500' : ($resourceName ? 'bg-sky-500' : 'bg-slate-400') }}"></span>
                                    <span class="max-w-[110px] truncate">{{ $staffResourceDisplay }}</span>
                                </div>
                            </td>

                            {{-- Amount --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-xs font-extrabold text-bq-text">
                                Rp {{ number_format($booking->layanan->harga ?? 0, 0, ',', '.') }}
                            </td>

                            {{-- Payment Status --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">
                                @if ($paymentStatus === 'sukses' || $booking->status === 'paid' || $booking->status === 'completed')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        Paid
                                    </span>
                                @elseif ($paymentStatus === 'expired')
                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-[10px] font-bold text-gray-700 ring-1 ring-inset ring-gray-600/20">
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        Unpaid
                                    </span>
                                @endif
                            </td>

                            {{-- Booking Status --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">
                                @php
                                    $warnastatus = match($booking->status) {
                                        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'paid'      => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                        'pending'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        default     => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                    $statusLabel = match($booking->status) {
                                        'paid'      => 'Confirmed',
                                        'pending'   => 'Pending',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                        default     => ucfirst($booking->status),
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset {{ $warnastatus }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- View Detail button --}}
                                    <button type="button"
                                        @click="viewBooking({{ json_encode($bookingData) }})"
                                        class="inline-flex items-center gap-1 rounded-lg border border-bq-border bg-bq-surface px-2.5 py-1.5 text-xs font-medium text-bq-text hover:bg-bq-background transition">
                                        <svg class="h-3.5 w-3.5 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Detail
                                    </button>

                                    {{-- FS-010: Dropdown aksi ubah status booking --}}
                                    @if (in_array($booking->status, ['paid', 'pending']))
                                        <div class="relative inline-block text-left" x-data="{ open: false }">
                                            <button @click="open = !open"
                                                class="inline-flex items-center gap-1 rounded-lg border border-bq-border bg-bq-surface px-2.5 py-1.5 text-xs font-medium text-bq-text-muted transition hover:border-bq-border-strong hover:text-bq-text"
                                                id="action-btn-{{ $booking->id }}">
                                                Status
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.outside="open = false"
                                                class="absolute right-0 z-20 mt-1 w-44 origin-top-right rounded-xl border border-bq-border bg-white shadow-xl overflow-hidden"
                                                style="display: none;">
                                                @if ($booking->status === 'pending')
                                                    <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="paid">
                                                        <button type="submit"
                                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                                            id="mark-paid-{{ $booking->id }}">
                                                            ✓ Konfirmasi Lunas
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($booking->status === 'paid')
                                                    <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit"
                                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                                            id="mark-completed-{{ $booking->id }}">
                                                            ✓ Tandai Selesai
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button"
                                                    @click="open = false; $dispatch('open-owner-reschedule', { booking: {{ json_encode($bookingData) }} })"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-indigo-700 hover:bg-indigo-50 border-t border-slate-100 cursor-pointer">
                                                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    📅 Ubah Jadwal
                                                </button>
                                                <form id="form-cancel-booking-{{ $booking->id }}" method="POST" action="{{ route('owner.bookings.status', $booking->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="button"
                                                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-rose-700 hover:bg-rose-50 border-t border-slate-100"
                                                        id="cancel-booking-{{ $booking->id }}"
                                                        @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan booking ini?', formId: 'form-cancel-booking-{{ $booking->id }}' })">
                                                        ✕ Batalkan Booking
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-sm text-bq-text-muted">
                                <div class="mx-auto max-w-sm text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-bq-text">No bookings found</p>
                                    <p class="text-xs text-bq-text-muted mt-1">When customers schedule sessions, they will appear here in real-time.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Pagination ── --}}
    @if ($daftarbooking->hasPages())
        <div class="flex justify-center">
            {{ $daftarbooking->appends(['status' => $filterstatus, 'katakunci' => $katakunci])->links() }}
        </div>
    @endif

    {{-- ── Centered Booking Detail Modal ── --}}
    <div x-show="detailOpen"
         class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="detailOpen = false">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="detailOpen = false"></div>

        {{-- Centered Dialog Card --}}
        <div x-show="detailOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] z-10 border border-[#e7e2f7]">

            {{-- Modal Header --}}
            <div class="p-4 sm:p-5 border-b border-[#e7e2f7] flex items-center justify-between bg-[#fbfaff]">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#b499ff]/30 shadow-2xs">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-extrabold text-[#231a3d]">Detail Reservasi</h3>
                            <span class="font-mono text-xs px-2 py-0.5 rounded-lg bg-[#f3effe] text-[#382186] font-bold border border-[#b499ff]/30" x-text="'#' + (activeBooking ? activeBooking.code : '')"></span>
                        </div>
                        <p class="text-[11px] text-[#6e6584]">Informasi lengkap pesanan dan aksi reservasi pelanggan.</p>
                    </div>
                </div>
                <button type="button" @click="detailOpen = false" class="rounded-xl p-2 text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition cursor-pointer" aria-label="Close modal">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                <template x-if="activeBooking">
                    <div class="space-y-4">
                        {{-- Status Banner --}}
                        <div class="flex items-center justify-between p-3.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]">
                            <div>
                                <p class="text-[10px] uppercase font-bold text-[#6e6584] tracking-wider">Status Booking</p>
                                <p class="text-xs sm:text-sm font-extrabold uppercase mt-0.5"
                                   :class="{
                                       'text-emerald-700': activeBooking.status === 'completed' || activeBooking.status === 'paid',
                                       'text-amber-700': activeBooking.status === 'pending',
                                       'text-rose-700': activeBooking.status === 'cancelled'
                                   }"
                                   x-text="activeBooking.status === 'paid' ? 'Confirmed' : activeBooking.status"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase font-bold text-[#6e6584] tracking-wider">Status Pembayaran</p>
                                <p class="text-xs sm:text-sm font-extrabold uppercase mt-0.5"
                                   :class="activeBooking.payment_status === 'sukses' ? 'text-emerald-700' : 'text-amber-700'"
                                   x-text="activeBooking.payment_status === 'sukses' ? 'Paid' : activeBooking.payment_status"></p>
                            </div>
                        </div>

                        {{-- Customer Card --}}
                        <div class="rounded-2xl border border-[#e7e2f7] p-4 space-y-2.5 bg-white shadow-2xs">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-[#6e6584] flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Data Pelanggan
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Nama Pelanggan:</span>
                                    <span class="font-bold text-[#231a3d]" x-text="activeBooking.name"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[#6e6584]">Nomor WhatsApp:</span>
                                    <a :href="'https://wa.me/' + (activeBooking.phone ? activeBooking.phone.replace(/[^0-9]/g, '') : '')" target="_blank" class="font-mono font-bold text-emerald-700 hover:underline flex items-center gap-1">
                                        <span x-text="activeBooking.phone || '-'"></span>
                                        <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1 py-0.2 rounded font-bold">Chat WA</span>
                                    </a>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Email:</span>
                                    <span class="text-[#231a3d] font-medium" x-text="activeBooking.email || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Catatan Pelanggan:</span>
                                    <span class="text-[#231a3d] italic text-right max-w-[200px]" x-text="activeBooking.notes || 'Tidak ada catatan'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Service & Schedule Card --}}
                        <div class="rounded-2xl border border-[#e7e2f7] p-4 space-y-2.5 bg-white shadow-2xs">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-[#6e6584] flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Layanan &amp; Jadwal
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Layanan:</span>
                                    <span class="font-bold text-[#231a3d]" x-text="activeBooking.service"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Tanggal:</span>
                                    <span class="font-bold text-[#231a3d]" x-text="activeBooking.date"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Waktu / Jam:</span>
                                    <span class="font-mono font-bold text-[#231a3d]" x-text="activeBooking.time"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Staff Ditugaskan:</span>
                                    <span class="font-medium text-[#231a3d]" x-text="activeBooking.staff || 'General Staff'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Fasilitas / Resource:</span>
                                    <span class="font-medium text-[#231a3d]" x-text="activeBooking.resource || 'General Facility'"></span>
                                </div>
                                <template x-if="activeBooking.rescheduled_from_date">
                                    <div class="mt-2 rounded-xl bg-amber-50 p-2.5 border border-amber-200 text-xs text-amber-800">
                                        <span class="font-semibold">Reschedule History:</span> Dipindahkan dari jadwal sebelumnya pada <span class="font-mono" x-text="activeBooking.rescheduled_from_date"></span> pukul <span class="font-mono" x-text="activeBooking.rescheduled_from_time || '-'"></span>.
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Payment Summary Card --}}
                        <div class="rounded-2xl border border-[#e7e2f7] p-4 space-y-2.5 bg-[#f8f6ff]">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-[#6e6584] flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Rincian Biaya &amp; Order
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-[#6e6584]">Total Biaya:</span>
                                    <span class="font-black text-[#382186] text-base" x-text="activeBooking.formatted_price"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Order ID:</span>
                                    <span class="font-mono text-xs text-[#6e6584]" x-text="activeBooking.order_id"></span>
                                </div>
                                <template x-if="activeBooking.manage_url">
                                    <div class="pt-2 border-t border-[#e7e2f7] flex items-center justify-between">
                                        <span class="text-xs text-[#6e6584]">Link Mandiri Pelanggan:</span>
                                        <a :href="activeBooking.manage_url" target="_blank" class="text-xs font-bold text-[#382186] hover:underline flex items-center gap-1">
                                            <span>Buka Halaman Manage</span>
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 sm:p-5 border-t border-[#e7e2f7] bg-[#fbfaff] flex items-center justify-between gap-2">
                <button type="button" @click="detailOpen = false" class="px-4 py-2 rounded-xl border border-[#e7e2f7] bg-white text-xs font-bold text-[#231a3d] hover:bg-[#f7f7fa] transition cursor-pointer shrink-0 shadow-2xs">
                    Tutup
                </button>
                <template x-if="activeBooking && (activeBooking.status === 'paid' || activeBooking.status === 'pending')">
                        {{-- Tombol Ubah Jadwal (Reschedule) Khusus Owner --}}
                        <button
                            type="button"
                            @click="detailOpen = false; $dispatch('open-owner-reschedule', { booking: activeBooking })"
                            class="craft-btn px-3.5 py-2 rounded-xl border border-indigo-200 text-indigo-700 bg-indigo-50 text-xs font-bold hover:bg-indigo-100 transition cursor-pointer flex items-center gap-1.5 shadow-2xs"
                            title="Ubah jadwal / slot reservasi"
                        >
                            <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Ubah Jadwal</span>
                        </button>

                        <template x-if="activeBooking.status === 'pending'">
                            <form method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="paid">
                                <button type="submit" class="craft-btn px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                    ✓ Konfirmasi Lunas
                                </button>
                            </form>
                        </template>
                        <template x-if="activeBooking.status === 'paid'">
                            <form method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="craft-btn px-3.5 py-2 rounded-xl bg-[#382186] hover:bg-[#2d1a6d] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                    ✓ Selesai
                                </button>
                            </form>
                        </template>
                        <form :id="'form-drawer-cancel-' + activeBooking.id" method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="button"
                                class="craft-btn px-3.5 py-2 rounded-xl border border-rose-200 text-rose-700 bg-rose-50 text-xs font-bold hover:bg-rose-100 transition cursor-pointer"
                                @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan booking ini?', formId: 'form-drawer-cancel-' + activeBooking.id })">
                                Batalkan
                            </button>
                        </form>
                    </div>
                </template>
            </div>

        </div>
    </div>

    {{-- Component Modal Reschedule Booking --}}
    <x-owner.modal-reschedule-booking />

</div>
@endsection

