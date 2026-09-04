@extends('layouts.owner-layout')

@section('title', 'Customers CRM')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '',
    detailOpen: false,
    activeCustomer: null,
    activeTab: 'overview',
    customers: @js($customers),
    openCustomer(c) {
        this.activeCustomer = c;
        this.activeTab = 'overview';
        this.detailOpen = true;
    },
    filteredCustomers() {
        if (!this.search.trim()) return this.customers;
        const q = this.search.toLowerCase();
        return this.customers.filter(c => 
            c.name.toLowerCase().includes(q) || 
            c.email.toLowerCase().includes(q) || 
            c.phone.toLowerCase().includes(q)
        );
    }
}">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Customers CRM',
        'subjudul' => 'Direktori data pelanggan, riwayat pemesanan, nilai transaksi, dan preferensi customer.',
    ])

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

    {{-- ── Search & Filter ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="search" placeholder="Cari nama, email, nomor HP..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </div>
        <p class="text-xs text-bq-text-muted">
            Menampilkan <span class="font-bold text-bq-text" x-text="filteredCustomers().length"></span> customer
        </p>
    </div>

    {{-- ── Customer Table ── --}}
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
                        <th class="px-5 py-3.5">Upcoming Booking</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    <template x-for="(c, idx) in filteredCustomers()" :key="idx">
                        <tr class="hover:bg-bq-background/40 transition">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 text-xs">
                                        <span x-text="c.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-bq-text text-sm" x-text="c.name"></p>
                                        <template x-if="c.total_bookings >= 3">
                                            <span class="inline-flex rounded bg-amber-50 text-amber-700 px-1.5 py-0.5 text-[10px] font-bold">VIP Regular</span>
                                        </template>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <template x-if="c.phone && c.phone !== '-'">
                                    <a :href="'https://wa.me/' + c.phone.replace(/[^0-9]/g, '')" target="_blank" class="inline-flex items-center gap-1 font-mono text-emerald-700 hover:underline">
                                        <span x-text="c.phone"></span>
                                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1 rounded">WA</span>
                                    </a>
                                </template>
                                <template x-if="!c.phone || c.phone === '-'">
                                    <span class="text-bq-text-muted">—</span>
                                </template>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted" x-text="c.email"></td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <span class="rounded-full bg-slate-100 text-slate-800 font-bold px-2.5 py-0.5 text-xs" x-text="c.total_bookings"></span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-semibold text-emerald-700" x-text="c.formatted_spent"></td>
                            <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted" x-text="c.last_booking"></td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <template x-if="c.upcoming_booking !== '-'">
                                    <span class="inline-flex rounded-full bg-indigo-50 text-indigo-700 px-2 py-0.5 text-[11px] font-medium" x-text="c.upcoming_booking"></span>
                                </template>
                                <template x-if="c.upcoming_booking === '-'">
                                    <span class="text-bq-text-muted text-[11px]">—</span>
                                </template>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <button type="button" @click="openCustomer(c)" class="inline-flex items-center gap-1 rounded-lg border border-bq-border bg-bq-surface px-2.5 py-1.5 text-xs font-semibold text-bq-text hover:bg-bq-background transition">
                                    <svg class="h-3.5 w-3.5 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Detail CRM
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="filteredCustomers().length === 0" class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center" style="display: none;">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
        </div>
        <h3 class="text-sm font-bold text-bq-text">Belum ada data customer</h3>
        <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Saat customer melakukan booking pada halaman publik bisnis Anda, profil dan riwayat mereka otomatis terakumulasi di sini.</p>
    </div>

    {{-- ── Slide-Over Detail Drawer (CRM) ── --}}
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
                 class="w-screen max-w-lg bg-white shadow-2xl flex flex-col justify-between">
                
                {{-- Drawer Header --}}
                <div class="p-6 border-b border-bq-border bg-slate-50/70 flex items-center justify-between">
                    <template x-if="activeCustomer">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white font-bold text-base">
                                <span x-text="activeCustomer.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-bq-text" x-text="activeCustomer.name"></h3>
                                <p class="text-xs text-bq-text-muted" x-text="activeCustomer.email"></p>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="detailOpen = false" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-200 hover:text-bq-text transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Tabs Inside Drawer --}}
                <div class="border-b border-bq-border px-6 flex items-center gap-4 text-xs font-semibold">
                    <button type="button" @click="activeTab = 'overview'" class="py-3 border-b-2 transition" :class="activeTab === 'overview' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Overview
                    </button>
                    <button type="button" @click="activeTab = 'history'" class="py-3 border-b-2 transition" :class="activeTab === 'history' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Booking History
                    </button>
                    <button type="button" @click="activeTab = 'notes'" class="py-3 border-b-2 transition" :class="activeTab === 'notes' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Notes &amp; Preferences
                    </button>
                </div>

                {{-- Drawer Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <template x-if="activeCustomer">
                        <div>
                            {{-- TAB 1: OVERVIEW --}}
                            <div x-show="activeTab === 'overview'" class="space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Total Spent (LTV)</p>
                                        <p class="text-base font-bold text-emerald-700 mt-0.5" x-text="activeCustomer.formatted_spent"></p>
                                    </div>
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Total Appointments</p>
                                        <p class="text-base font-bold text-bq-primary mt-0.5" x-text="activeCustomer.total_bookings + ' Bookings'"></p>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-bq-border p-4 space-y-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Contact Info</h4>
                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-bq-text-muted">Phone / WhatsApp:</span>
                                            <a :href="'https://wa.me/' + (activeCustomer.phone ? activeCustomer.phone.replace(/[^0-9]/g, '') : '')" target="_blank" class="font-mono text-emerald-700 font-bold hover:underline" x-text="activeCustomer.phone"></a>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-bq-text-muted">Email:</span>
                                            <span class="font-medium text-bq-text" x-text="activeCustomer.email"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-bq-text-muted">Last Visited:</span>
                                            <span class="text-bq-text" x-text="activeCustomer.last_booking"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-bq-text-muted">Next Scheduled:</span>
                                            <span class="font-semibold text-indigo-600" x-text="activeCustomer.upcoming_booking"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 2: HISTORY --}}
                            <div x-show="activeTab === 'history'" style="display: none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Booking Records</h4>
                                <div class="divide-y divide-bq-border rounded-xl border border-bq-border overflow-hidden">
                                    <template x-for="b in activeCustomer.bookings" :key="b.id">
                                        <div class="p-3.5 hover:bg-slate-50 transition text-xs space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold text-bq-text" x-text="b.service"></span>
                                                <span class="font-mono font-bold text-bq-primary" x-text="b.price"></span>
                                            </div>
                                            <div class="flex items-center justify-between text-bq-text-muted text-[11px]">
                                                <span x-text="b.date + ' • ' + b.time"></span>
                                                <span class="uppercase font-semibold px-2 py-0.5 rounded text-[10px]"
                                                      :class="{
                                                          'bg-emerald-50 text-emerald-700': b.status === 'completed' || b.status === 'paid',
                                                          'bg-amber-50 text-amber-700': b.status === 'pending',
                                                          'bg-rose-50 text-rose-700': b.status === 'cancelled'
                                                      }"
                                                      x-text="b.status === 'paid' ? 'Confirmed' : b.status"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- TAB 3: NOTES --}}
                            <div x-show="activeTab === 'notes'" style="display: none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Internal Preferences &amp; Remarks</h4>
                                <textarea rows="4" placeholder="Catatan internal tentang preferensi customer ini (misal: alergi tertentu, request instruktur khusus)..." class="w-full rounded-xl border border-bq-border p-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                                <button type="button" @click="alert('Catatan customer berhasil disimpan.')" class="rounded-xl bg-bq-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
                                    Simpan Catatan
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Drawer Footer --}}
                <div class="p-6 border-t border-bq-border bg-slate-50 flex items-center justify-between">
                    <button type="button" @click="detailOpen = false" class="px-4 py-2 rounded-xl border border-bq-border bg-white text-xs font-semibold text-bq-text hover:bg-slate-100 transition">
                        Tutup
                    </button>
                    <template x-if="activeCustomer && activeCustomer.phone && activeCustomer.phone !== '-'">
                        <a :href="'https://wa.me/' + activeCustomer.phone.replace(/[^0-9]/g, '')" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition shadow-sm">
                            Hubungi via WhatsApp &rarr;
                        </a>
                    </template>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
