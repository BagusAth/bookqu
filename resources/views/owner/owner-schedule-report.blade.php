@extends('layouts.owner-layout')

@section('title', 'Schedule Report')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Header & Export ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7] shadow-2xs">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </span>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#231a3d] sm:text-3xl">Schedule Report &amp; Utilization</h1>
            </div>
            <p class="mt-1 text-sm text-[#6e6584]">Analisis komprehensif efisiensi slot jadwal, tingkat okupansi staf, dan utilisasi resource bisnis.</p>
        </div>
        <div class="flex items-center gap-2">
            <a
                href="{{ route('owner.schedule-report.export', ['period' => $period ?? 'all']) }}"
                class="craft-btn inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-white px-4 py-2.5 text-xs font-bold text-[#231a3d] hover:bg-[#f7f7fa] hover:border-[#b499ff] shadow-2xs"
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
    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-[#e7e2f7] bg-white p-3.5 shadow-[0_4px_20px_rgba(35,26,61,0.03)] text-xs">
        <span class="font-extrabold text-[#231a3d] px-2">Periode:</span>
        <a href="{{ route('owner.schedule-report', ['period' => 'all']) }}"
            class="craft-btn rounded-xl px-3.5 py-1.5 font-bold transition-all {{ ($period ?? 'all') === 'all' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-[#f7f7fa] text-[#6e6584] hover:text-[#231a3d] hover:bg-[#e7e2f7]' }}">
            Semua Waktu
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'today']) }}"
            class="craft-btn rounded-xl px-3.5 py-1.5 font-bold transition-all {{ ($period ?? 'all') === 'today' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-[#f7f7fa] text-[#6e6584] hover:text-[#231a3d] hover:bg-[#e7e2f7]' }}">
            Hari Ini
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'this_week']) }}"
            class="craft-btn rounded-xl px-3.5 py-1.5 font-bold transition-all {{ ($period ?? 'all') === 'this_week' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-[#f7f7fa] text-[#6e6584] hover:text-[#231a3d] hover:bg-[#e7e2f7]' }}">
            Minggu Ini
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'this_month']) }}"
            class="craft-btn rounded-xl px-3.5 py-1.5 font-bold transition-all {{ ($period ?? 'all') === 'this_month' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-[#f7f7fa] text-[#6e6584] hover:text-[#231a3d] hover:bg-[#e7e2f7]' }}">
            Bulan Ini
        </a>
        <a href="{{ route('owner.schedule-report', ['period' => 'last_30_days']) }}"
            class="craft-btn rounded-xl px-3.5 py-1.5 font-bold transition-all {{ ($period ?? 'all') === 'last_30_days' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-[#f7f7fa] text-[#6e6584] hover:text-[#231a3d] hover:bg-[#e7e2f7]' }}">
            30 Hari Terakhir
        </a>
    </div>

    {{-- ── Metric KPI Cards ── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="craft-card rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)]">
            <span class="text-xs font-bold text-[#6e6584] uppercase tracking-wider">Total Slots</span>
            <p class="mt-2 text-2xl sm:text-3xl font-black text-[#231a3d]">{{ number_format($totalSlots) }} <span class="text-xs font-semibold text-[#6e6584]">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-[#6e6584] font-medium">
                <span class="h-2 w-2 rounded-full bg-[#6e6584]"></span>
                <span>Kapasitas jadwal aktif</span>
            </div>
        </div>

        <div class="craft-card rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)]">
            <span class="text-xs font-bold text-[#6e6584] uppercase tracking-wider">Booked Slots</span>
            <p class="mt-2 text-2xl sm:text-3xl font-black text-[#382186]">{{ number_format($bookedSlots) }} <span class="text-xs font-semibold text-[#6e6584]">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-[#382186] font-bold">
                <span class="rounded-md bg-[#f3effe] px-1.5 py-0.5 text-[10px] text-[#382186]">↑ {{ $utilizationRate }}% Terisi</span>
            </div>
        </div>

        <div class="craft-card rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)]">
            <span class="text-xs font-bold text-[#6e6584] uppercase tracking-wider">Empty / Available</span>
            <p class="mt-2 text-2xl sm:text-3xl font-black text-[#231a3d]">{{ number_format($availableSlots) }} <span class="text-xs font-semibold text-[#6e6584]">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-[#875000] font-bold">
                <span class="rounded-md bg-[#fff8eb] px-1.5 py-0.5 text-[10px] text-[#875000] border border-[#ffb84d]/50">{{ 100 - $utilizationRate }}% Kesempatan promosi</span>
            </div>
        </div>

        <div class="craft-card rounded-2xl border border-[#b499ff] bg-gradient-to-br from-white to-[#f3effe]/30 p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)]">
            <span class="text-xs font-bold text-[#382186] uppercase tracking-wider">Utilization Rate</span>
            <p class="mt-2 text-3xl sm:text-4xl font-black text-[#382186]">{{ $utilizationRate }}%</p>
            <div class="mt-2">
                @if($utilizationRate >= 80)
                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-extrabold text-emerald-800 border border-emerald-200">
                        ● Sangat Optimal
                    </span>
                @elseif($utilizationRate >= 40)
                    <span class="inline-flex items-center gap-1 rounded-md bg-[#f3effe] px-2 py-0.5 text-[10px] font-extrabold text-[#382186] border border-[#b499ff]/50">
                        ● Stabil Sehat
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-md bg-[#fff8eb] px-2 py-0.5 text-[10px] font-extrabold text-[#875000] border border-[#ffb84d]/60">
                        ● Butuh Promo
                    </span>
                @endif
            </div>
            <p class="mt-1 text-xs text-[#6e6584] font-semibold">Tingkat efisiensi slot operasional</p>
        </div>
    </div>

    {{-- ── Smart Business Insights Card ── --}}
    <div class="rounded-2xl border border-[#b499ff]/40 bg-gradient-to-r from-[#f3effe]/80 via-white to-[#fff8eb]/50 p-5 shadow-[0_4px_24px_rgba(56,33,134,0.04)]">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#382186] text-white shadow-2xs">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-extrabold text-[#231a3d]">Smart Business Insights &amp; Rekomendasi</h3>
                        <span class="rounded-full bg-[#ffb84d]/20 px-2 py-0.5 text-[10px] font-extrabold text-[#875000]">Live AI Analitik</span>
                    </div>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-[#231a3d]">
                        <div class="rounded-xl bg-white/80 p-3 border border-[#e7e2f7] shadow-2xs">
                            <span class="font-black text-[#382186] block">⚡ Jam Sibuk ({{ $peakHour }}):</span>
                            <span class="text-[#6e6584] mt-0.5 block">Hari {{ $peakDay }} pada jam ini memiliki okupansi tertinggi. Pastikan kesiapan penuh staf &amp; ruangan.</span>
                        </div>
                        <div class="rounded-xl bg-white/80 p-3 border border-[#e7e2f7] shadow-2xs">
                            <span class="font-black text-[#875000] block">💡 Peluang Promo ({{ $lowHour }}):</span>
                            <span class="text-[#6e6584] mt-0.5 block">Trafik paling renggang terdeteksi pada jam ini. Pertimbangkan promo voucher Happy Hour untuk mengisi slot kosong.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="shrink-0 flex sm:flex-col gap-2">
                <a
                    href="{{ route('owner.vouchers') }}"
                    class="craft-btn inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#382186] hover:bg-[#2d1a6d] px-4 py-2 text-xs font-bold text-white shadow-xs"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Buat Diskon Happy Hour</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ── Charts Grid ── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Hourly Peak vs Low Demand --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 sm:p-6 shadow-[0_4px_24px_rgba(35,26,61,0.03)] space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-[#231a3d]">Jam Sibuk vs Jam Sepi (Hourly Demand)</h3>
                    <p class="text-xs text-bq-text-muted mt-0.5">Frekuensi booking berdasarkan jam operasional harian</p>
                </div>
                <div class="flex items-center gap-2 text-[11px]">
                    <span class="flex items-center gap-1 font-bold text-[#382186]"><span class="h-2.5 w-2.5 rounded-full bg-[#382186]"></span> Sibuk</span>
                    <span class="flex items-center gap-1 font-bold text-[#ffb84d]"><span class="h-2.5 w-2.5 rounded-full bg-[#ffb84d]"></span> Low Demand</span>
                </div>
            </div>
            <div class="h-64 mt-2">
                <canvas id="chart-hourly-demand"></canvas>
            </div>
            <div class="rounded-2xl bg-[#f7f7fa] p-3.5 border border-[#e7e2f7] text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <span class="font-extrabold text-[#382186]">🔥 Peak Hours:</span>
                    <span class="text-[#231a3d] font-bold ml-1">{{ $peakHour }}</span>
                    <span class="text-[#6e6584] text-[11px]">(Jam paling diminati)</span>
                </div>
                <div>
                    <span class="font-extrabold text-[#875000]">❄️ Low Demand:</span>
                    <span class="text-[#231a3d] font-bold ml-1">{{ $lowHour }}</span>
                    <span class="text-[#6e6584] text-[11px]">(Peluang promo)</span>
                </div>
            </div>
        </div>

        {{-- Peak Days of Week --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 sm:p-6 shadow-[0_4px_24px_rgba(35,26,61,0.03)] space-y-4">
            <div>
                <h3 class="text-base font-extrabold text-[#231a3d]">Okupansi Berdasarkan Hari (Daily Demand)</h3>
                <p class="text-xs text-[#6e6584] mt-0.5">Jumlah booking dari Senin sampai Minggu</p>
            </div>
            <div class="h-64 mt-2">
                <canvas id="chart-daily-demand"></canvas>
            </div>
            <div class="rounded-2xl bg-[#f7f7fa] p-3.5 border border-[#e7e2f7] text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <span class="font-extrabold text-[#382186]">🏆 Hari Paling Ramai:</span>
                    <span class="text-[#231a3d] font-bold ml-1">{{ $peakDay }}</span>
                </div>
                <div>
                    <span class="font-extrabold text-[#6e6584]">📅 Hari Paling Longgar:</span>
                    <span class="text-[#231a3d] font-bold ml-1">{{ $lowDay }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Staff & Resource Workload Allocation ── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Staff Workload --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 sm:p-6 shadow-[0_4px_24px_rgba(35,26,61,0.03)] space-y-4">
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-[#231a3d]">Beban Kerja Staf (Staff Workload)</h3>
                    <p class="text-xs text-[#6e6584] mt-0.5">Total sesi reservasi yang ditangani berdasarkan penugasan layanan</p>
                </div>
            </div>

            <div class="space-y-3 text-xs divide-y divide-[#e7e2f7]">
                @forelse($staffMembers as $s)
                    @php
                        $staffPercent = $bookedSlots > 0 ? min(100, round(($s['count'] / $bookedSlots) * 100)) : 0;
                    @endphp
                    <div class="pt-3 first:pt-0">
                        <div class="flex items-start justify-between font-semibold mb-1">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#f3effe] text-[#382186] font-bold text-xs">
                                    {{ strtoupper(substr($s['name'], 0, 1)) }}
                                </span>
                                <div>
                                    <span class="text-[#231a3d] font-extrabold text-xs">{{ $s['name'] }}</span>
                                    <span class="ml-1 text-[11px] text-[#6e6584] font-medium">({{ $s['role'] }})</span>
                                </div>
                            </div>
                            <span class="rounded-full bg-[#f3effe] px-2.5 py-0.5 text-[11px] font-extrabold text-[#382186] border border-[#b499ff]/50">
                                {{ $s['count'] }} Booking Berjalan
                            </span>
                        </div>
                        <div class="pl-9 pr-1 mt-1 space-y-1">
                            <div class="flex items-center justify-between text-[11px] text-[#6e6584]">
                                <span class="truncate"><span class="font-bold text-[#231a3d]">Layanan:</span> {{ $s['services_names'] ?: 'Belum ditugaskan ke layanan' }}</span>
                                <span class="font-bold text-[#382186] ml-2 shrink-0">{{ $staffPercent }}% kontribusi</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-[#f7f7fa] overflow-hidden">
                                <div class="h-full rounded-full bg-[#382186] transition-all duration-300" style="width: {{ $staffPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-[#6e6584] py-4 text-center">Belum ada staf terdaftar di sistem.</p>
                @endforelse
            </div>
        </div>

        {{-- Resource Allocation --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 sm:p-6 shadow-[0_4px_24px_rgba(35,26,61,0.03)] space-y-4">
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-[#231a3d]">Alokasi Resource &amp; Ruangan</h3>
                    <p class="text-xs text-[#6e6584] mt-0.5">Aktivitas pemakaian ruangan, aset fisik, dan kapasitas sesi</p>
                </div>
            </div>

            <div class="space-y-3 text-xs divide-y divide-[#e7e2f7]">
                @forelse($resourceList as $r)
                    @php
                        $resPercent = $bookedSlots > 0 ? min(100, round(($r['count'] / $bookedSlots) * 100)) : 0;
                    @endphp
                    <div class="pt-3 first:pt-0">
                        <div class="flex items-start justify-between font-semibold mb-1">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#f7f7fa] text-[#382186] border border-[#e7e2f7]">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </span>
                                <div>
                                    <span class="text-[#231a3d] font-extrabold text-xs">{{ $r['name'] }}</span>
                                    <span class="ml-1 text-[11px] text-[#6e6584] font-medium">({{ $r['type'] }} • Kapasitas {{ $r['capacity'] ?: 1 }})</span>
                                </div>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-extrabold text-emerald-700 border border-emerald-200">
                                {{ $r['count'] }} Booking Berjalan
                            </span>
                        </div>
                        <div class="pl-9 pr-1 mt-1 space-y-1">
                            <div class="flex items-center justify-between text-[11px] text-[#6e6584]">
                                <span class="truncate"><span class="font-bold text-[#231a3d]">Layanan:</span> {{ $r['services_names'] ?: 'Belum ditugaskan ke layanan' }}</span>
                                <span class="font-bold text-emerald-700 ml-2 shrink-0">{{ $resPercent }}% okupansi</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-[#f7f7fa] overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $resPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-[#6e6584] py-4 text-center">Belum ada resource atau ruangan fisik terdaftar.</p>
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
                    backgroundColor: hourlyData.map(v => v > 0 ? '#382186' : '#e7e2f7'),
                    hoverBackgroundColor: '#2d1a6d',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#231a3d',
                        bodyColor: '#fff',
                        titleColor: '#b499ff',
                        cornerRadius: 10,
                        padding: 10,
                        callbacks: { label: c => c.parsed.y + ' booking appointments' }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' }, color: '#6e6584' } },
                    y: { grid: { color: 'rgba(231, 226, 247, 0.6)' }, ticks: { font: { size: 10 }, color: '#6e6584', precision: 0 } }
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
                    borderColor: '#382186',
                    backgroundColor: 'rgba(180, 153, 255, 0.15)',
                    borderWidth: 3,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: '#382186',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#231a3d',
                        bodyColor: '#fff',
                        titleColor: '#b499ff',
                        cornerRadius: 10,
                        padding: 10,
                        callbacks: { label: c => c.parsed.y + ' Booking' }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' }, color: '#6e6584' } },
                    y: { min: 0, grid: { color: 'rgba(231, 226, 247, 0.6)' }, ticks: { font: { size: 10 }, color: '#6e6584', precision: 0 } }
                }
            }
        });
    }
});
</script>
@endsection
