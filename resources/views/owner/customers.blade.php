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
    @include('owner.partials.customers.summary')

    {{-- ── Search & Filters ── --}}
    @include('owner.partials.customers.filters')

    {{-- ── Customer Table & Empty State ── --}}
    @include('owner.partials.customers.table')

    {{-- ── Slide-Over Detail Drawer ── --}}
    @include('owner.partials.customers.detail-modal')

</div>
@endsection
