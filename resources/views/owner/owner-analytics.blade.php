@extends('layouts.owner-layout')

@section('title', 'Analytics')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    @include('components.owner.page-header', [
        'judul' => 'Analytics',
        'subjudul' => 'Insights and performance metrics for your business.',
    ])

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5 transition-all hover:shadow-md">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Monthly Revenue</p>
            <p class="mt-1 text-2xl font-bold text-bq-text">Rp {{ number_format($revenuebulanini, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs {{ $persenperubahanrevenue >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">{{ $persenperubahanrevenue >= 0 ? '+' : '' }}{{ $persenperubahanrevenue }}% vs last month</p>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5 transition-all hover:shadow-md">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Monthly Bookings</p>
            <p class="mt-1 text-2xl font-bold text-bq-text">{{ number_format($bookingbulanini) }}</p>
            <p class="mt-1 text-xs {{ $persenbooking >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">{{ $persenbooking >= 0 ? '+' : '' }}{{ $persenbooking }}% vs last month</p>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5 transition-all hover:shadow-md">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Daily Avg. Bookings</p>
            <p class="mt-1 text-2xl font-bold text-bq-text">{{ $ratarataharian }}</p>
            <p class="mt-1 text-xs text-bq-text-muted">Last 30 days</p>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5 transition-all hover:shadow-md">
            <p class="text-xs font-medium uppercase tracking-wide text-bq-text-muted">Conversion Rate</p>
            <p class="mt-1 text-2xl font-bold text-bq-text">{{ $tingkatkonversi }}%</p>
            <div class="mt-2 h-1.5 w-full rounded-full bg-bq-background"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $tingkatkonversi }}%"></div></div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <h2 class="text-base font-semibold text-bq-text">Revenue Trend</h2>
            <p class="text-sm text-bq-text-muted">12-month overview</p>
            <div class="mt-4"><canvas id="chart-revenue-trend" height="260"></canvas></div>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <h2 class="text-base font-semibold text-bq-text">Booking Volume</h2>
            <p class="text-sm text-bq-text-muted">12-month overview</p>
            <div class="mt-4"><canvas id="chart-booking-trend" height="260"></canvas></div>
        </div>
    </div>

    {{-- Bottom --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <h2 class="text-base font-semibold text-bq-text">Top Programs</h2>
            <div class="mt-4 space-y-3">
                @foreach ($toplayanan as $i => $layanan)
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-bq-primary/10 text-xs font-bold text-bq-primary">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-bq-text">{{ $layanan->namalayanan }}</p>
                            <div class="mt-1 h-1.5 w-full rounded-full bg-bq-background"><div class="h-full rounded-full bg-bq-primary/60" style="width: {{ $toplayanan->first()->bookings_count > 0 ? round(($layanan->bookings_count / $toplayanan->first()->bookings_count) * 100) : 0 }}%"></div></div>
                        </div>
                        <span class="text-sm font-bold text-bq-text">{{ $layanan->bookings_count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <h2 class="text-base font-semibold text-bq-text">Status Distribution</h2>
            <div class="mt-4"><canvas id="chart-status" height="260"></canvas></div>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <h2 class="text-base font-semibold text-bq-text">Revenue by Program</h2>
            <div class="mt-4 space-y-3">
                @foreach ($revenueperLayanan as $item)
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-bq-text">{{ $item['namalayanan'] }}</p><p class="text-xs text-bq-text-muted">{{ $item['jumlahbooking'] }} paid</p></div>
                        <span class="text-sm font-bold text-bq-primary">Rp {{ number_format($item['revenue'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Annual --}}
    <div class="rounded-xl border border-bq-border bg-gradient-to-r from-indigo-50 to-violet-50 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-bq-text">Annual Revenue</h2>
                <p class="mt-1 text-3xl font-bold text-bq-primary">Rp {{ number_format($totalrevenuesetahun, 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-bq-text-muted">Total revenue in the last 12 months</p>
            </div>
            <button class="inline-flex items-center gap-2 rounded-lg border border-bq-primary/30 bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-primary hover:bg-bq-primary hover:text-white transition-all" id="btn-export">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M7 20h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v13a2 2 0 002 2z"/></svg>
                Export Report
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = @json(array_map(fn($l) => explode(' ', $l)[0], $labelbulanpanjang));

    // Revenue
    const ctx1 = document.getElementById('chart-revenue-trend').getContext('2d');
    const g1 = ctx1.createLinearGradient(0,0,0,260);
    g1.addColorStop(0,'rgba(99,102,241,0.15)'); g1.addColorStop(1,'rgba(99,102,241,0.01)');
    new Chart(ctx1, { type:'line', data:{ labels, datasets:[{ data:@json($revenueperbulan), borderColor:'rgb(99,102,241)', backgroundColor:g1, borderWidth:2, tension:0.4, fill:true, pointRadius:3, pointBackgroundColor:'rgb(99,102,241)', pointBorderColor:'#fff', pointBorderWidth:2 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'#1e1b4b', bodyColor:'#fff', cornerRadius:8, displayColors:false, callbacks:{ label:c=>'Rp '+c.parsed.y.toLocaleString('id-ID') } } }, scales:{ x:{grid:{display:false},ticks:{color:'#9ca3af',font:{size:11}},border:{display:false}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{color:'#9ca3af',font:{size:10},callback:v=>v>=1e6?(v/1e6).toFixed(1)+'M':v>=1e3?(v/1e3).toFixed(0)+'K':v},border:{display:false}} } } });

    // Bookings
    new Chart(document.getElementById('chart-booking-trend'), { type:'bar', data:{ labels, datasets:[{ data:@json($bookingperbulan), backgroundColor:'rgba(99,102,241,0.7)', borderRadius:6, borderSkipped:false }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:{backgroundColor:'#1e1b4b',bodyColor:'#fff',cornerRadius:8,displayColors:false}}, scales:{ x:{grid:{display:false},ticks:{color:'#9ca3af',font:{size:11}},border:{display:false}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{color:'#9ca3af',font:{size:10}},border:{display:false}} } } });

    // Status
    new Chart(document.getElementById('chart-status'), { type:'doughnut', data:{ labels:['Completed','Confirmed','Pending','Cancelled'], datasets:[{ data:[{{$distribusistatus['completed']}},{{$distribusistatus['paid']}},{{$distribusistatus['pending']}},{{$distribusistatus['cancelled']}}], backgroundColor:['#10b981','#6366f1','#f59e0b','#f43f5e'], borderWidth:0, borderRadius:4 }] }, options:{ responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{legend:{position:'bottom',labels:{padding:16,usePointStyle:true,pointStyleWidth:8,font:{size:11}}}} } });
});
</script>
@endsection
