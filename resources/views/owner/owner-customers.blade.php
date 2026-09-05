@extends('layouts.owner-layout')

@section('title', 'Customers CRM')

@section('content')
<div class="mx-auto max-w-7xl space-y-6"
     x-data="{
         detailOpen: false,
         loading: false,
         activeCustomer: null,
         activeTab: 'overview',
         openCustomer(identifier) {
             this.loading = true;
             this.detailOpen = true;
             this.activeTab = 'overview';
             this.activeCustomer = null;

             fetch(`{{ route('owner.customers.detail') }}?identifier=${encodeURIComponent(identifier)}`, {
                 headers: { 'X-Requested-With': 'XMLHttpRequest' }
             })
             .then(r => r.json())
             .then(data => {
                 this.activeCustomer = data;
                 this.loading = false;
             })
             .catch(() => {
                 this.detailOpen = false;
                 this.loading = false;
             });
         },
         saveNote() {
             if (!this.activeCustomer) return;
             const form = document.getElementById('form-customer-note');
             const fd = new FormData(form);
             fetch('{{ route('owner.customers.note') }}', {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'X-Requested-With': 'XMLHttpRequest',
                 },
                 body: fd,
             })
             .then(r => r.json())
             .then(() => {
                 $dispatch('toast', { message: 'Catatan berhasil disimpan!', type: 'success' });
             });
         }
     }">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul'    => 'Customers CRM',
        'subjudul' => 'Direktori data pelanggan, riwayat pemesanan, nilai transaksi, dan catatan internal.',
    ])

    {{-- ── Flash Messages ── --}}
    @if (session('sukses'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('sukses') }}</span>
        </div>
    @endif

    {{-- ── Summary Cards ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-bq-text-muted">Total Unique Customers</p>
                <p class="text-2xl font-bold text-bq-text mt-1">{{ number_format($totalCustomers) }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-bq-text-muted">Total Customer Spending</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">Rp {{ number_format($totalSpentAll, 0, ',', '.') }}</p>
                <p class="text-[10px] text-bq-text-muted mt-0.5">Dari pembayaran sukses</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-bq-text-muted">Total Bookings Recorded</p>
                <p class="text-2xl font-bold text-bq-primary mt-1">{{ number_format($totalBookingsAll) }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>
    </div>

    {{-- ── Search (server-side) ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('owner.customers') }}" class="relative w-full sm:max-w-xs flex gap-2">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama, email, nomor HP..."
                       id="input-customer-search"
                       class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
            </div>
            <button type="submit" class="rounded-xl bg-bq-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Cari</button>
            @if($search)
                <a href="{{ route('owner.customers') }}" class="rounded-xl border border-bq-border px-3 py-2 text-xs font-semibold text-bq-text hover:bg-bq-background transition">×</a>
            @endif
        </form>
        <p class="text-xs text-bq-text-muted">
            @if($search)
                Hasil pencarian "<span class="font-semibold text-bq-text">{{ $search }}</span>" —
            @endif
            <span class="font-bold text-bq-text">{{ $customers->total() }}</span> customer ditemukan
        </p>
    </div>

    {{-- ── Customer Table ── --}}
    @if($customers->isNotEmpty())
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-5 py-3.5">Customer</th>
                        <th class="px-5 py-3.5">Phone (WhatsApp)</th>
                        <th class="px-5 py-3.5">Email</th>
                        <th class="px-5 py-3.5 text-center">Total Bookings</th>
                        <th class="px-5 py-3.5">Total Spending</th>
                        <th class="px-5 py-3.5">Last Booking</th>
                        <th class="px-5 py-3.5">Upcoming</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @foreach($customers as $c)
                    <tr class="hover:bg-bq-background/40 transition">
                        {{-- Name + VIP badge --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 text-xs shrink-0">
                                    {{ strtoupper(substr($c->name ?: 'C', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-bq-text text-sm">{{ $c->name ?: 'Customer' }}</p>
                                    @if($c->total_bookings >= 3)
                                        <span class="inline-flex rounded bg-amber-50 text-amber-700 px-1.5 py-0.5 text-[10px] font-bold">VIP Regular</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Phone --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($c->phone && $c->phone !== '-')
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c->phone) }}" target="_blank"
                                   class="inline-flex items-center gap-1 font-mono text-emerald-700 hover:underline">
                                    {{ $c->phone }}
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1 rounded">WA</span>
                                </a>
                            @else
                                <span class="text-bq-text-muted">—</span>
                            @endif
                        </td>

                        {{-- Email --}}
                        <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">{{ $c->email ?: '—' }}</td>

                        {{-- Total Bookings --}}
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            <span class="rounded-full bg-slate-100 text-slate-800 font-bold px-2.5 py-0.5 text-xs">{{ $c->total_bookings }}</span>
                        </td>

                        {{-- Total Spending (from payments) --}}
                        <td class="px-5 py-4 whitespace-nowrap font-semibold text-emerald-700">
                            {{ $c->formatted_spent }}
                        </td>

                        {{-- Last Booking --}}
                        <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">{{ $c->last_booking }}</td>

                        {{-- Upcoming --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($c->upcoming_booking)
                                <span class="inline-flex rounded-full bg-indigo-50 text-indigo-700 px-2 py-0.5 text-[11px] font-medium">
                                    {{ $c->upcoming_booking }}
                                </span>
                            @else
                                <span class="text-bq-text-muted text-[11px]">—</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <button type="button"
                                    @click="openCustomer('{{ addslashes($c->identifier) }}')"
                                    id="btn-view-customer-{{ $loop->index }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-bq-border bg-bq-surface px-2.5 py-1.5 text-xs font-semibold text-bq-text hover:bg-bq-background transition">
                                <svg class="h-3.5 w-3.5 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail CRM
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
        <div class="border-t border-bq-border px-5 py-4">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    @else
    {{-- ── Empty State ── --}}
    <div class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        </div>
        @if($search)
            <h3 class="text-sm font-bold text-bq-text">Tidak ada customer yang cocok</h3>
            <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">
                Pencarian "<strong>{{ $search }}</strong>" tidak menemukan customer. Coba ubah kata kunci pencarian.
            </p>
            <a href="{{ route('owner.customers') }}" class="mt-4 inline-flex items-center gap-1 rounded-xl border border-bq-border px-4 py-2 text-xs font-semibold text-bq-text hover:bg-bq-background transition">
                Tampilkan semua customer
            </a>
        @else
            <h3 class="text-sm font-bold text-bq-text">Belum ada data customer</h3>
            <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">
                Saat customer melakukan booking pada halaman publik bisnis Anda, profil dan riwayat mereka otomatis terakumulasi di sini.
            </p>
        @endif
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

        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="detailOpen = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="detailOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-lg bg-white shadow-2xl flex flex-col">

                {{-- Drawer Header --}}
                <div class="p-6 border-b border-bq-border bg-slate-50/70 flex items-center justify-between shrink-0">
                    <template x-if="loading">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-2xl bg-slate-200 animate-pulse"></div>
                            <div class="space-y-2">
                                <div class="h-4 w-32 rounded bg-slate-200 animate-pulse"></div>
                                <div class="h-3 w-24 rounded bg-slate-200 animate-pulse"></div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!loading && activeCustomer">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white font-bold text-base">
                                <span x-text="activeCustomer.name ? activeCustomer.name.charAt(0).toUpperCase() : 'C'"></span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-bq-text" x-text="activeCustomer.name"></h3>
                                <p class="text-xs text-bq-text-muted" x-text="activeCustomer.email"></p>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="detailOpen = false"
                            class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-200 hover:text-bq-text transition shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="border-b border-bq-border px-6 flex items-center gap-4 text-xs font-semibold shrink-0">
                    <button type="button" @click="activeTab = 'overview'" id="tab-overview"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'overview' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Overview
                    </button>
                    <button type="button" @click="activeTab = 'history'" id="tab-history"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'history' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Booking History
                    </button>
                    <button type="button" @click="activeTab = 'payments'" id="tab-payments"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'payments' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Payments
                    </button>
                    <button type="button" @click="activeTab = 'notes'" id="tab-notes"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'notes' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Notes
                    </button>
                </div>

                {{-- Drawer Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-4">

                    {{-- Loading skeleton --}}
                    <template x-if="loading">
                        <div class="space-y-3">
                            <div class="h-20 rounded-xl bg-slate-100 animate-pulse"></div>
                            <div class="h-32 rounded-xl bg-slate-100 animate-pulse"></div>
                        </div>
                    </template>

                    <template x-if="!loading && activeCustomer">
                        <div>

                            {{-- TAB 1: OVERVIEW --}}
                            <div x-show="activeTab === 'overview'" class="space-y-4">
                                {{-- Stats grid --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Total Spent (LTV)</p>
                                        <p class="text-base font-bold text-emerald-700 mt-0.5" x-text="activeCustomer.formatted_spent"></p>
                                    </div>
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Rata-rata Transaksi</p>
                                        <p class="text-base font-bold text-bq-primary mt-0.5" x-text="activeCustomer.avg_transaction"></p>
                                    </div>
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Total Appointments</p>
                                        <p class="text-base font-bold text-bq-text mt-0.5" x-text="activeCustomer.total_bookings + ' bookings'"></p>
                                    </div>
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Bergabung Sejak</p>
                                        <p class="text-sm font-semibold text-bq-text mt-0.5" x-text="activeCustomer.first_seen"></p>
                                    </div>
                                </div>

                                {{-- Contact --}}
                                <div class="rounded-xl border border-bq-border p-4 space-y-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Contact Info</h4>
                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Phone / WhatsApp:</span>
                                            <a :href="'https://wa.me/' + (activeCustomer.phone ? activeCustomer.phone.replace(/[^0-9]/g, '') : '')"
                                               target="_blank"
                                               class="font-mono text-emerald-700 font-bold hover:underline"
                                               x-text="activeCustomer.phone"></a>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Email:</span>
                                            <span class="font-medium text-bq-text" x-text="activeCustomer.email"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Last Visited:</span>
                                            <span class="text-bq-text" x-text="activeCustomer.last_booking"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Next Scheduled:</span>
                                            <template x-if="activeCustomer.upcoming_booking">
                                                <span class="font-semibold text-indigo-600" x-text="activeCustomer.upcoming_booking"></span>
                                            </template>
                                            <template x-if="!activeCustomer.upcoming_booking">
                                                <span class="text-bq-text-muted">—</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Services Used --}}
                                <div class="rounded-xl border border-bq-border p-4 space-y-2">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Services Used</h4>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="svc in (activeCustomer.services_used || [])" :key="svc">
                                            <span class="rounded-lg bg-indigo-50 text-indigo-700 px-2.5 py-1 text-xs font-medium" x-text="svc"></span>
                                        </template>
                                        <template x-if="!activeCustomer.services_used || activeCustomer.services_used.length === 0">
                                            <span class="text-bq-text-subtle text-xs">Belum ada layanan</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 2: BOOKING HISTORY --}}
                            <div x-show="activeTab === 'history'" style="display:none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Riwayat Booking</h4>
                                <template x-if="activeCustomer.bookings && activeCustomer.bookings.length > 0">
                                    <div class="divide-y divide-bq-border rounded-xl border border-bq-border overflow-hidden">
                                        <template x-for="b in activeCustomer.bookings" :key="b.id">
                                            <div class="p-3.5 hover:bg-slate-50 transition text-xs space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <span class="font-semibold text-bq-text" x-text="b.service"></span>
                                                        <p class="text-[10px] font-mono text-bq-text-muted" x-text="b.code"></p>
                                                    </div>
                                                    <span class="font-mono font-bold text-bq-primary" x-text="b.price"></span>
                                                </div>
                                                <div class="flex items-center justify-between text-bq-text-muted text-[11px]">
                                                    <span x-text="b.date + ' • ' + b.time"></span>
                                                    <span class="uppercase font-semibold px-2 py-0.5 rounded text-[10px]"
                                                          :class="{
                                                              'bg-emerald-50 text-emerald-700': b.status === 'completed',
                                                              'bg-indigo-50 text-indigo-700': b.status === 'paid',
                                                              'bg-amber-50 text-amber-700': b.status === 'pending',
                                                              'bg-rose-50 text-rose-700': b.status === 'cancelled'
                                                          }"
                                                          x-text="b.status === 'paid' ? 'Confirmed' : b.status.charAt(0).toUpperCase() + b.status.slice(1)">
                                                    </span>
                                                </div>
                                                <template x-if="b.notes">
                                                    <p class="text-[11px] text-bq-text-muted italic" x-text="'Catatan: ' + b.notes"></p>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!activeCustomer.bookings || activeCustomer.bookings.length === 0">
                                    <p class="text-xs text-bq-text-muted text-center py-8">Tidak ada riwayat booking.</p>
                                </template>
                            </div>

                            {{-- TAB 3: PAYMENTS --}}
                            <div x-show="activeTab === 'payments'" style="display:none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Riwayat Pembayaran</h4>
                                <template x-if="activeCustomer.payments && activeCustomer.payments.length > 0">
                                    <div class="divide-y divide-bq-border rounded-xl border border-bq-border overflow-hidden">
                                        <template x-for="p in activeCustomer.payments" :key="p.order_id">
                                            <div class="p-3.5 hover:bg-slate-50 transition text-xs space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <span class="font-semibold text-bq-text" x-text="p.service"></span>
                                                        <p class="text-[10px] font-mono text-bq-text-muted" x-text="p.order_id"></p>
                                                    </div>
                                                    <span class="font-mono font-bold text-emerald-700" x-text="p.jumlah"></span>
                                                </div>
                                                <div class="flex items-center justify-between text-bq-text-muted text-[11px]">
                                                    <span x-text="p.date"></span>
                                                    <span class="uppercase font-semibold px-2 py-0.5 rounded text-[10px]"
                                                          :class="{
                                                              'bg-emerald-50 text-emerald-700': p.status === 'sukses',
                                                              'bg-amber-50 text-amber-700': p.status === 'pending',
                                                              'bg-rose-50 text-rose-700': p.status === 'gagal'
                                                          }"
                                                          x-text="p.status === 'sukses' ? 'Sukses' : p.status === 'pending' ? 'Pending' : 'Gagal'">
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!activeCustomer.payments || activeCustomer.payments.length === 0">
                                    <p class="text-xs text-bq-text-muted text-center py-8">Belum ada riwayat pembayaran.</p>
                                </template>
                            </div>

                            {{-- TAB 4: NOTES --}}
                            <div x-show="activeTab === 'notes'" style="display:none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Internal Preferences & Remarks</h4>
                                <p class="text-[11px] text-bq-text-muted">Catatan ini hanya terlihat oleh Anda (owner) — tidak ditampilkan ke customer.</p>
                                <form id="form-customer-note" class="space-y-3" @submit.prevent="saveNote()">
                                    @csrf
                                    <input type="hidden" name="customer_identifier" :value="activeCustomer.identifier">
                                    <textarea name="notes"
                                              x-model="activeCustomer.notes"
                                              rows="5"
                                              placeholder="Catatan internal tentang preferensi customer ini (misal: alergi, request khusus, preferensi instruktur)..."
                                              class="w-full rounded-xl border border-bq-border p-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20 resize-none"></textarea>
                                    <button type="submit"
                                            class="rounded-xl bg-bq-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
                                        Simpan Catatan
                                    </button>
                                </form>
                            </div>

                        </div>
                    </template>
                </div>

                {{-- Drawer Footer --}}
                <div class="p-6 border-t border-bq-border bg-slate-50 flex items-center justify-between shrink-0">
                    <button type="button" @click="detailOpen = false"
                            class="px-4 py-2 rounded-xl border border-bq-border bg-white text-xs font-semibold text-bq-text hover:bg-slate-100 transition">
                        Tutup
                    </button>
                    <template x-if="activeCustomer && activeCustomer.phone && activeCustomer.phone !== '-'">
                        <a :href="'https://wa.me/' + activeCustomer.phone.replace(/[^0-9]/g, '')"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition shadow-sm">
                            Hubungi via WhatsApp &rarr;
                        </a>
                    </template>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
