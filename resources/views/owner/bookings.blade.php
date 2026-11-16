@extends('layouts.owner-layout')

@section('title', 'Booking')

@section('content')
<div class="mx-auto max-w-7xl space-y-5" x-data
    @if ($initialPanelUrl)
        x-init="$nextTick(() => $dispatch('open-booking-panel', { url: @js($initialPanelUrl) }))"
    @endif
>
    <x-owner.page-header title="Booking" purpose="Temukan booking dan tangani permintaan pelanggan.">
        @if ($canWalkIn)
            <x-slot:action>
                <button type="button" class="bq-btn bq-btn-primary" @click="$dispatch('open-dialog', { id: 'owner-walkin-dialog', trigger: $el })">+ Booking</button>
            </x-slot:action>
        @endif
    </x-owner.page-header>
    <x-owner.tabs :tabs="$tabs" :active="$tab" />

    <form method="GET" action="{{ route('owner.bookings') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <label class="grid gap-1 text-sm">Cari booking
            <input name="katakunci" value="{{ $filters['katakunci'] ?? '' }}" placeholder="Kode, nama, telepon, atau email" class="bq-input">
        </label>
        @foreach (['from' => 'Dari', 'to' => 'Sampai'] as $field => $label)
            <label class="grid gap-1 text-sm">{{ $label }}
                <input type="date" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}" @change="$el.form.requestSubmit()" class="bq-input">
            </label>
        @endforeach
        <label class="grid gap-1 text-sm">Layanan
            <select name="service" @change="$el.form.requestSubmit()" class="bq-input">
                <option value="">Semua layanan</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected($service->id == ($filters['service'] ?? null))>{{ $service->namalayanan }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm">Sumber
            <select name="source" @change="$el.form.requestSubmit()" class="bq-input">
                <option value="">Semua sumber</option>
                @foreach (['online' => 'Online', 'walkin' => 'Walk-in'] as $key => $label)
                    <option value="{{ $key }}" @selected($key === ($filters['source'] ?? null))>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm">Urutan sesi
            <select name="sort" @change="$el.form.requestSubmit()" class="bq-input">
                <option value="desc">Terbaru</option>
                <option value="asc" @selected(($filters['sort'] ?? null) === 'asc')>Terlama</option>
            </select>
        </label>
        <button class="bq-btn bq-btn-secondary">Cari</button>
        <a href="{{ route('owner.bookings', ['tab' => $tab]) }}" class="px-3 py-2 text-sm text-bq-primary underline">Reset filter</a>
        @if ($exportUrl)
            <a class="px-3 py-2 text-sm font-semibold text-bq-primary underline" href="{{ $exportUrl }}">Ekspor CSV</a>
        @endif
    </form>

    @if ($cancellationRequests?->isNotEmpty())
        <section id="permintaan-pembatalan" class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
            <h2 class="font-semibold">Permintaan pembatalan</h2>
            @foreach ($cancellationRequests as $item)
                <article class="space-y-2 border-t border-bq-border pt-3 text-sm">
                    <button type="button" class="font-semibold text-bq-primary underline"
                        @click="$dispatch('open-booking-panel', { url: @js(route('owner.bookings.panel', $item->booking_id)), trigger: $el })">
                        {{ $item->booking_code ?? 'BKQ-'.$item->booking_id }}
                    </button>
                    <p>{{ $item->namapelanggan }} · {{ $item->session }}</p>
                    <p>Diajukan oleh {{ $item->requester ?? 'Akun tidak tersedia' }} · {{ $item->reason }}</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach (['approve' => 'Setujui pembatalan', 'reject' => 'Tolak permintaan'] as $decision => $label)
                            <form id="cancellation-{{ $item->id }}-{{ $decision }}" method="POST" action="{{ route('owner.bookings.cancellation-requests.decide', $item->id) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="{{ $decision }}">
                                <button type="button" class="font-semibold {{ $decision === 'approve' ? 'text-bq-danger' : 'text-bq-primary' }}"
                                    @click="$dispatch('open-confirm', { title: @js($label.'?'), message: @js($decision === 'approve' ? 'Booking akan dibatalkan.' : 'Booking tetap berlaku.'), formId: $el.form.id, confirmText: @js($label) })">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>
                </article>
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
                            <tr class="cursor-pointer hover:bg-bq-background" @click="$el.querySelector('button').click()">
                                <td class="p-3">
                                    @include('owner.partials.bookings.panel-link')
                                    @foreach ($rows[$booking->id]['reasons'] as $reason)
                                        <p class="mt-1 text-xs font-medium text-bq-warning">{{ $reason }}</p>
                                    @endforeach
                                </td>
                                <td class="p-3">{{ $booking->namapelanggan }}</td>
                                <td class="p-3">
                                    {{ $booking->layanan?->namalayanan ?? 'Layanan dihapus' }}
                                    @include('owner.partials.bookings.participants')
                                </td>
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
                    <article class="cursor-pointer space-y-2 rounded-xl border border-bq-border bg-bq-surface p-4 text-sm" @click="$el.querySelector('button').click()">
                        <div class="flex justify-between gap-2">
                            @include('owner.partials.bookings.panel-link')
                            <x-owner.status-badge type="booking" :status="$booking->status" />
                        </div>
                        <p>{{ $booking->namapelanggan }} · {{ $booking->layanan?->namalayanan ?? 'Layanan dihapus' }}</p>
                        @include('owner.partials.bookings.participants')
                        @foreach ($rows[$booking->id]['reasons'] as $reason)
                            <p class="text-xs font-medium text-bq-warning">{{ $reason }}</p>
                        @endforeach
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
