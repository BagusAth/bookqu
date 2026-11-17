@extends('layouts.owner-layout')

@section('title', 'Pelanggan')

@php
    $tabs = ['' => 'Semua', 'new' => 'Baru', 'regular' => 'Tetap', 'dormant' => 'Lama tidak datang'];
    $dormant = $filter === 'dormant';
    $columns = ['Pelanggan', 'Booking'];
    if ($canViewRevenue) {
        $columns[] = 'Total dibayar';
    }
    $columns[] = 'Booking terakhir';
    if (! $dormant) {
        $columns[] = 'Berikutnya';
    }
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-5" x-data="{ greeting: null, message: '' }">
    <x-owner.page-header title="Pelanggan" purpose="Siapa pelanggan Anda, dan bagaimana menghubungi mereka." />
    <x-owner.tabs :tabs="$tabs" :counts="$counts" param="filter" :active="$filter ?? ''" label="Segmen pelanggan" />

    @if ($dormant)
        <p class="text-bq-text-muted">{{ $counts['dormant'] }} pelanggan belum booking lagi lebih dari 60 hari. Sapa membuka WhatsApp dengan pesan siap kirim, satu per satu.</p>
    @endif

    <form method="GET" action="{{ route('owner.customers') }}" role="search" class="flex flex-wrap gap-2">
        @if ($filter)
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <label for="customer-search" class="sr-only">Cari pelanggan</label>
        <x-ui.input id="customer-search" type="search" name="search" :value="$search" placeholder="Cari nama, telepon atau email" class="min-w-0 flex-1 sm:w-auto" />
        <label class="sr-only" for="customer-sort">Urutkan pelanggan</label>
        <x-ui.select id="customer-sort" name="sort" x-on:change="$el.form.requestSubmit()" class="w-auto max-w-full">
            @foreach ($sortOptions as $key => $option)
                <option value="{{ $key }}" @selected($sort === $key)>{{ $option }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button type="submit">Cari</x-ui.button>
    </form>

    @if ($customers->total() === 0)
        <x-ui.empty icon="customers" :title="$search !== '' || $filter ? 'Tidak ada pelanggan yang cocok' : 'Belum ada pelanggan'"
            :description="$search !== '' || $filter ? 'Ubah pencarian atau filter untuk melihat pelanggan lain.' : 'Pelanggan muncul otomatis setelah booking pertama.'" />
    @else
        <x-ui.table :columns="$columns" actions label="Daftar pelanggan">
            @foreach ($customers as $customer)
                @php
                    $name = $customer->name ?: 'Tanpa nama';
                    $context = $customer->total_bookings.' booking · terakhir '.$customer->last_booking;
                    $greeting = ['name' => $name, 'last' => $customer->last_booking, 'url' => $customer->whatsapp_url, 'message' => $customer->greeting];
                @endphp
                <x-ui.table.row two-lines>
                    <x-ui.table.cell phone="main">
                        <button type="button" class="flex max-w-full items-center gap-2 text-left bq-link"
                            data-customer-panel-url="{{ $customer->panel_url }}"
                            x-on:click="$dispatch('open-customer-panel', { url: $el.dataset.customerPanelUrl, trigger: $el })">
                            <x-ui.avatar :name="$name" class="shrink-0 max-md:hidden" />
                            <span class="min-w-0 truncate" title="{{ $name }}">{{ $name }}</span>
                        </button>
                        @if ($customer->segment && ! $dormant)
                            <x-ui.badge tone="neutral" class="mt-0.5 max-md:hidden">{{ $customer->segment }}</x-ui.badge>
                        @endif
                        <x-slot:subtitle :title="$context"><span class="block truncate md:hidden">{{ $context }}</span></x-slot:subtitle>
                    </x-ui.table.cell>
                    <x-ui.table.cell><span class="bq-num">{{ $customer->total_bookings }}</span></x-ui.table.cell>
                    @if ($canViewRevenue)
                        <x-ui.table.cell :phone="$dormant ? null : 'end'"><span class="bq-num font-semibold">{{ $customer->formatted_spent }}</span></x-ui.table.cell>
                    @endif
                    <x-ui.table.cell><span class="bq-num">{{ $customer->last_booking }}</span></x-ui.table.cell>
                    @unless ($dormant)
                        <x-ui.table.cell><span class="bq-num">{{ $customer->upcoming_booking ?? '—' }}</span></x-ui.table.cell>
                    @endunless
                    <x-slot:action>
                        @if ($dormant && $customer->whatsapp_url)
                            <x-ui.button size="sm" icon="whatsapp" data-greeting="{{ \Illuminate\Support\Js::encode($greeting) }}"
                                x-on:click="greeting = JSON.parse($el.dataset.greeting); message = greeting.message; $dispatch('open-dialog', { id: 'customer-greeting', trigger: $el })">Sapa</x-ui.button>
                        @elseif ($customer->whatsapp_url)
                            <x-ui.button variant="ghost" icon="whatsapp" icon-only :href="$customer->whatsapp_url" target="_blank" rel="noopener"
                                :aria-label="'WhatsApp '.$name" :title="'WhatsApp '.$name" />
                        @endif
                    </x-slot:action>
                </x-ui.table.row>
            @endforeach
        </x-ui.table>
        {{ $customers->links() }}
    @endif

    @if ($dormant)
        <x-owner.dialog id="customer-greeting" title="Sapa pelanggan">
            <p class="mb-4 text-bq-text-muted">Booking terakhir <span x-text="greeting?.last"></span>. Pesan dibuka di WhatsApp Anda; tidak ada yang terkirim otomatis.</p>
            <label for="greeting-text" class="bq-label">Pesan untuk <span x-text="greeting?.name"></span></label>
            <x-ui.textarea id="greeting-text" rows="4" x-model="message" class="mt-1" />
            <p class="mt-1 text-bq-small text-bq-text-muted">Ubah sesuai gaya Anda.</p>
            <x-slot:footer>
                <x-ui.button x-on:click="hide()">Batal</x-ui.button>
                <x-ui.button variant="primary" href="#" target="_blank" rel="noopener" icon="whatsapp"
                    x-bind:href="greeting ? greeting.url + '?text=' + encodeURIComponent(message) : '#'">Buka WhatsApp</x-ui.button>
            </x-slot:footer>
        </x-owner.dialog>
    @endif
</div>
@endsection
