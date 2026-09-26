{{-- ── Search & Filter Controls ── --}}
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" action="/owner/bookings" class="relative w-full sm:max-w-xs">
        <input type="hidden" name="status" value="{{ $filterstatus }}">
        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" name="katakunci" value="{{ $katakunci }}" placeholder="Search code, name, email, phone..."
            class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs sm:text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
            id="input-search-bookings">
    </form>
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1.5 pt-0.5 sm:pb-0 sm:flex-wrap no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
        @foreach (['semua' => 'All', 'today' => 'Today', 'pending' => 'Pending', 'paid' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $kunci => $label)
            <a href="/owner/bookings?status={{ $kunci }}&katakunci={{ $katakunci }}"
               class="whitespace-nowrap rounded-xl px-3 py-1.5 text-xs font-semibold transition-all shrink-0
                {{ $filterstatus === $kunci
                    ? 'bg-bq-primary text-white shadow-xs ring-2 ring-bq-primary/30'
                    : 'border border-bq-border bg-bq-surface text-bq-text-muted hover:border-bq-border-strong hover:text-bq-text'
                }}"
                id="filter-{{ $kunci }}"
            >{{ $label }}</a>
        @endforeach
    </div>
</div>
