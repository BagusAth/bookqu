@extends('layouts.owner-layout')

@section('title', 'Vouchers & Discounts')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '',
    addModalOpen: false,
    editModalOpen: false,
    activeVoucher: null,
    notification: '',
    vouchers: [
        { id: 1, code: 'BOOKQUWELCOME', discount: '20% OFF', discount_val: '20%', usage: '28 / 50 Digunakan', valid_period: '01 Jan 2026 - 31 Dec 2026', applicable: 'Semua Layanan', is_active: true },
        { id: 2, code: 'WEEKDAYPROMO', discount: 'Rp 30.000', discount_val: 'Rp 30.000', usage: '14 / 100 Digunakan', valid_period: 'Mon - Thu Only', applicable: 'Treatment & Rental', is_active: true },
        { id: 3, code: 'SPECIALFLASH50', discount: '50% OFF', discount_val: '50%', usage: '50 / 50 (Penuh)', valid_period: 'Expired (Mei 2026)', applicable: 'VIP Package', is_active: false }
    ],
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
    },
    copyCode(code) {
        navigator.clipboard.writeText(code);
        this.showToast('Kode voucher ' + code + ' disalin ke clipboard!');
    },
    toggleStatus(v) {
        v.is_active = !v.is_active;
        this.showToast('Status voucher ' + v.code + ' diubah.');
    },
    openEdit(v) {
        this.activeVoucher = { ...v };
        this.editModalOpen = true;
    },
    saveEdit() {
        const idx = this.vouchers.findIndex(v => v.id === this.activeVoucher.id);
        if (idx !== -1) {
            this.vouchers[idx] = { ...this.activeVoucher };
        }
        this.editModalOpen = false;
        this.showToast('Perubahan voucher disimpan.');
    },
    deleteVoucher(id) {
        this.vouchers = this.vouchers.filter(v => v.id !== id);
        this.showToast('Voucher dihapus.');
    },
    filteredVouchers() {
        if (!this.search.trim()) return this.vouchers;
        const q = this.search.toLowerCase();
        return this.vouchers.filter(v => v.code.toLowerCase().includes(q) || v.applicable.toLowerCase().includes(q));
    }
}">

    {{-- Toast Notification --}}
    <div x-show="notification"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 rounded-xl bg-slate-900 text-white px-4 py-3 shadow-xl text-xs font-medium flex items-center gap-2"
         style="display: none;">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
        <span x-text="notification"></span>
    </div>

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Promo Vouchers &amp; Discounts</h1>
            <p class="text-sm text-bq-text-muted mt-1">Buat kode promo, kupon diskon persentase atau nominal untuk mendongkrak konversi booking.</p>
        </div>
        <button type="button" @click="addModalOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            + Buat Voucher Baru
        </button>
    </div>

    {{-- ── Search & Filter ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Cari kode kupon..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </div>
        <p class="text-xs text-bq-text-muted">
            Total <span class="font-bold text-bq-text" x-text="filteredVouchers().length"></span> voucher promo
        </p>
    </div>

    {{-- ── Voucher Cards Grid ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <template x-for="v in filteredVouchers()" :key="v.id">
            <div class="relative rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs transition hover:border-bq-border-strong hover:shadow-md flex flex-col justify-between overflow-hidden">
                <div class="absolute -right-6 -bottom-6 h-24 w-24 rounded-full bg-indigo-50/50 pointer-events-none"></div>

                <div>
                    {{-- Header Code & Discount --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-indigo-50 px-2.5 py-1 font-mono text-xs font-bold text-indigo-700 tracking-wider" x-text="v.code"></span>
                            <button type="button" @click="copyCode(v.code)" class="text-bq-text-muted hover:text-indigo-600 transition" title="Salin Kode">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                        <span class="font-bold text-sm text-emerald-600" x-text="v.discount"></span>
                    </div>

                    {{-- Details --}}
                    <div class="mt-4 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Layanan:</span>
                            <span class="font-medium text-bq-text" x-text="v.applicable"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Penggunaan:</span>
                            <span class="font-medium text-bq-text" x-text="v.usage"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Masa Berlaku:</span>
                            <span class="font-medium text-bq-text-subtle font-mono text-[11px]" x-text="v.valid_period"></span>
                        </div>
                    </div>
                </div>

                {{-- Footer Controls --}}
                <div class="mt-5 pt-3 border-t border-bq-border flex items-center justify-between">
                    <button type="button" @click="toggleStatus(v)"
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition"
                        :class="v.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-500'">
                        <span x-text="v.is_active ? 'Aktif' : 'Non-Aktif'"></span>
                    </button>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEdit(v)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button type="button" @click="deleteVoucher(v.id)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div x-show="filteredVouchers().length === 0" class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center" style="display: none;">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
        </div>
        <h3 class="text-sm font-bold text-bq-text">Belum ada promo voucher</h3>
        <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Buat kupon diskon untuk memikat customer pertama atau memberikan reward kepada pelanggan setia.</p>
        <button type="button" @click="addModalOpen = true" class="mt-4 rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
            + Buat Voucher Pertama
        </button>
    </div>

    {{-- Add Voucher Modal --}}
    <div x-show="addModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Buat Voucher Promo Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Tentukan kode kupon, nilai potongan harga, dan kuota pemakaian.</p>
            <form @submit.prevent="
                vouchers.push({
                    id: Date.now(),
                    code: $refs.vCode.value.toUpperCase(),
                    discount: $refs.vDisc.value,
                    usage: '0 / ' + ($refs.vQuota.value || 50) + ' Digunakan',
                    valid_period: $refs.vPeriod.value || '1 Bulan',
                    applicable: $refs.vApp.value || 'Semua Layanan',
                    is_active: true
                });
                addModalOpen = false;
                showToast('Voucher promo berhasil dibuat!')" class="mt-4 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-bq-text">Kode Promo (Huruf Kapital)</label>
                    <input type="text" x-ref="vCode" required placeholder="Contoh: HEMAT25" class="mt-1.5 w-full uppercase font-mono rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nilai Diskon</label>
                        <input type="text" x-ref="vDisc" required placeholder="Contoh: 20% atau Rp 20.000" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Maksimal Kuota</label>
                        <input type="number" x-ref="vQuota" placeholder="50" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Masa Berlaku</label>
                    <input type="text" x-ref="vPeriod" placeholder="Contoh: 01 Jun - 30 Jun 2026" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Berlaku Untuk</label>
                    <input type="text" x-ref="vApp" placeholder="Semua Layanan" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Voucher</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Voucher Modal --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Edit Voucher Promo</h3>
            <template x-if="activeVoucher">
                <form @submit.prevent="saveEdit()" class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Kode Promo</label>
                        <input type="text" x-model="activeVoucher.code" required class="mt-1.5 w-full uppercase font-mono rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nilai Diskon</label>
                        <input type="text" x-model="activeVoucher.discount" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Masa Berlaku</label>
                        <input type="text" x-model="activeVoucher.valid_period" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-bq-border">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-bq-text">
                            <input type="checkbox" x-model="activeVoucher.is_active" class="rounded text-bq-primary focus:ring-bq-primary">
                            Status Aktif
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="editModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                            <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
