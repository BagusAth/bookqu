@extends('layouts.owner-layout')

@section('title', 'Booking')

@section('content')
<div class="mx-auto max-w-7xl space-y-5" x-data
    @if ($initialPanelUrl)
        x-init="$nextTick(() => $dispatch('open-booking-panel', { url: @js($initialPanelUrl) }))"
    @endif
>
    <x-owner.page-header title="Booking" purpose="Semua booking, dikelompokkan menurut yang perlu Anda kerjakan." />
    <x-owner.tabs :tabs="$tabs" :active="$tab" :counts="$tabCounts" label="Bagian halaman Booking" />

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
            @php
                $actionTab = $tab === 'action';
                $columns = [$actionTab ? 'Sesi' : 'Jam', 'Pelanggan', 'Layanan', $actionTab ? 'Yang perlu dilakukan' : 'Status'];
                if ($canViewRevenue && ! $actionTab) {
                    $columns[] = 'Dibayar';
                }
            @endphp
            <x-ui.table :columns="$columns" label="Daftar booking" :actions="$actionTab">
                @foreach ($daftarbooking->getCollection()->groupBy(fn ($booking) => $booking->tanggalbooking?->toDateString()) as $bookingsOnDate)
                    <x-ui.table.row group>{{ $bookingsOnDate->first()->tanggalbooking?->translatedFormat('l, j M Y') }} · {{ $bookingsOnDate->count() }} booking</x-ui.table.row>
                    @foreach ($bookingsOnDate as $booking)
                        @php
                            $row = $rows[$booking->id];
                            $service = $booking->layanan?->namalayanan ?? 'Layanan dihapus';
                            [$statusLabel, $statusTone] = \App\Support\StatusBadge::for('booking', $booking->status);
                            $statusText = $actionTab ? ($row['reasons'][0] ?? $statusLabel) : $statusLabel;
                            $context = $row['time'].' · '.$service.($actionTab ? ' · '.$statusText : '');
                            $panel = route('owner.bookings.panel', $booking);
                        @endphp
                        <x-ui.table.row :two-lines="true" :muted="$booking->status === 'cancelled'">
                            <x-ui.table.cell>
                                <span class="bq-num font-bq-display text-bq-clock font-semibold">{{ $row['time'] }}</span>
                            </x-ui.table.cell>
                            <x-ui.table.cell phone="main" :title="$booking->namapelanggan">
                                <button type="button" class="max-w-full truncate text-left bq-link" title="{{ $booking->namapelanggan }}"
                                    data-booking-panel-url="{{ $panel }}" x-on:click="$dispatch('open-booking-panel', { url: $el.dataset.bookingPanelUrl, trigger: $el })">
                                    {{ $booking->namapelanggan }}
                                </button>
                                <x-slot:subtitle :title="$context"><span class="block truncate md:hidden">{{ $context }}</span></x-slot:subtitle>
                            </x-ui.table.cell>
                            <x-ui.table.cell :title="$service">
                                {{ $service }}
                                @include('owner.partials.bookings.participants')
                            </x-ui.table.cell>
                            <x-ui.table.cell :phone="$actionTab ? null : 'end'" :title="$statusText" wrap>
                                @if ($row['waiting'] && ! $actionTab)
                                    <span class="bq-badge bg-bq-warning-bg text-bq-warning">{{ $statusText }}</span>
                                    <p class="hidden text-bq-small text-bq-text-muted md:block">{{ $row['waiting'] }}</p>
                                @else
                                    <x-ui.status-dot :tone="$actionTab ? 'warning' : ($statusTone === 'info' ? 'brand' : $statusTone)">{{ $statusText }}</x-ui.status-dot>
                                @endif
                            </x-ui.table.cell>
                            @if ($canViewRevenue && ! $actionTab)
                                <x-ui.table.cell align="end"><span class="bq-num font-semibold">{{ $row['amount'] }}</span></x-ui.table.cell>
                            @endif
                            @if ($actionTab)
                                <x-slot:action>
                                    @if ($row['task'] === 'complete')
                                        <form id="complete-booking-{{ $booking->id }}" method="POST" action="{{ route('owner.bookings.status', $booking) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <x-ui.button size="sm"
                                                x-on:click="$dispatch('open-confirm', { title: 'Tandai booking selesai?', message: 'Pastikan sesi pelanggan sudah selesai.', formId: $el.form.id, confirmText: 'Tandai selesai' })">Tandai selesai</x-ui.button>
                                        </form>
                                    @elseif ($row['task'] === 'cancellation')
                                        <x-ui.button size="sm" href="#permintaan-pembatalan">Tinjau</x-ui.button>
                                    @else
                                        <x-ui.button size="sm" :data-booking-panel-url="$panel"
                                            x-on:click="$dispatch('open-booking-panel', { url: $el.dataset.bookingPanelUrl, trigger: $el })">{{ $row['task'] === 'refund' ? 'Putuskan refund' : 'Tinjau' }}</x-ui.button>
                                    @endif
                                </x-slot:action>
                            @endif
                        </x-ui.table.row>
                    @endforeach
                @endforeach
            </x-ui.table>
            <div class="mt-4">{{ $daftarbooking->links() }}</div>
        @endif
    </section>
</div>
@endsection
