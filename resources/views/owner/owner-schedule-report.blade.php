@extends('layouts.owner-layout')

@section('title', 'Schedule Report')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Schedule Report & Utilization',
        'subjudul' => 'Analisis komprehensif efisiensi slot jadwal, tingkat okupansi staf, dan utilisasi resource bisnis.',
    ])

    {{-- ── Metric KPI Cards ── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Total Slots</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">240 <span class="text-xs font-normal text-bq-text-muted">Slot/bln</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-bq-text-muted">
                <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                <span>Kapasitas jadwal aktif</span>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Booked Slots</span>
            <p class="mt-2 text-2xl font-bold text-bq-primary">184 <span class="text-xs font-normal text-bq-text-muted">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-emerald-600 font-medium">
                <span>↑ 76.7% Terisi</span>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Empty / Available</span>
            <p class="mt-2 text-2xl font-bold text-slate-700">56 <span class="text-xs font-normal text-bq-text-muted">Slot</span></p>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-amber-600 font-medium">
                <span>23.3% Kesempatan promosi</span>
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Utilization Rate</span>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">76.7%</p>
            <p class="mt-1 text-xs text-emerald-600 font-semibold">+8.4% vs bulan lalu</p>
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
                    <span class="text-bq-text-muted ml-1">15:00 – 19:00 (Rata-rata 92% okupansi)</span>
                </div>
                <div>
                    <span class="font-bold text-amber-700">❄️ Low Demand:</span>
                    <span class="text-bq-text-muted ml-1">09:00 – 12:00 (Peluang diskon)</span>
                </div>
            </div>
        </div>

        {{-- Peak Days of Week --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs space-y-3">
            <div>
                <h3 class="text-base font-bold text-bq-text">Okupansi Berdasarkan Hari (Peak Days)</h3>
                <p class="text-xs text-bq-text-muted">Persentase slot terisi dari Senin sampai Minggu</p>
            </div>
            <div class="h-64 mt-2">
                <canvas id="chart-daily-demand"></canvas>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 border border-bq-border text-xs flex items-center justify-between">
                <div>
                    <span class="font-bold text-bq-text">🏆 Hari Paling Ramai:</span>
                    <span class="text-bq-text-muted ml-1">Sabtu (94%) &amp; Minggu (91%)</span>
                </div>
                <div>
                    <span class="font-bold text-slate-700">📅 Hari Paling Longgar:</span>
                    <span class="text-bq-text-muted ml-1">Selasa (55%)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Staff & Resource Utilization Gauges ── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Staff Utilization --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-bq-text">Staff Utilization Rate</h3>
            <p class="text-xs text-bq-text-muted">Persentase jam kerja efektif yang dialokasikan untuk melayani customer</p>

            <div class="space-y-3 text-xs">
                <div>
                    <div class="flex justify-between font-semibold mb-1">
                        <span class="text-bq-text">Budi Santoso (Lead Specialist)</span>
                        <span class="text-emerald-700 font-bold">88% (35 Jam/minggu)</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-500" style="width: 88%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-semibold mb-1">
                        <span class="text-bq-text">Siti Rahma (Senior Therapist)</span>
                        <span class="text-emerald-700 font-bold">78% (31 Jam/minggu)</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-indigo-500" style="width: 78%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-semibold mb-1">
                        <span class="text-bq-text">Ahmad Fauzi (Junior Assistant)</span>
                        <span class="text-amber-700 font-bold">54% (16 Jam/minggu)</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-amber-400" style="width: 54%;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resource Utilization --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-bq-text">Resource &amp; Room Utilization</h3>
            <p class="text-xs text-bq-text-muted">Tingkat pemakaian ruangan, lapangan, atau workstation operasional</p>

            <div class="space-y-3 text-xs">
                <div>
                    <div class="flex justify-between font-semibold mb-1">
                        <span class="text-bq-text">Studio Main Court</span>
                        <span class="text-emerald-700 font-bold">92% Okupansi</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-500" style="width: 92%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-semibold mb-1">
                        <span class="text-bq-text">VIP Room 01</span>
                        <span class="text-indigo-700 font-bold">74% Okupansi</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-indigo-500" style="width: 74%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-semibold mb-1">
                        <span class="text-bq-text">Styling Station #3</span>
                        <span class="text-amber-700 font-bold">48% Okupansi</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-amber-400" style="width: 48%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Hourly Demand Chart
    const ctxHourly = document.getElementById('chart-hourly-demand').getContext('2d');
    new Chart(ctxHourly, {
        type: 'bar',
        data: {
            labels: ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'],
            datasets: [{
                label: 'Booked Sessions',
                data: [4, 6, 5, 8, 12, 18, 26, 28, 30, 24, 16, 7],
                backgroundColor: [
                    '#f59e0b', '#f59e0b', '#f59e0b', '#818cf8', '#818cf8',
                    '#4f46e5', '#4f46e5', '#4f46e5', '#4f46e5', '#4f46e5', '#818cf8', '#f59e0b'
                ],
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
                y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, color: '#9ca3af' } }
            }
        }
    });

    // 2. Daily Utilization Demand Chart
    const ctxDaily = document.getElementById('chart-daily-demand').getContext('2d');
    new Chart(ctxDaily, {
        type: 'line',
        data: {
            labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            datasets: [{
                label: 'Okupansi (%)',
                data: [62, 55, 68, 72, 86, 94, 91],
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
                    callbacks: { label: c => c.parsed.y + '% Slot Terisi' }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                y: { min: 0, max: 100, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, color: '#9ca3af', callback: v => v + '%' } }
            }
        }
    });
});
</script>
@endsection
