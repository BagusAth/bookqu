@extends('layouts.owner-layout')

@section('title', 'Pelanggan')

{{--
    Pelanggan (BQ-137, owner-portal.md §8.5): summary, quick filters, search and the directory.
    Data comes from App\Actions\Customer\ListCustomers; a row opens the shared customer panel.
    Spending is hidden when the viewer may not see revenue (AC-PLG-03).
--}}

@php
    $filters = [
        null => 'Semua',
        'new' => 'Baru',
        'regular' => 'Pelanggan tetap',
        'dormant' => 'Lama tidak datang',
    ];
    $filterHints = [
        'new' => 'Booking pertama bulan ini',
        'regular' => '3 booking atau lebih',
        'dormant' => 'Tidak booking 60 hari terakhir',
    ];
    $openButton = 'inline-flex items-center rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-background';
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header title="Pelanggan" purpose="Siapa pelanggan Anda dan bagaimana menghubungi mereka." />

    <section aria-label="Ringkasan pelanggan" class="grid grid-cols-3 gap-3">
        @foreach ([['Total pelanggan', $summary['total']], ['Baru bulan ini', $summary['new']], ['Kembali lagi', $summary['returning']]] as [$label, $value])
            <div class="rounded-xl border border-bq-border bg-bq-surface p-3 sm:p-4">
                <p class="text-xs text-bq-text-muted sm:text-sm">{{ $label }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-bq-text sm:text-2xl">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </section>

    <div class="space-y-3">
        <nav aria-label="Filter cepat" class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
            @foreach ($filters as $key => $label)
                @php $isActive = ($filter ?? null) === ($key === '' ? null : $key); @endphp
                <a
                    href="{{ route('owner.customers', array_filter(['filter' => $key ?: null, 'search' => $search !== '' ? $search : null])) }}"
                    @if ($isActive) aria-current="page" @endif
                    @isset($filterHints[$key]) title="{{ $filterHints[$key] }}" @endisset
                    class="shrink-0 rounded-full border px-3 py-1.5 text-sm font-medium {{ $isActive ? 'border-bq-primary bg-bq-primary-light text-bq-primary' : 'border-bq-border bg-bq-surface text-bq-text-muted hover:text-bq-text' }}"
                >{{ $label }}</a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('owner.customers') }}" role="search" class="flex gap-2">
            @if ($filter)
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            <label for="customer-search" class="sr-only">Cari pelanggan</label>
            <input id="customer-search" type="search" name="search" value="{{ $search }}" placeholder="Cari nama, email atau nomor HP" class="w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/30">
            <button type="submit" class="{{ $openButton }} py-2">Cari</button>
        </form>
    </div>

    @if ($customers->total() === 0)
        @if ($search !== '' || $filter)
            <x-owner.empty-state title="Tidak ada pelanggan yang cocok" description="Ubah pencarian atau filter untuk melihat pelanggan lain." action-label="Tampilkan semua" :action-href="route('owner.customers')" />
        @else
            <x-owner.empty-state title="Belum ada pelanggan" description="Pelanggan muncul otomatis setelah booking pertama." />
        @endif
    @else
        {{-- Desktop table --}}
        <div class="hidden overflow-hidden rounded-xl border border-bq-border bg-bq-surface md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-bq-border bg-bq-background text-xs text-bq-text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Pelanggan</th>
                        <th scope="col" class="px-4 py-3 font-medium">Kontak</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Booking</th>
                        @if ($canViewRevenue)
                            <th scope="col" class="px-4 py-3 text-right font-medium">Total dibayar</th>
                        @endif
                        <th scope="col" class="px-4 py-3 font-medium">Terakhir</th>
                        <th scope="col" class="px-4 py-3 font-medium">Berikutnya</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="px-4 py-3 font-medium text-bq-text">{{ $customer->name ?: 'Tanpa nama' }}</td>
                            <td class="px-4 py-3 text-bq-text-muted">
                                <span class="block tabular-nums">{{ $customer->phone ?: '-' }}</span>
                                @if ($customer->email)
                                    <span class="block break-all text-xs">{{ $customer->email }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $customer->total_bookings }}</td>
                            @if ($canViewRevenue)
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $customer->formatted_spent }}</td>
                            @endif
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-bq-text-muted">{{ $customer->last_booking }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-bq-text-muted">{{ $customer->upcoming_booking ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" class="{{ $openButton }}" x-data x-on:click="$dispatch('open-customer-panel', { url: @js($customer->panel_url), trigger: $el })">Detail</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Phone cards (BQ-100 finding 2) --}}
        <ul class="space-y-3 md:hidden" aria-label="Daftar pelanggan">
            @foreach ($customers as $customer)
                <li class="rounded-xl border border-bq-border bg-bq-surface p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="break-words font-medium text-bq-text">{{ $customer->name ?: 'Tanpa nama' }}</p>
                            <p class="text-sm tabular-nums text-bq-text-muted">{{ $customer->phone ?: '-' }}</p>
                            @if ($customer->email)
                                <p class="break-all text-xs text-bq-text-muted">{{ $customer->email }}</p>
                            @endif
                        </div>
                        <button type="button" class="{{ $openButton }} shrink-0" x-data x-on:click="$dispatch('open-customer-panel', { url: @js($customer->panel_url), trigger: $el })">Detail</button>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                        <dt class="text-bq-text-muted">Booking</dt><dd class="text-right tabular-nums text-bq-text">{{ $customer->total_bookings }}</dd>
                        @if ($canViewRevenue)
                            <dt class="text-bq-text-muted">Total dibayar</dt><dd class="text-right tabular-nums text-bq-text">{{ $customer->formatted_spent }}</dd>
                        @endif
                        <dt class="text-bq-text-muted">Terakhir</dt><dd class="text-right tabular-nums text-bq-text">{{ $customer->last_booking }}</dd>
                        <dt class="text-bq-text-muted">Berikutnya</dt><dd class="text-right tabular-nums text-bq-text">{{ $customer->upcoming_booking ?? '-' }}</dd>
                    </dl>
                </li>
            @endforeach
        </ul>

        <div>{{ $customers->links() }}</div>
    @endif
</div>
@endsection
