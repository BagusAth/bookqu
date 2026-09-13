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
        'subjudul' => 'View, monitor, and manage all customer bookings and appointments.',
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
                ['label' => 'Today',     'nilai' => $bookinghariini,    'warna' => 'bg-blue-100 text-blue-700',     'filter' => null],
                ['label' => 'Pending',   'nilai' => $bookingpending,    'warna' => 'bg-amber-100 text-amber-800',   'filter' => 'pending'],
                ['label' => 'Confirmed', 'nilai' => $bookingkonfirmasi, 'warna' => 'bg-indigo-100 text-indigo-700', 'filter' => 'paid'],
                ['label' => 'Completed', 'nilai' => $bookingselesai,    'warna' => 'bg-emerald-100 text-emerald-800','filter' => 'completed'],
                ['label' => 'Cancelled', 'nilai' => $bookingbatal,      'warna' => 'bg-rose-100 text-rose-700',    'filter' => 'cancelled'],
            ];
        @endphp
        @foreach ($statbooking as $stat)
            <a href="{{ $stat['filter'] ? '/owner/bookings?status=' . $stat['filter'] : '/owner/bookings' }}"
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
            <input type="text" name="katakunci" value="{{ $katakunci }}" placeholder="Search by name, email, or phone..."
                class="w-full rounded-lg border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                id="input-search-bookings">
        </form>
        <div class="flex flex-wrap items-center gap-2">
            @foreach (['semua' => 'All', 'pending' => 'Pending', 'paid' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $kunci => $label)
                <a href="/owner/bookings?status={{ $kunci }}&katakunci={{ $katakunci }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium transition-all
                    {{ $filterstatus === $kunci
                        ? 'bg-bq-primary text-white shadow-sm'
                        : 'border border-bq-border bg-bq-surface text-bq-text-muted hover:border-bq-border-strong hover:text-bq-text'
                    }}"
                    id="filter-{{ $kunci }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- ── Bookings Table ── --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface shadow-sm overflow-hidden" id="bookings-table-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="bookings-table">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 text-xs font-semibold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-5 py-3.5">Booking ID</th>
                        <th class="px-5 py-3.5">Customer</th>
                        <th class="px-5 py-3.5">Service</th>
                        <th class="px-5 py-3.5">Date & Time</th>
                        <th class="px-5 py-3.5">Staff / Resource</th>
                        <th class="px-5 py-3.5">Amount</th>
                        <th class="px-5 py-3.5 text-center">Payment</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @forelse ($daftarbooking as $booking)
                        @php
                            $paymentStatus = $booking->payment?->status ?? ($booking->status === 'paid' ? 'sukses' : 'pending');
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
                                'notes' => $booking->catatan ?? '-',
                                'order_id' => $booking->payment?->order_id ?? '-',
                                'snap_token' => $booking->payment?->snap_token ?? null,
                            ];
                        @endphp
                        <tr class="transition-colors hover:bg-bq-background/40">
                            {{-- Booking ID --}}
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="font-mono text-xs font-semibold text-bq-primary bg-indigo-50 px-2 py-1 rounded">
                                    #{{ $booking->booking_code ?? $booking->id }}
                                </span>
                            </td>

                            {{-- Customer --}}
                            <td class="whitespace-nowrap px-5 py-4">
                                <div>
                                    <p class="text-sm font-semibold text-bq-text">{{ $booking->namapelanggan }}</p>
                                    <div class="flex items-center gap-2 text-xs text-bq-text-muted mt-0.5">
                                        @if($booking->email)
                                            <span>{{ $booking->email }}</span>
                                        @endif
                                        @if($booking->nomorhp)
                                            <span class="text-bq-text-subtle">•</span>
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->nomorhp) }}" target="_blank" class="hover:text-emerald-600 font-mono">
                                                {{ $booking->nomorhp }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Service --}}
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ $booking->layanan->namalayanan ?? 'Standard Service' }}
                                </span>
                            </td>

                            {{-- Date & Time --}}
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-medium text-bq-text">{{ $booking->tanggalbooking ? $booking->tanggalbooking->format('d M Y') : '-' }}</p>
                                <p class="text-xs text-bq-text-muted font-mono">{{ $booking->jam }}</p>
                            </td>

                            {{-- Staff / Resource --}}
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-bq-text-muted">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                    <span>General Staff / Spot</span>
                                </div>
                            </td>

                            {{-- Amount --}}
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-bq-text">
                                Rp {{ number_format($booking->layanan->harga ?? 0, 0, ',', '.') }}
                            </td>

                            {{-- Payment Status --}}
                            <td class="whitespace-nowrap px-5 py-4 text-center">
                                @if ($paymentStatus === 'sukses' || $booking->status === 'paid' || $booking->status === 'completed')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        Paid
                                    </span>
                                @elseif ($paymentStatus === 'expired')
                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-600/20">
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        Unpaid
                                    </span>
                                @endif
                            </td>

                            {{-- Booking Status --}}
                            <td class="whitespace-nowrap px-5 py-4 text-center">
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
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase ring-1 ring-inset {{ $warnastatus }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right">
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
                                                <form id="form-cancel-booking-{{ $booking->id }}" method="POST" action="{{ route('owner.bookings.status', $booking->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="button"
                                                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-rose-700 hover:bg-rose-50"
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

    {{-- ── Slide-Over Detail Drawer ── --}}
    <div x-show="detailOpen"
         class="fixed inset-0 z-50 overflow-hidden"
         style="display: none;"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="detailOpen = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="detailOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md bg-white shadow-2xl flex flex-col justify-between">
                
                {{-- Drawer Header --}}
                <div class="p-6 border-b border-bq-border flex items-center justify-between bg-slate-50/70">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-bq-text">Booking Details</h3>
                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold" x-text="'#' + (activeBooking ? activeBooking.code : '')"></span>
                        </div>
                        <p class="text-xs text-bq-text-muted mt-0.5">Comprehensive reservation summary and controls</p>
                    </div>
                    <button type="button" @click="detailOpen = false" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-200 hover:text-bq-text transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Drawer Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <template x-if="activeBooking">
                        <div class="space-y-6">
                            {{-- Status Badges Banner --}}
                            <div class="flex items-center justify-between p-4 rounded-xl border border-bq-border bg-bq-background/60">
                                <div>
                                    <p class="text-xs text-bq-text-muted font-medium">Booking Status</p>
                                    <p class="text-sm font-bold uppercase mt-0.5"
                                       :class="{
                                           'text-emerald-700': activeBooking.status === 'completed' || activeBooking.status === 'paid',
                                           'text-amber-700': activeBooking.status === 'pending',
                                           'text-rose-700': activeBooking.status === 'cancelled'
                                       }"
                                       x-text="activeBooking.status === 'paid' ? 'Confirmed' : activeBooking.status"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-bq-text-muted font-medium">Payment</p>
                                    <p class="text-sm font-bold uppercase mt-0.5"
                                       :class="activeBooking.payment_status === 'sukses' ? 'text-emerald-700' : 'text-amber-700'"
                                       x-text="activeBooking.payment_status === 'sukses' ? 'Paid' : activeBooking.payment_status"></p>
                                </div>
                            </div>

                            {{-- Customer Card --}}
                            <div class="rounded-xl border border-bq-border p-4 space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-bq-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Customer Information
                                </h4>
                                <div class="space-y-1.5 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Name:</span>
                                        <span class="font-semibold text-bq-text" x-text="activeBooking.name"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Phone:</span>
                                        <a :href="'https://wa.me/' + (activeBooking.phone ? activeBooking.phone.replace(/[^0-9]/g, '') : '')" target="_blank" class="font-mono text-emerald-700 hover:underline flex items-center gap-1">
                                            <span x-text="activeBooking.phone || '-'"></span>
                                            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1 rounded">WA</span>
                                        </a>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Email:</span>
                                        <span class="text-bq-text" x-text="activeBooking.email || '-'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Customer Notes:</span>
                                        <span class="text-bq-text italic text-xs text-right max-w-[200px]" x-text="activeBooking.notes || 'None'"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Service & Schedule Card --}}
                            <div class="rounded-xl border border-bq-border p-4 space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-bq-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Service & Schedule
                                </h4>
                                <div class="space-y-1.5 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Service:</span>
                                        <span class="font-semibold text-bq-text" x-text="activeBooking.service"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Date:</span>
                                        <span class="font-semibold text-bq-text" x-text="activeBooking.date"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Time Slot:</span>
                                        <span class="font-mono text-bq-text" x-text="activeBooking.time"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Staff / Resource:</span>
                                        <span class="text-bq-text text-xs">General Staff / Facility</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Summary Card --}}
                            <div class="rounded-xl border border-bq-border p-4 space-y-3 bg-slate-50/50">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-bq-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Payment Summary
                                </h4>
                                <div class="space-y-1.5 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Total Amount:</span>
                                        <span class="font-bold text-bq-primary text-base" x-text="activeBooking.formatted_price"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-bq-text-muted text-xs">Order ID:</span>
                                        <span class="font-mono text-xs text-bq-text-muted" x-text="activeBooking.order_id"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Drawer Footer --}}
                <div class="p-6 border-t border-bq-border bg-slate-50 flex items-center justify-between">
                    <button type="button" @click="detailOpen = false" class="px-4 py-2 rounded-lg border border-bq-border bg-white text-xs font-semibold text-bq-text hover:bg-slate-100 transition">
                        Tutup
                    </button>
                    <template x-if="activeBooking && (activeBooking.status === 'paid' || activeBooking.status === 'pending')">
                        <div class="flex items-center gap-2">
                            <template x-if="activeBooking.status === 'paid'">
                                <form method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition shadow-sm">
                                        ✓ Selesai
                                    </button>
                                </form>
                            </template>
                            <form :id="'form-drawer-cancel-' + activeBooking.id" method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="button"
                                    class="px-3.5 py-2 rounded-lg border border-rose-300 text-rose-700 bg-rose-50 text-xs font-semibold hover:bg-rose-100 transition"
                                    @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan booking ini?', formId: 'form-drawer-cancel-' + activeBooking.id })">
                                    Batalkan
                                </button>
                            </form>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

