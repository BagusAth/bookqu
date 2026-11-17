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

    <section role="tabpanel" aria-label="Daftar booking" class="bq-panel min-w-0">
        @php
            $pastTab = in_array($tab, ['completed', 'cancelled', 'all'], true);
            $activeFilters = collect(['range', 'service'])->filter(fn ($key) => filled($filters[$key] ?? null))->count();
        @endphp
        {{-- BQ-162: one compact filter row inside the list panel, as in the prototype (#/booking). --}}
        <form method="GET" action="{{ route('owner.bookings') }}" class="bq-filter" x-data="{ more: false }">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <x-ui.search name="katakunci" :value="$filters['katakunci'] ?? ''" label="Cari nama, kode atau layanan" />
            <div class="contents" :class="more ? '' : 'max-md:hidden'">
                <select name="range" aria-label="Tanggal" @change="$el.form.requestSubmit()" class="bq-input max-md:w-full">
                    @foreach (['' => 'Semua tanggal', 'today' => 'Hari ini', 'next7' => '7 hari ke depan', 'month' => 'Bulan ini'] as $key => $label)
                        <option value="{{ $key }}" @selected($key === ($filters['range'] ?? ''))>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="service" aria-label="Layanan" @change="$el.form.requestSubmit()" class="bq-input max-w-56 max-md:w-full max-md:max-w-none">
                    <option value="">Semua layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected($service->id == ($filters['service'] ?? null))>{{ $service->namalayanan }}</option>
                    @endforeach
                </select>
                @if (filled($filters['source'] ?? null))<input type="hidden" name="source" value="{{ $filters['source'] }}">@endif
                <select name="sort" aria-label="Urutkan" @change="$el.form.requestSubmit()" class="bq-input max-md:w-full">
                    <option value="desc">{{ $pastTab ? 'Terbaru dulu' : 'Sesi terjauh' }}</option>
                    <option value="asc" @selected(($filters['sort'] ?? null) === 'asc')>{{ $pastTab ? 'Terlama dulu' : 'Sesi terdekat' }}</option>
                </select>
            </div>
            <button type="button" class="bq-btn bq-btn-secondary bq-btn-sm md:hidden" @click="more = !more" :aria-expanded="more">
                <x-owner.icon name="filter" class="h-4 w-4" />Filter{{ $activeFilters ? ' ('.$activeFilters.')' : '' }}
            </button>
            <noscript><button class="bq-btn bq-btn-secondary bq-btn-sm">Cari</button></noscript>
            @if ($exportUrl)
                <a class="bq-btn bq-btn-secondary bq-btn-sm ml-auto max-md:hidden" href="{{ $exportUrl }}"><x-owner.icon name="download" class="h-4 w-4" />Ekspor CSV</a>
            @endif
        </form>
        @if ($daftarbooking->isEmpty())
            @if ($storefrontUrl)
                <x-ui.empty title="Belum ada booking" description="Booking masuk dari halaman booking Anda. Bagikan linknya ke pelanggan." icon="bookings">
                    <x-slot:action><x-ui.button variant="primary" :href="$storefrontUrl">Bagikan link booking</x-ui.button></x-slot:action>
                </x-ui.empty>
            @else
                <x-ui.empty :title="$emptyTitle" description="Coba tab lain atau ubah filter." icon="bookings" />
            @endif
        @else
            @php
                $actionTab = $tab === 'action';
                $columns = [$actionTab ? 'Sesi' : 'Jam', 'Pelanggan', 'Layanan', $actionTab ? 'Yang perlu dilakukan' : 'Status'];
                if ($canViewRevenue && ! $actionTab) {
                    $columns[] = 'Dibayar';
                }
            @endphp
            @php $bulk = $actionTab && $canComplete && $endedCount > 0; @endphp
            @if ($bulk)
                <form id="bulk-complete" method="POST" action="{{ route('owner.bookings.complete-many') }}" class="hidden">@csrf</form>
                <form id="bulk-complete-all" method="POST" action="{{ route('owner.bookings.complete-many') }}" class="hidden">@csrf<input type="hidden" name="all_ended" value="1"></form>
            @endif
            <x-ui.table class="rounded-none border-0" :columns="$columns" :widths="'auto minmax(160px, 1fr) minmax(0, 2fr) auto'.($canViewRevenue && ! $actionTab ? ' auto' : '')" label="Daftar booking" :actions="$actionTab" :selectable="$bulk"
                x-data="{ picked: 0 }" x-on:change="picked = $el.querySelectorAll('input[form=bulk-complete]:checked').length">
                @if ($bulk)
                    <x-slot:note>
                        <x-ui.table-note>
                            <span>{{ $endedCount }} sesi sudah lewat dan belum ditutup.</span>
                            <x-slot:action>
                                <span class="inline-flex flex-wrap gap-2">
                                    <x-ui.button size="sm" x-show="picked > 0" x-cloak
                                        x-on:click="$dispatch('open-confirm', { title: 'Tandai booking terpilih selesai?', message: 'Booking yang sedang minta refund atau pembatalan dilewati.', formId: 'bulk-complete', confirmText: 'Tandai selesai' })">
                                        Tandai selesai (<span x-text="picked"></span>)
                                    </x-ui.button>
                                    <x-ui.button size="sm"
                                        x-on:click="$dispatch('open-confirm', { title: 'Tandai {{ $endedCount }} booking selesai?', message: 'Semua sesi yang sudah lewat dan belum ditutup ditandai selesai, kecuali yang sedang minta refund atau pembatalan. Pelanggan yang tidak datang ubah satu per satu dari slip.', formId: 'bulk-complete-all', confirmText: 'Tandai {{ $endedCount }} selesai' })">
                                        Tandai selesai semua ({{ $endedCount }})
                                    </x-ui.button>
                                </span>
                            </x-slot:action>
                        </x-ui.table-note>
                    </x-slot:note>
                    <x-slot:selection><span class="sr-only">Pilih</span></x-slot:selection>
                @endif
                @foreach ($daftarbooking->getCollection()->groupBy(fn ($booking) => $actionTab ? 'all' : $booking->tanggalbooking?->toDateString()) as $bookingsOnDate)
                    @unless ($actionTab)
                        @php $groupDate = $bookingsOnDate->first()->tanggalbooking; @endphp
                        <x-ui.table.row group>{{ $groupDate?->isToday() ? 'Hari ini · ' : ($groupDate?->isTomorrow() ? 'Besok · ' : '') }}{{ $groupDate?->locale('id')->translatedFormat('l, j M') }} <span class="font-normal text-bq-text-muted">· {{ $bookingsOnDate->count() }} booking</span></x-ui.table.row>
                    @endunless
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
                            @if ($bulk)
                                <x-slot:selection>
                                    @if ($row['task'] === 'complete')
                                        <x-ui.checkbox name="ids[]" :value="$booking->id" form="bulk-complete" :label="'Pilih '.$booking->namapelanggan" hide-label />
                                    @endif
                                </x-slot:selection>
                            @endif
                            <x-ui.table.cell>
                                <span class="bq-num font-bq-display text-bq-clock font-semibold whitespace-nowrap">{{ str_replace(':', '.', $row['time']) }}</span>
                                @if ($actionTab)<span class="text-bq-small text-bq-text-muted max-md:hidden">{{ $booking->tanggalbooking?->locale('id')->translatedFormat('j M') }}</span>@endif
                            </x-ui.table.cell>
                            <x-ui.table.cell phone="main" :title="$booking->namapelanggan">
                                <button type="button" class="max-w-full truncate text-left font-semibold text-bq-ink hover:text-bq-primary" title="{{ $booking->namapelanggan }}"
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
            <x-ui.pager :paginator="$daftarbooking" noun="booking" />
        @endif
    </section>
</div>
@endsection
