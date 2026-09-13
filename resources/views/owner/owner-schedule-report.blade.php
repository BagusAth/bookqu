@extends('layouts.owner-layout')

@section('title', 'Schedule Report')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Header & Export ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text sm:text-3xl">Schedule Report &amp; Utilization</h1>
            <p class="mt-1 text-sm text-bq-text-muted">Analisis komprehensif efisiensi slot jadwal, tingkat okupansi staf, dan utilisasi resource bisnis.</p>
        </div>
        <div class="flex items-center gap-2">
            <a
                href="{{ route('owner.schedule-report.export', ['period' => $period ?? 'all']) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-bq-border bg-white px-4 py-2.5 text-xs font-bold text-bq-text hover:bg-slate-50 transition shadow-2xs"
                id="btn-export-schedule-csv"
            >
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- ── Period Filter Bar ── --}}
    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-bq-border bg-white p-3 shadow-2xs text-xs">
        <span class="font-bold text-slate-700 px-2">Periode:</span>
        <a href="{{ route('owner.schedule-report', ['period' => 'all']) }}"
            class="rounded-xl px-3 py-1.5 font-semibold transition {{ ($period ?? 'all') === 'all' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Semua Waktu
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'today']) }}"
            class="rounded-xl px-3 py-1.5 font-semibold transition {{ ($period ?? 'all') === 'today' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Hari Ini
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'this_week']) }}"
            class="rounded-xl px-3 py-1.5 font-semibold transition {{ ($period ?? 'all') === 'this_week' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Minggu Ini
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'this_month']) }}"
            class="rounded-xl px-3 py-1.5 font-semibold transition {{ ($period ?? 'all') === 'this_month' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Bulan Ini
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'last_30_days']) }}"
            class="rounded-xl px-3 py-1.5 font-semibold transition {{ ($period ?? 'all') === 'last_30_days' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            30 Hari Terakhir
        </a>
    </div>

    {{-- ── Metric KPI Cards ── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Total Slots</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">{{ number_format($totalSlots) }} <span class="text-xs font-normal text-bq-text-muted">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-bq-text-muted">
                <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                <span>Kapasitas jadwal aktif</span>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Booked Slots</span>
            <p class="mt-2 text-2xl font-bold text-bq-primary">{{ number_format($bookedSlots) }} <span class="text-xs font-normal text-bq-text-muted">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-emerald-600 font-medium">
                <span>↑ {{ $utilizationRate }}% Terisi</span>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Empty / Available</span>
            <p class="mt-2 text-2xl font-bold text-slate-700">{{ number_format($availableSlots) }} <span class="text-xs font-normal text-bq-text-muted">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-amber-600 font-medium">
                <span>{{ 100 - $utilizationRate }}% Kesempatan promosi</span>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Utilization Rate</span>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ $utilizationRate }}%</p>
            <p class="mt-1 text-xs text-bq-text-muted font-medium">Tingkat efisiensi slot operasional</p>
        </div>
    </div>

    {{-- ── Charts Grid ── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Hourly Peak vs Low Demand --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-bq-text">Jam Sibuk vs Jam Sepi (Hourly Demand)</h3>
                    <p class="text-xs text-bq-text-muted">Frekuensi booking berdasarkan jam operasional harian</p>
                </div>
                <div class="flex items-center gap-2 text-[11px]">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-indigo-600"></span> Sibuk</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Low Demand</span>
                </div>
            </div>
            <div class="h-64 mt-2">
                <canvas id="chart-hourly-demand"></canvas>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 border border-bq-border text-xs flex items-center justify-between">
                <div>
                    <span class="font-bold text-bq-text">🔥 Peak Hours:</span>
                    <span class="text-bq-text-muted ml-1">{{ $peakHour }} (Jam paling diminati)</span>
                </div>
                <div>
                    <span class="font-bold text-amber-700">❄️ Low Demand:</span>
                    <span class="text-bq-text-muted ml-1">{{ $lowHour }} (Peluang promo)</span>
                </div>
            </div>
        </div>

        {{-- Peak Days of Week --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs space-y-3">
            <div>
                <h3 class="text-base font-bold text-bq-text">Okupansi Berdasarkan Hari (Daily Demand)</h3>
                <p class="text-xs text-bq-text-muted">Jumlah booking dari Senin sampai Minggu</p>
            </div>
            <div class="h-64 mt-2">
                <canvas id="chart-daily-demand"></canvas>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 border border-bq-border text-xs flex items-center justify-between">
                <div>
                    <span class="font-bold text-bq-text">🏆 Hari Paling Ramai:</span>
                    <span class="text-bq-text-muted ml-1">{{ $peakDay }}</span>
                </div>
                <div>
                    <span class="font-bold text-slate-700">📅 Hari Paling Longgar:</span>
                    <span class="text-bq-text-muted ml-1">{{ $lowDay }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Staff & Resource Workload Allocation ── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Staff Workload --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-bq-text">Beban Kerja Staf (Staff Workload)</h3>
                    <p class="text-xs text-bq-text-muted">Total sesi reservasi yang ditangani berdasarkan penugasan layanan</p>
                </div>
            </div>

            <div class="space-y-3 text-xs divide-y divide-bq-border/60">
                @forelse($staffMembers as $s)
                    <div class="pt-3 first:pt-0">
                        <div class="flex items-start justify-between font-semibold mb-1">
                            <div>
                                <span class="text-bq-text font-bold text-xs">{{ $s['name'] }}</span>
                                <span class="ml-1.5 text-[11px] text-bq-text-muted font-normal">({{ $s['role'] }})</span>
                            </div>
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700">
                                {{ $s['count'] }} Booking Berjalan
                            </span>
                        </div>
                        <p class="text-[11px] text-bq-text-muted truncate">
                            <span class="font-medium text-slate-700">Layanan:</span> {{ $s['services_names'] ?: 'Belum ditugaskan ke layanan' }}
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-bq-text-subtle py-4 text-center">Belum ada staf terdaftar di sistem.</p>
                @endforelse
            </div>
        </div>

        {{-- Resource Allocation --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-bq-text">Alokasi Resource &amp; Ruangan</h3>
                    <p class="text-xs text-bq-text-muted">Aktivitas pemakaian ruangan, aset fisik, dan kapasitas sesi</p>
                </div>
            </div>

            <div class="space-y-3 text-xs divide-y divide-bq-border/60">
                @forelse($resourceList as $r)
                    <div class="pt-3 first:pt-0">
                        <div class="flex items-start justify-between font-semibold mb-1">
                            <div>
                                <span class="text-bq-text font-bold text-xs">{{ $r['name'] }}</span>
                                <span class="ml-1.5 text-[11px] text-bq-text-muted font-normal">({{ $r['type'] }} • Kapasitas {{ $r['capacity'] ?: 1 }})</span>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                {{ $r['count'] }} Booking Berjalan
                            </span>
                        </div>
                        <p class="text-[11px] text-bq-text-muted truncate">
                            <span class="font-medium text-slate-700">Layanan:</span> {{ $r['services_names'] ?: 'Belum ditugaskan ke layanan' }}
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-bq-text-subtle py-4 text-center">Belum ada resource atau ruangan fisik terdaftar.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    // 1. Hourly Demand Chart
    const hourlyLabels = @json(array_keys($hourlyCounts));
    const hourlyData = @json(array_values($hourlyCounts));

    const ctxHourly = document.getElementById('chart-hourly-demand');
    if (ctxHourly) {
        new Chart(ctxHourly.getContext('2d'), {
            type: 'bar',
            data: {
                labels: hourlyLabels,
                datasets: [{
                    label: 'Booked Sessions',
                    data: hourlyData,
                    backgroundColor: hourlyData.map(v => v > 0 ? '#4f46e5' : '#e2e8f0'),
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e1b4b',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        callbacks: { label: c => c.parsed.y + ' booking appointments' }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                    y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, color: '#9ca3af', precision: 0 } }
                }
            }
        });
    }

    // 2. Daily Utilization Demand Chart
    const dailyLabels = @json(array_keys($dailyCounts));
    const dailyData = @json(array_values($dailyCounts));

    const ctxDaily = document.getElementById('chart-daily-demand');
    if (ctxDaily) {
        new Chart(ctxDaily.getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'Jumlah Booking',
                    data: dailyData,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#4f46e5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e1b4b',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        callbacks: { label: c => c.parsed.y + ' Booking' }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                    y: { min: 0, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, color: '#9ca3af', precision: 0 } }
                }
            }
        });
    }
});
</script>
@endsection
