@extends('layouts.owner-layout')

@section('title', 'Bookings')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Bookings Management',
        'subjudul' => 'View and manage all customer bookings.',
    ])

    {{-- ── Stats ── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @php
            $statbooking = [
                ['label' => 'All', 'nilai' => $totalbooking, 'warna' => 'bg-slate-50 text-slate-700', 'filter' => 'semua'],
                ['label' => 'Today', 'nilai' => $bookinghariini, 'warna' => 'bg-blue-50 text-blue-700', 'filter' => null],
                ['label' => 'Pending', 'nilai' => $bookingpending, 'warna' => 'bg-amber-50 text-amber-700', 'filter' => 'pending'],
                ['label' => 'Confirmed', 'nilai' => $bookingkonfirmasi, 'warna' => 'bg-indigo-50 text-indigo-700', 'filter' => 'paid'],
                ['label' => 'Completed', 'nilai' => $bookingselesai, 'warna' => 'bg-emerald-50 text-emerald-700', 'filter' => 'completed'],
                ['label' => 'Cancelled', 'nilai' => $bookingbatal, 'warna' => 'bg-rose-50 text-rose-700', 'filter' => 'cancelled'],
            ];
        @endphp
        @foreach ($statbooking as $stat)
            <a href="{{ $stat['filter'] ? '/owner/bookings?status=' . $stat['filter'] : '/owner/bookings' }}"
               class="rounded-xl border border-bq-border bg-bq-surface p-4 text-center transition-all hover:border-bq-border-strong hover:shadow-sm {{ $filterstatus === ($stat['filter'] ?? '') ? 'ring-2 ring-bq-primary ring-offset-1' : '' }}">
                <p class="text-2xl font-bold text-bq-text">{{ number_format($stat['nilai']) }}</p>
                <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $stat['warna'] }}">{{ $stat['label'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- ── Search & Filters ── --}}
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
        <div class="flex items-center gap-2">
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
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="bookings-table-card">
        <div class="overflow-x-auto">
            <table class="w-full" id="bookings-table">
                <thead>
                    <tr class="border-b border-bq-border">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Program</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Date & Time</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Amount</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @forelse ($daftarbooking as $booking)
                        <tr class="transition-colors hover:bg-bq-background/50">
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div>
                                    <p class="text-sm font-medium text-bq-text">{{ $booking->namapelanggan }}</p>
                                    <p class="text-xs text-bq-text-muted">{{ $booking->email }}</p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-bq-text-muted">{{ $booking->layanan->namalayanan ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <p class="text-sm text-bq-text">{{ $booking->tanggalbooking->format('d M Y') }}</p>
                                <p class="text-xs text-bq-text-muted">{{ $booking->jam }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-bq-text">
                                Rp {{ number_format($booking->layanan->harga ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-center">
                                @php
                                    $warnastatus = match($booking->status) {
                                        'completed', 'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase ring-1 ring-inset {{ $warnastatus }}">
                                    {{ $booking->status === 'paid' ? 'confirmed' : $booking->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-center">
                                <button class="rounded-lg p-1.5 text-bq-text-subtle transition-colors hover:bg-bq-background hover:text-bq-text">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-bq-text-muted">No bookings found.</td>
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

</div>
@endsection
