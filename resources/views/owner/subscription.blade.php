@extends('layouts.owner-layout')

@section('title', 'Paket & Tagihan')

{{--
    Paket & Tagihan (BQ-145, owner-portal.md §8.15): current plan, status and end date, usage,
    the plan comparison generated from the plan list (D-017) and billing history with invoices.
    Pemilik only (AC-PKT-03). Data from App\Actions\Subscription\GetSubscriptionOverview.
--}}

@php
    $card = 'bq-panel';
    $toneClass = fn (string $tone): string => match ($tone) {
        'success' => 'bg-bq-success-bg text-bq-success',
        'danger' => 'bg-bq-danger-bg text-bq-danger',
        'warning' => 'bg-bq-warning-bg text-bq-warning',
        default => 'bg-bq-background text-bq-text-muted ring-1 ring-inset ring-bq-border',
    };
    $statusTone = match ($statusLabel) {
        'Aktif' => 'success',
        'Uji coba' => 'warning',
        default => 'danger',
    };
    $usageRows = [
        ['Layanan', 'layanan', $usage['services']],
        ['Booking bulan ini', 'booking', $usage['bookings']],
        ['Akun tim', 'akun', $usage['team']],
    ];
    $currentPlanId = $langgananaktif?->plan?->id;
    $currentLevel = $currentPlanId ? \App\Domain\Subscription\PlanCapability::getPlanLevel($langgananaktif->plan->namapaket) : null;
    $comparisonPlans = $semuapaket->sortBy(fn ($plan) => \App\Domain\Subscription\PlanCapability::getPlanLevel($plan->namapaket))->values();
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header title="Paket & Tagihan" purpose="Paket Anda, apa yang didapat, dan riwayat pembayaran." />

    <div class="grid gap-4 lg:grid-cols-[2fr_1fr] lg:items-start">
        <x-ui.panel :title="$langgananaktif?->plan ? 'Paket '.ucfirst($langgananaktif->plan->namapaket) : 'Belum ada paket'">
            <x-ui.figures :value="$daysRemaining.' hari'"
                :label="$endDate ? ($statusLabel === 'Berakhir' ? 'Berakhir '.$endDate : 'tersisa, sampai '.$endDate) : 'Pilih paket untuk mulai menerima booking online.'"
                :context="$endDate && $statusLabel !== 'Berakhir' ? 'Perpanjangan menambah masa aktif dari tanggal berakhir.' : null"
                class="!grid-cols-1" />
            <x-ui.status-dot :tone="$statusTone" class="mt-3">{{ $statusLabel }}</x-ui.status-dot>
        </x-ui.panel>
        <x-ui.panel title="Pemakaian">
            <dl class="grid gap-4">
                @foreach ($usageRows as [$title, $unit, $figure])
                    @php
                        $limit = $figure['limit'];
                        $percent = $limit !== null && $limit > 0 ? min(100, (int) round($figure['used'] / $limit * 100)) : 0;
                    @endphp
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                            <dt class="text-sm text-bq-text-muted">{{ $title }}</dt>
                            <dd class="mt-0.5 text-sm font-medium bq-num text-bq-text">
                                @if ($limit === null)
                                    {{ $figure['used'] }} {{ $unit }} · tanpa batas
                                @else
                                    {{ $figure['used'] }} dari {{ $limit }} {{ $unit }}
                                @endif
                            </dd>
                        </div>
                        @if ($limit !== null && $limit > 0)
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-bq-background" aria-hidden="true">
                                <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-bq-warning' : 'bg-bq-primary' }}" style="width: {{ $percent }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </dl>
        </x-ui.panel>
    </div>

    {{-- Comparison generated from the plan list (§6, D-017) --}}
    <x-ui.panel title="Bandingkan paket" description="Harga per bulan. Ganti paket berlaku sejak tanggal bayar.">
        <div class="grid gap-3" x-data="{ plan: @js($currentPlanId ?? $comparisonPlans->first()?->id) }">
            <div class="inline-flex flex-wrap gap-1 rounded-bq-control bg-bq-surface-2 p-1 md:hidden" role="group" aria-label="Pilih paket untuk dibandingkan">
                @foreach ($comparisonPlans as $paket)
                    <button type="button" class="rounded-bq-control px-3 py-1.5 text-bq-label"
                        :class="plan === @js($paket->id) && 'bg-bq-surface text-bq-primary'"
                        :aria-pressed="plan === @js($paket->id)" @click="plan = @js($paket->id)">{{ ucfirst($paket->namapaket) }}</button>
                @endforeach
            </div>
            <ul class="grid gap-4 md:grid-cols-3">
                @foreach ($comparisonPlans as $paket)
                    @php
                        $isCurrent = $currentPlanId === $paket->id;
                        $previous = $loop->first ? null : $comparisonPlans[$loop->index - 1];
                        $level = \App\Domain\Subscription\PlanCapability::getPlanLevel($paket->namapaket);
                    @endphp
                    <li class="{{ $card }} flex-col p-4 {{ $isCurrent ? 'ring-2 ring-bq-primary' : '' }}"
                        :class="plan === @js($paket->id) ? 'flex' : 'hidden md:flex'">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-base font-semibold text-bq-text">{{ ucfirst($paket->namapaket) }}</h3>
                            @if ($isCurrent)
                                <span class="rounded-full bg-bq-primary-light px-2 py-0.5 text-xs font-medium text-bq-primary">Paket Anda</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xl font-semibold bq-num text-bq-text">{{ \App\Support\Rupiah::format($paket->hargabulanan) }}<span class="text-sm font-normal text-bq-text-muted">/bulan</span></p>
                        <p class="mt-3 text-bq-small text-bq-text-muted">{{ $previous ? 'Semua di '.ucfirst($previous->namapaket).', ditambah:' : 'Termasuk:' }}</p>
                        <x-plan-features :plan="$paket" :previous="$previous" class="mt-2 flex-1 text-bq-text-muted" />
                        <a href="{{ route('owner.checkout', $paket->id) }}" class="mt-4 bq-btn {{ $isCurrent ? 'bq-btn-secondary' : 'bq-btn-primary' }}">
                            {{ $isCurrent ? 'Perpanjang' : ($currentLevel !== null && $level < $currentLevel ? 'Turun ke '.ucfirst($paket->namapaket) : 'Pilih paket') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-ui.panel>

    <section aria-labelledby="billing-title" class="{{ $card }}">
        <h2 id="billing-title" class="border-b border-bq-border px-4 py-3 text-base font-semibold text-bq-text">Riwayat tagihan</h2>
        @if (count($history) === 0)
            <p class="px-4 py-6 text-sm text-bq-text-muted">Belum ada tagihan.</p>
        @else
            <ul class="divide-y divide-bq-border">
                @foreach ($history as $row)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium bq-num text-bq-text">{{ $row['amount'] }}</p>
                            <p class="text-xs text-bq-text-muted">{{ $row['date'] }} · {{ $row['orderId'] }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bq-badge {{ $toneClass($row['status']['tone']) }}">{{ $row['status']['label'] }}</span>
                            @if ($row['invoiceUrl'])
                                <a href="{{ $row['invoiceUrl'] }}" class="text-sm bq-link">Invoice</a>
                            @elseif ($row['payUrl'])
                                <a href="{{ $row['payUrl'] }}" class="bq-btn bq-btn-primary bq-btn-sm">Bayar sekarang</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
