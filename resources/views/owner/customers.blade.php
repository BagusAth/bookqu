@extends('layouts.owner-layout')

@section('title', 'Pelanggan')

{{--
    Pelanggan (BQ-137, owner-portal.md §8.5): summary, quick filters, search and the directory.
    Data comes from App\Actions\Customer\ListCustomers; a row opens the shared customer panel.
    Spending is hidden when the viewer may not see revenue (AC-PLG-03).
    Visual style follows the existing owner pages (icon tiles, soft cards, compact table).
--}}

@php
    $filters = [
        '' => 'Semua',
        'new' => 'Baru',
        'regular' => 'Pelanggan tetap',
        'dormant' => 'Lama tidak datang',
    ];
    $filterHints = [
        'new' => 'Booking pertama bulan ini',
        'regular' => '3 booking atau lebih',
        'dormant' => 'Tidak booking 60 hari terakhir',
    ];
    $summaryCards = [
        ['label' => 'Total pelanggan', 'value' => $summary['total'], 'tile' => 'bg-indigo-50 text-indigo-600', 'valueClass' => 'text-bq-text',
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => 'Baru bulan ini', 'value' => $summary['new'], 'tile' => 'bg-emerald-50 text-emerald-600', 'valueClass' => 'text-emerald-600',
            'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
        ['label' => 'Kembali lagi', 'value' => $summary['returning'], 'tile' => 'bg-[#EEF2FF] text-[#4F46E5]', 'valueClass' => 'text-bq-primary',
            'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
    ];
    $detailButton = 'craft-btn inline-flex items-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-3 py-1.5 text-xs font-bold text-[#382186] shadow-2xs transition hover:bg-[#f3effe]';
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header title="Pelanggan" purpose="Siapa pelanggan Anda dan bagaimana menghubungi mereka." />

    {{-- ── Summary ── --}}
    <section aria-label="Ringkasan pelanggan" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($summaryCards as $item)
            <div class="flex items-center justify-between rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
                <div>
                    <p class="text-xs font-semibold text-bq-text-muted">{{ $item['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $item['valueClass'] }}">{{ number_format($item['value'], 0, ',', '.') }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $item['tile'] }}" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                </div>
            </div>
        @endforeach
    </section>

    {{-- ── Quick filters & search ── --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-[#e7e2f7] bg-white p-4 shadow-[0_4px_20px_rgba(35,26,61,0.03)] xl:flex-row xl:items-center xl:justify-between">
        <form method="GET" action="{{ route('owner.customers') }}" role="search" class="relative w-full xl:max-w-sm">
            @if ($filter)
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            <label for="customer-search" class="sr-only">Cari pelanggan</label>
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input id="customer-search" type="search" name="search" value="{{ $search }}" placeholder="Cari nama, email atau nomor HP..." class="w-full rounded-xl border border-[#e7e2f7] bg-white py-2 pl-9 pr-3.5 text-xs text-[#231a3d] placeholder-[#6e6584] shadow-2xs transition focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30">
        </form>
        <nav aria-label="Filter cepat" class="flex w-full items-center overflow-x-auto rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] p-1 text-xs no-scrollbar xl:w-auto">
            @foreach ($filters as $key => $label)
                @php $isActive = ($filter ?? '') === $key; @endphp
                <a
                    href="{{ route('owner.customers', array_filter(['filter' => $key !== '' ? $key : null, 'search' => $search !== '' ? $search : null])) }}"
                    @if ($isActive) aria-current="page" @endif
                    @isset($filterHints[$key]) title="{{ $filterHints[$key] }}" @endisset
                    class="shrink-0 whitespace-nowrap rounded-lg px-2.5 py-1 font-bold transition {{ $isActive ? 'bg-white text-[#382186] shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}"
                >{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    @if ($customers->total() === 0)
        @if ($search !== '' || $filter)
            <x-owner.empty-state title="Tidak ada pelanggan yang cocok" description="Ubah pencarian atau filter untuk melihat pelanggan lain." action-label="Tampilkan semua" :action-href="route('owner.customers')" />
        @else
            <x-owner.empty-state title="Belum ada pelanggan" description="Pelanggan muncul otomatis setelah booking pertama." />
        @endif
    @else
        {{-- ── Desktop table ── --}}
        <div class="hidden overflow-hidden rounded-2xl border border-bq-border bg-bq-surface shadow-xs md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                            <th scope="col" class="px-5 py-3.5">Pelanggan</th>
                            <th scope="col" class="px-5 py-3.5">Kontak</th>
                            <th scope="col" class="px-5 py-3.5 text-center">Booking</th>
                            @if ($canViewRevenue)
                                <th scope="col" class="px-5 py-3.5">Total dibayar</th>
                            @endif
                            <th scope="col" class="px-5 py-3.5">Terakhir</th>
                            <th scope="col" class="px-5 py-3.5">Berikutnya</th>
                            <th scope="col" class="px-5 py-3.5 text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bq-border">
                        @foreach ($customers as $customer)
                            <tr class="transition hover:bg-bq-background/40">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($customer->name ?: 'P', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="break-words text-sm font-semibold text-bq-text">{{ $customer->name ?: 'Tanpa nama' }}</p>
                                            @if ($customer->total_bookings >= \App\Actions\Customer\ListCustomers::REGULAR_MIN_BOOKINGS)
                                                <span class="inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">Pelanggan tetap</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="block whitespace-nowrap font-mono text-bq-text">{{ $customer->phone ?: '—' }}</span>
                                    @if ($customer->email)
                                        <span class="block max-w-[14rem] truncate text-bq-text-muted" title="{{ $customer->email }}">{{ $customer->email }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-center">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-800">{{ $customer->total_bookings }}</span>
                                </td>
                                @if ($canViewRevenue)
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold tabular-nums text-emerald-700">{{ $customer->formatted_spent }}</td>
                                @endif
                                <td class="whitespace-nowrap px-5 py-4 tabular-nums text-bq-text-muted">{{ $customer->last_booking }}</td>
                                <td class="whitespace-nowrap px-5 py-4 tabular-nums">
                                    @if ($customer->upcoming_booking)
                                        <span class="font-semibold text-indigo-700">{{ $customer->upcoming_booking }}</span>
                                    @else
                                        <span class="text-bq-text-muted">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <button type="button" class="{{ $detailButton }}" x-data x-on:click="$dispatch('open-customer-panel', { url: @js($customer->panel_url), trigger: $el })">Detail</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Phone cards (BQ-100 finding 2) ── --}}
        <ul class="space-y-3 md:hidden" aria-label="Daftar pelanggan">
            @foreach ($customers as $customer)
                <li class="rounded-2xl border border-bq-border bg-bq-surface p-4 shadow-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($customer->name ?: 'P', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="break-words text-sm font-semibold text-bq-text">{{ $customer->name ?: 'Tanpa nama' }}</p>
                                <p class="font-mono text-xs text-bq-text-muted">{{ $customer->phone ?: '—' }}</p>
                                @if ($customer->email)
                                    <p class="break-all text-[11px] text-bq-text-muted">{{ $customer->email }}</p>
                                @endif
                            </div>
                        </div>
                        <button type="button" class="{{ $detailButton }} shrink-0" x-data x-on:click="$dispatch('open-customer-panel', { url: @js($customer->panel_url), trigger: $el })">Detail</button>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1.5 border-t border-bq-border pt-3 text-[11px]">
                        <dt class="font-semibold uppercase tracking-wide text-bq-text-muted">Booking</dt><dd class="text-right font-bold tabular-nums text-bq-text">{{ $customer->total_bookings }}</dd>
                        @if ($canViewRevenue)
                            <dt class="font-semibold uppercase tracking-wide text-bq-text-muted">Total dibayar</dt><dd class="text-right font-semibold tabular-nums text-emerald-700">{{ $customer->formatted_spent }}</dd>
                        @endif
                        <dt class="font-semibold uppercase tracking-wide text-bq-text-muted">Terakhir</dt><dd class="text-right tabular-nums text-bq-text">{{ $customer->last_booking }}</dd>
                        <dt class="font-semibold uppercase tracking-wide text-bq-text-muted">Berikutnya</dt><dd class="text-right tabular-nums text-bq-text">{{ $customer->upcoming_booking ?? '—' }}</dd>
                    </dl>
                </li>
            @endforeach
        </ul>

        <div>{{ $customers->links() }}</div>
    @endif
</div>
@endsection
