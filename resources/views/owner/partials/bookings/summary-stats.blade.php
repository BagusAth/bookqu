{{-- ── Top Summary Stats ── --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    @php
        $statbooking = [
            ['label' => 'All',       'nilai' => $totalbooking,      'warna' => 'bg-slate-100 text-slate-700',   'filter' => 'semua'],
            ['label' => 'Today',     'nilai' => $bookinghariini,    'warna' => 'bg-[#EEF2FF] text-[#4F46E5]',     'filter' => 'today'],
            ['label' => 'Pending',   'nilai' => $bookingpending,    'warna' => 'bg-amber-100 text-amber-800',   'filter' => 'pending'],
            ['label' => 'Confirmed', 'nilai' => $bookingkonfirmasi, 'warna' => 'bg-indigo-100 text-indigo-700', 'filter' => 'paid'],
            ['label' => 'Completed', 'nilai' => $bookingselesai,    'warna' => 'bg-emerald-100 text-emerald-800','filter' => 'completed'],
            ['label' => 'Cancelled', 'nilai' => $bookingbatal,      'warna' => 'bg-rose-100 text-rose-700',    'filter' => 'cancelled'],
        ];
    @endphp
    @foreach ($statbooking as $stat)
        <a href="{{ '/owner/bookings?status=' . $stat['filter'] }}"
           class="rounded-xl border border-bq-border bg-bq-surface p-4 text-center transition-all hover:border-bq-border-strong hover:shadow-sm {{ $filterstatus === ($stat['filter'] ?? '') ? 'ring-2 ring-bq-primary ring-offset-1' : '' }}">
            <p class="text-2xl font-bold text-bq-text">{{ number_format($stat['nilai']) }}</p>
            <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stat['warna'] }}">{{ $stat['label'] }}</span>
        </a>
    @endforeach
</div>
