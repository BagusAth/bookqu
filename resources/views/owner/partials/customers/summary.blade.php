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
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EEF2FF] text-[#4F46E5]">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
    </div>
</div>
