@extends('layouts.owner-layout')

@section('title', 'Booking')

@section('content')
<div class="mx-auto max-w-7xl space-y-5" x-data
    @if ($initialPanelUrl)
        x-init="$nextTick(() => $dispatch('open-booking-panel', { url: @js($initialPanelUrl) }))"
    @endif
>
    <x-owner.page-header title="Booking" purpose="Temukan booking dan tangani permintaan pelanggan." />
    <x-owner.tabs :tabs="$tabs" :active="$tab" />

    @php
        $activeFilters = collect(['from', 'to', 'service', 'source'])->filter(fn ($key) => filled($filters[$key] ?? null))->count()
            + (($filters['sort'] ?? null) === 'asc' ? 1 : 0);
    @endphp
    <form method="GET" action="{{ route('owner.bookings') }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <x-ui.filter-bar :active="$activeFilters">
            <x-slot:search>
                <label class="grid gap-1.5"><span class="bq-label">Cari booking</span>
                    <span class="relative block">
                        <x-owner.icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" />
                        <input name="katakunci" value="{{ $filters['katakunci'] ?? '' }}" placeholder="Kode, nama, telepon, atau email" class="bq-input w-full pl-9">
                    </span>
                </label>
            </x-slot:search>
            @foreach (['from' => 'Dari', 'to' => 'Sampai'] as $field => $label)
                <label class="grid gap-1.5 max-md:w-[calc(50%-0.375rem)]"><span class="bq-label">{{ $label }}</span>
                    <input type="date" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}" @change="$el.form.requestSubmit()" class="bq-input">
                </label>
            @endforeach
            <label class="grid gap-1.5 max-md:w-full"><span class="bq-label">Layanan</span>
                <select name="service" @change="$el.form.requestSubmit()" class="bq-input">
                    <option value="">Semua layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected($service->id == ($filters['service'] ?? null))>{{ $service->namalayanan }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1.5 max-md:w-[calc(50%-0.375rem)]"><span class="bq-label">Sumber</span>
                <select name="source" @change="$el.form.requestSubmit()" class="bq-input">
                    <option value="">Semua sumber</option>
                    @foreach (['online' => 'Online', 'walkin' => 'Dicatat kasir'] as $key => $label)
                        <option value="{{ $key }}" @selected($key === ($filters['source'] ?? null))>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1.5 max-md:w-[calc(50%-0.375rem)]"><span class="bq-label">Urutan sesi</span>
                <select name="sort" @change="$el.form.requestSubmit()" class="bq-input">
                    <option value="desc">Terbaru</option>
                    <option value="asc" @selected(($filters['sort'] ?? null) === 'asc')>Terlama</option>
                </select>
            </label>
            <button class="bq-btn bq-btn-secondary">Cari</button>
            <x-slot:actions>
                <a href="{{ route('owner.bookings', ['tab' => $tab]) }}" class="bq-link">Reset filter</a>
                @if ($exportUrl)
                    <a class="bq-link inline-flex items-center gap-1" href="{{ $exportUrl }}"><x-owner.icon name="download" class="h-4 w-4" />Ekspor CSV</a>
                @endif
            </x-slot:actions>
        </x-ui.filter-bar>
    </form>

    @if ($cancellationRequests?->isNotEmpty())
        <section id="permintaan-pembatalan" class="space-y-3 rounded-xl border border-bq-warning/30 bg-bq-warning-bg/50 p-4 sm:p-5">
            <h2 class="flex items-center gap-2 text-base font-semibold text-bq-ink"><x-owner.icon name="alert" class="h-5 w-5 text-bq-warning" />Permintaan pembatalan</h2>
            @foreach ($cancellationRequests as $item)
                <article class="space-y-2 rounded-lg border border-bq-border bg-bq-surface p-3 text-sm">
                    <button type="button" class="bq-link"
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
                                <button type="button" class="bq-btn bq-btn-sm {{ $decision === 'approve' ? 'bq-btn-danger' : 'bq-btn-secondary' }}"
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
            <div class="bq-panel hidden overflow-x-auto sm:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-bq-border bg-bq-surface-2/70 text-xs text-bq-text-muted">
                        <tr>
                            @foreach (['Booking', 'Pelanggan', 'Layanan', 'Sesi (WIB)', 'Status booking', 'Pembayaran'] as $heading)
                                <th scope="col" class="px-4 py-3 font-semibold">{{ $heading }}</th>
                            @endforeach
                            @if ($canViewRevenue)<th scope="col" class="px-4 py-3 text-right font-semibold">Dibayar</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bq-border">
                        @foreach ($daftarbooking as $booking)
                            <tr class="cursor-pointer align-top transition-colors hover:bg-bq-primary-light/50" @click="$el.querySelector('button').click()">
                                <td class="px-4 py-3">
                                    @include('owner.partials.bookings.panel-link')
                                    @foreach ($rows[$booking->id]['reasons'] as $reason)
                                        <span class="bq-badge mt-1.5 bg-bq-accent-bg text-bq-ink">{{ $reason }}</span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 font-medium text-bq-ink">{{ $booking->namapelanggan }}</td>
                                <td class="px-4 py-3">
                                    {{ $booking->layanan?->namalayanan ?? 'Layanan dihapus' }}
                                    @include('owner.partials.bookings.participants')
                                </td>
                                <td class="bq-num px-4 py-3 whitespace-nowrap">{{ $booking->tanggalbooking?->translatedFormat('j M Y') }}<span class="block text-xs text-bq-text-muted">{{ $rows[$booking->id]['time'] }}</span></td>

                                <td class="px-4 py-3"><x-owner.status-badge type="booking" :status="$booking->status" /></td>
                                <td class="px-4 py-3"><x-owner.status-badge type="payment" :status="$rows[$booking->id]['payment_status']" />
                                    @if ($rows[$booking->id]['waiting'])
                                        <p class="mt-1 text-xs">{{ $rows[$booking->id]['waiting'] }}</p>
                                    @endif
                                </td>
                                @if ($canViewRevenue)<td class="bq-num px-4 py-3 text-right font-semibold whitespace-nowrap text-bq-ink">{{ $rows[$booking->id]['amount'] }}</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="space-y-3 sm:hidden">
                @foreach ($daftarbooking as $booking)
                    <article class="bq-panel cursor-pointer space-y-2 p-4 text-sm" @click="$el.querySelector('button').click()">
                        <div class="flex justify-between gap-2">
                            @include('owner.partials.bookings.panel-link')
                            <x-owner.status-badge type="booking" :status="$booking->status" />
                        </div>
                        <p><span class="font-semibold text-bq-ink">{{ $booking->namapelanggan }}</span> · {{ $booking->layanan?->namalayanan ?? 'Layanan dihapus' }}</p>
                        @include('owner.partials.bookings.participants')
                        @foreach ($rows[$booking->id]['reasons'] as $reason)
                            <span class="bq-badge bg-bq-accent-bg text-bq-ink">{{ $reason }}</span>
                        @endforeach
                        <p class="bq-num text-bq-text-muted">{{ $booking->tanggalbooking?->translatedFormat('j M Y') }} · {{ $rows[$booking->id]['time'] }} WIB</p>
                        <div class="flex items-center justify-between gap-2 border-t border-bq-border pt-2"><x-owner.status-badge type="payment" :status="$rows[$booking->id]['payment_status']" />@if ($canViewRevenue)<span class="bq-num font-semibold text-bq-ink">{{ $rows[$booking->id]['amount'] }}</span>@endif</div>
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
