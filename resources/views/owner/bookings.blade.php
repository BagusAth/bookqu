@extends('layouts.owner-layout')

@section('title', 'Booking')

@section('content')
<div class="mx-auto max-w-7xl space-y-5" x-data>
    <x-owner.page-header title="Booking" purpose="Temukan booking dan tangani permintaan pelanggan.">
        @if ($canWalkIn)
            <x-slot:action>
                <button type="button" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white" @click="$dispatch('open-dialog', { id: 'owner-walkin-dialog', trigger: $el })">+ Booking</button>
            </x-slot:action>
        @endif
    </x-owner.page-header>
    <x-owner.tabs :tabs="$tabs" :active="$tab" />

    <form method="GET" action="{{ route('owner.bookings') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <label class="grid gap-1 text-sm">Cari booking
            <input name="katakunci" value="{{ $filters['katakunci'] ?? '' }}" placeholder="Kode, nama, telepon, atau email" class="rounded border border-bq-border bg-bq-surface p-2">
        </label>
        @foreach (['from' => 'Dari', 'to' => 'Sampai'] as $field => $label)
            <label class="grid gap-1 text-sm">{{ $label }}
                <input type="date" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}" class="rounded border border-bq-border bg-bq-surface p-2">
            </label>
        @endforeach
        <label class="grid gap-1 text-sm">Layanan
            <select name="service" class="rounded border border-bq-border bg-bq-surface p-2">
                <option value="">Semua layanan</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected($service->id == ($filters['service'] ?? null))>{{ $service->namalayanan }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm">Sumber
            <select name="source" class="rounded border border-bq-border bg-bq-surface p-2">
                <option value="">Semua sumber</option>
                @foreach (['online' => 'Online', 'walkin' => 'Walk-in'] as $key => $label)
                    <option value="{{ $key }}" @selected($key === ($filters['source'] ?? null))>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm">Urutan sesi
            <select name="sort" class="rounded border border-bq-border bg-bq-surface p-2">
                <option value="desc">Terbaru</option>
                <option value="asc" @selected(($filters['sort'] ?? null) === 'asc')>Terlama</option>
            </select>
        </label>
        <button class="rounded-lg border border-bq-border px-3 py-2 text-sm font-semibold">Terapkan</button>
        @if ($exportUrl)
            <a class="px-3 py-2 text-sm font-semibold text-bq-primary underline" href="{{ $exportUrl }}">Ekspor CSV</a>
        @endif
    </form>

    @if ($cancellationRequests?->isNotEmpty())
        <section id="permintaan-pembatalan" class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
            <h2 class="font-semibold">Permintaan pembatalan</h2>
            @foreach ($cancellationRequests as $item)
                <form method="POST" action="{{ route('owner.bookings.cancellation-requests.decide', $item->id) }}" class="flex flex-wrap items-center gap-3 text-sm">
                    @csrf
                    @method('PATCH')
                    <span>Booking #{{ $item->booking_id }} · {{ $item->reason }}</span>
                    <button name="decision" value="approve" class="font-semibold text-bq-primary">Setujui pembatalan</button>
                    <button name="decision" value="reject" class="font-semibold text-bq-danger">Tolak</button>
                </form>
            @endforeach
            {{ $cancellationRequests->links() }}
        </section>
    @endif

    <section role="tabpanel" aria-label="Daftar booking">
        @if ($daftarbooking->isEmpty())
            @if ($storefrontUrl)
                <x-owner.empty-state title="Belum ada booking" description="Bagikan halaman booking agar pelanggan bisa memesan." action-label="Lihat halaman booking" :action-href="$storefrontUrl" />
            @else
                <x-owner.empty-state :title="$emptyTitle" description="Coba tab lain atau ubah filter." />
            @endif
        @else
            <div class="hidden overflow-x-auto rounded-xl border border-bq-border bg-bq-surface sm:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-bq-border text-bq-text-muted">
                        <tr>
                            @foreach (['Booking', 'Pelanggan', 'Layanan', 'Sesi (WIB)', 'Status booking', 'Pembayaran'] as $heading)
                                <th scope="col" class="p-3 font-medium">{{ $heading }}</th>
                            @endforeach
                            @if ($canViewRevenue)<th scope="col" class="p-3 font-medium">Dibayar</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bq-border">
                        @foreach ($daftarbooking as $booking)
                            <tr>
                                <td class="p-3">@include('owner.partials.bookings.panel-link')</td>
                                <td class="p-3">{{ $booking->namapelanggan }}</td>
                                <td class="p-3">{{ $booking->layanan?->namalayanan ?? 'Layanan dihapus' }}</td>
                                <td class="p-3 tabular-nums">{{ $booking->tanggalbooking?->format('d/m/Y') }} · {{ $rows[$booking->id]['time'] }}</td>

                                <td class="p-3"><x-owner.status-badge type="booking" :status="$booking->status" /></td>
                                <td class="p-3"><x-owner.status-badge type="payment" :status="$rows[$booking->id]['payment_status']" />
                                    @if ($rows[$booking->id]['waiting'])
                                        <p class="mt-1 text-xs">{{ $rows[$booking->id]['waiting'] }}</p>
                                    @endif
                                </td>
                                @if ($canViewRevenue)<td class="p-3 tabular-nums">{{ $rows[$booking->id]['amount'] }}</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="space-y-3 sm:hidden">
                @foreach ($daftarbooking as $booking)
                    <article class="space-y-2 rounded-xl border border-bq-border bg-bq-surface p-4 text-sm">
                        <div class="flex justify-between gap-2">
                            @include('owner.partials.bookings.panel-link')
                            <x-owner.status-badge type="booking" :status="$booking->status" />
                        </div>
                        <p>{{ $booking->namapelanggan }} · {{ $booking->layanan?->namalayanan ?? 'Layanan dihapus' }}</p>
                        <p class="tabular-nums">{{ $booking->tanggalbooking?->format('d/m/Y') }} · {{ $rows[$booking->id]['time'] }} WIB</p>
                        <p class="tabular-nums">@if ($canViewRevenue) Dibayar: {{ $rows[$booking->id]['amount'] }} · @endif <x-owner.status-badge type="payment" :status="$rows[$booking->id]['payment_status']" /></p>
                        @if ($rows[$booking->id]['waiting'])
                            <p class="text-xs">{{ $rows[$booking->id]['waiting'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $daftarbooking->links() }}</div>
        @endif
    </section>
</div>
@endsection
