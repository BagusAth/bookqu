@extends('layouts.owner-layout')

@section('title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Welcome Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-bq-text sm:text-3xl" id="welcome-heading">
                Welcome back, {{ $tenant->user->namalengkap ?? 'Owner' }}
            </h1>
            <p class="mt-1 text-sm text-bq-text-muted">Here's what's happening with your business today.</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Notification Bell --}}
            <button class="relative rounded-lg border border-bq-border bg-bq-surface p-2.5 text-bq-text-muted transition-all hover:border-bq-border-strong hover:text-bq-text hover:shadow-sm" id="btn-notifications">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-bq-primary text-[10px] font-bold text-white">3</span>
            </button>
            {{-- Avatar --}}
            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-bq-primary to-violet-500 text-sm font-bold text-white shadow-md shadow-bq-primary/20" id="user-avatar">
                {{ strtoupper(substr($tenant->user->namalengkap ?? 'O', 0, 1)) }}{{ strtoupper(substr(explode(' ', $tenant->user->namalengkap ?? 'O')[1] ?? '', 0, 1)) }}
            </div>
        </div>
    </div>

    {{-- ── Trial Banner ── --}}
    @if ($statustrial)
        @include('components.owner.trial-banner', ['sisahari' => $sisahari])
    @endif

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" id="stats-grid">
        @include('components.owner.stat-card', [
            'ikon' => 'booking',
            'label' => 'Total Bookings',
            'nilai' => number_format($totalbooking),
            'perubahan' => abs($persenperubahanboking),
            'tipeperubahan' => $persenperubahanboking > 0 ? 'naik' : ($persenperubahanboking < 0 ? 'turun' : 'stabil'),
        ])

        @include('components.owner.stat-card', [
            'ikon' => 'revenue',
            'label' => 'Total Revenue',
            'nilai' => 'Rp ' . number_format($totalrevenue, 0, ',', '.'),
            'perubahan' => abs($persenperubahanrevenue),
            'tipeperubahan' => $persenperubahanrevenue > 0 ? 'naik' : ($persenperubahanrevenue < 0 ? 'turun' : 'stabil'),
        ])

        @include('components.owner.stat-card', [
            'ikon' => 'program',
            'label' => 'Active Programs',
            'nilai' => $programaktif,
            'perubahan' => 0,
            'tipeperubahan' => 'stabil',
        ])
    </div>

    {{-- ── Revenue Chart & Daily Trends ── --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        {{-- Revenue Growth Chart --}}
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5 lg:col-span-3" id="revenue-chart-card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-bq-text">Revenue Growth</h2>
                    <p class="text-sm text-bq-text-muted">Monthly earnings overview</p>
                </div>
                {{-- Period Toggle --}}
                <div x-data="{ periodnya: 'monthly' }" class="flex rounded-lg border border-bq-border bg-bq-background p-0.5">
                    <button
                        @click="periodnya = 'weekly'"
                        :class="periodnya === 'weekly' ? 'bg-bq-surface text-bq-text shadow-sm' : 'text-bq-text-muted hover:text-bq-text'"
                        class="rounded-md px-3.5 py-1.5 text-xs font-medium transition-all duration-200"
                        id="btn-period-weekly"
                    >
                        Weekly
                    </button>
                    <button
                        @click="periodnya = 'monthly'"
                        :class="periodnya === 'monthly' ? 'bg-bq-primary text-white shadow-sm' : 'text-bq-text-muted hover:text-bq-text'"
                        class="rounded-md px-3.5 py-1.5 text-xs font-medium transition-all duration-200"
                        id="btn-period-monthly"
                    >
                        Monthly
                    </button>
                </div>
            </div>
            <div class="mt-6">
                <canvas id="revenue-chart" height="220"></canvas>
            </div>
        </div>

        {{-- Daily Trends --}}
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5 lg:col-span-2" id="daily-trends-card">
            <h2 class="text-base font-semibold text-bq-text">Daily Trends</h2>
            <div class="mt-4 space-y-4">
                @forelse ($trendlayanan as $trend)
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg
                            {{ $trend['trennya'] === 'naik' ? 'bg-emerald-50' : ($trend['trennya'] === 'turun' ? 'bg-rose-50' : 'bg-indigo-50') }}
                        ">
                            @if ($trend['trennya'] === 'naik')
                                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            @elseif ($trend['trennya'] === 'turun')
                                <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 12h7m0 0l-3-3m3 3l-3 3"/>
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-bq-text">{{ $trend['namalayanan'] }}</p>
                            <p class="text-xs text-bq-text-muted">
                                @if ($trend['trennya'] === 'naik')
                                    +{{ $trend['persenperubahan'] }}% vs yesterday
                                @elseif ($trend['trennya'] === 'turun')
                                    {{ $trend['persenperubahan'] }}% vs yesterday
                                @else
                                    Stable performance
                                @endif
                            </p>
                        </div>
                        <span class="text-sm font-bold text-bq-text">{{ $trend['jumlahbooking'] }}</span>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <p class="text-sm text-bq-text-muted">No booking data for today yet.</p>
                    </div>
                @endforelse
            </div>
            @if ($trendlayanan->count() > 0)
                <div class="mt-5 border-t border-bq-border pt-4">
                    <a href="#" class="text-sm font-medium text-bq-primary hover:text-bq-primary-hover transition-colors" id="link-full-report">
                        View Full Report →
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Recent Activity ── --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="recent-activity-card">
        <div class="flex items-center justify-between border-b border-bq-border px-5 py-4">
            <h2 class="text-base font-semibold text-bq-text">Recent Activity</h2>
            <a href="#" class="text-sm font-medium text-bq-text-muted transition-colors hover:text-bq-primary" id="link-all-activity">
                View All Activity →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" id="activity-table">
                <thead>
                    <tr class="border-b border-bq-border">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Program</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Date</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @foreach ($aktivitasterbaru as $aktivitas)
                        <tr class="transition-colors hover:bg-bq-background/50">
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-bq-text">
                                {{ $aktivitas->layanan->namalayanan ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-bq-text-muted">
                                {{ $aktivitas->namapelanggan }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-bq-text-muted">
                                {{ $aktivitas->tanggalbooking->format('d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-center">
                                @php
                                    $warnastatus = match($aktivitas->status) {
                                        'completed', 'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase ring-1 ring-inset {{ $warnastatus }}">
                                    {{ $aktivitas->status === 'paid' ? 'confirmed' : $aktivitas->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ── Revenue Chart Script ── --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('revenue-chart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.15)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($labelbulan),
                datasets: [{
                    label: 'Revenue',
                    data: @json($datarevenueperbulan),
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(99, 102, 241)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: 'rgb(99, 102, 241)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e1b4b',
                        titleColor: '#e0e7ff',
                        bodyColor: '#fff',
                        bodyFont: { weight: '600' },
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 12, weight: '500' }
                        },
                        border: { display: false }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0,0,0,0.04)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 },
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                                return value;
                            }
                        },
                        border: { display: false }
                    }
                }
            }
        });
    });
</script>
@endsection
