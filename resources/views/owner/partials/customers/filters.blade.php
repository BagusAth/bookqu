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
