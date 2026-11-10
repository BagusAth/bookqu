@extends('layouts.owner-layout')

@section('title', 'Paket & Tagihan')

{{--
    Paket & Tagihan (BQ-145, owner-portal.md §8.15): current plan, status and end date, usage,
    the plan comparison generated from the plan list (D-017) and billing history with invoices.
    Pemilik only (AC-PKT-03). Data from App\Actions\Subscription\GetSubscriptionOverview.
--}}

@php
    $card = 'rounded-xl border border-bq-border bg-bq-surface';
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
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header title="Paket & Tagihan" purpose="Paket Anda, apa yang didapat, dan apa yang sudah dibayar." />

    <section aria-labelledby="plan-title" class="{{ $card }} p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-2">
            <h2 id="plan-title" class="text-lg font-semibold text-bq-text">{{ $langgananaktif?->plan ? ucfirst($langgananaktif->plan->namapaket) : 'Belum ada paket' }}</h2>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $toneClass($statusTone) }}">{{ $statusLabel }}</span>
        </div>
        <p class="mt-1 text-sm text-bq-text-muted">
            @if ($endDate)
                {{ $statusLabel === 'Berakhir' ? 'Berakhir' : 'Berlaku sampai' }} {{ $endDate }}
                @if ($statustrial) · sisa {{ $sisahari }} hari uji coba @endif
            @else
                Pilih paket di bawah untuk mulai menerima booking online.
            @endif
        </p>

        <dl class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ($usageRows as [$title, $unit, $figure])
                @php
                    $limit = $figure['limit'];
                    $percent = $limit !== null && $limit > 0 ? min(100, (int) round($figure['used'] / $limit * 100)) : 0;
                @endphp
                <div>
                    <dt class="text-sm text-bq-text-muted">{{ $title }}</dt>
                    <dd class="mt-0.5 text-sm font-medium tabular-nums text-bq-text">
                        @if ($limit === null)
                            {{ $figure['used'] }} {{ $unit }} · tanpa batas
                        @else
                            {{ $figure['used'] }} dari {{ $limit }} {{ $unit }}
                        @endif
                    </dd>
                    @if ($limit !== null && $limit > 0)
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-bq-background" aria-hidden="true">
                            <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-bq-warning' : 'bg-bq-primary' }}" style="width: {{ $percent }}%"></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Comparison generated from the plan list (§6, D-017) --}}
    <section aria-labelledby="plans-title" class="space-y-3">
        <div>
            <h2 id="plans-title" class="text-base font-semibold text-bq-text">Bandingkan paket</h2>
            <p class="text-sm text-bq-text-muted">Memperpanjang paket yang sama menambah masa aktif dari tanggal berakhir. Ganti paket berlaku sejak tanggal bayar.</p>
        </div>
        <ul class="grid gap-4 md:grid-cols-3">
            @foreach ($semuapaket as $paket)
                @php $isCurrent = $currentPlanId === $paket->id; @endphp
                <li class="{{ $card }} flex flex-col p-4 {{ $isCurrent ? 'ring-2 ring-bq-primary' : '' }}">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-bq-text">{{ ucfirst($paket->namapaket) }}</h3>
                        @if ($isCurrent)
                            <span class="rounded-full bg-bq-primary-light px-2 py-0.5 text-xs font-medium text-bq-primary">Paket Anda</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-bq-text">Rp {{ number_format($paket->hargabulanan, 0, ',', '.') }}<span class="text-sm font-normal text-bq-text-muted"> / bulan</span></p>
                    <x-plan-features :plan="$paket" class="mt-3 flex-1 text-bq-text-muted" />
                    <a href="{{ route('owner.checkout', $paket->id) }}" class="mt-4 inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium {{ $isCurrent ? 'border border-bq-border bg-bq-surface text-bq-text hover:bg-bq-background' : 'bg-bq-primary text-white hover:bg-bq-primary-hover' }}">
                        {{ $isCurrent ? 'Perpanjang paket' : 'Pilih paket' }}
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="billing-title" class="{{ $card }}">
        <h2 id="billing-title" class="border-b border-bq-border px-4 py-3 text-base font-semibold text-bq-text">Riwayat tagihan</h2>
        @if (count($history) === 0)
            <p class="px-4 py-6 text-sm text-bq-text-muted">Belum ada tagihan.</p>
        @else
            <ul class="divide-y divide-bq-border">
                @foreach ($history as $row)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium tabular-nums text-bq-text">{{ $row['amount'] }}</p>
                            <p class="text-xs text-bq-text-muted">{{ $row['date'] }} · {{ $row['orderId'] }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $toneClass($row['status']['tone']) }}">{{ $row['status']['label'] }}</span>
                            @if ($row['invoiceUrl'])
                                <a href="{{ $row['invoiceUrl'] }}" class="text-sm text-bq-primary hover:text-bq-primary-hover">Invoice</a>
                            @elseif ($row['payUrl'])
                                <a href="{{ $row['payUrl'] }}" class="inline-flex items-center rounded-lg bg-bq-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-bq-primary-hover">Bayar sekarang</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
