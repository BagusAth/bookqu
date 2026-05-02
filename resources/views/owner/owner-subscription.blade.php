@extends('layouts.owner-layout')

@section('title', 'Subscription')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    @include('components.owner.page-header', [
        'judul' => 'Subscription Management',
        'subjudul' => 'Manage your plan, usage, and billing.',
    ])

    {{-- Trial Banner --}}
    @if ($statustrial)
        @include('components.owner.trial-banner', ['sisahari' => $sisahari])
    @endif

    {{-- Current Plan --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-bq-text">Current Plan</h2>
                    <span class="inline-flex rounded-full bg-bq-primary/10 px-2.5 py-0.5 text-xs font-semibold text-bq-primary uppercase">
                        {{ $langgananaktif?->plan?->namapaket ?? 'None' }}
                    </span>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase
                        {{ $langgananaktif?->status === 'trial' ? 'bg-amber-50 text-amber-700' : ($langgananaktif?->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700') }}">
                        {{ $langgananaktif?->status ?? 'inactive' }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-bq-text-muted">
                    Rp {{ number_format($langgananaktif?->plan?->hargabulanan ?? 0, 0, ',', '.') }}/month
                    @if ($statustrial)
                        · Trial ends {{ $langgananaktif->trial_berakhir->format('d M Y') }}
                    @endif
                </p>
            </div>
            <button class="inline-flex items-center gap-2 rounded-lg bg-bq-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5" id="btn-change-plan">
                Change Plan
            </button>
        </div>
    </div>

    {{-- Usage Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-bq-text">Services Used</p>
                <span class="text-sm font-bold text-bq-text">{{ $jumlahlayanan }} / {{ $maxlayanan }}</span>
            </div>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-bq-background">
                <div class="h-full rounded-full transition-all duration-500 {{ $persenlayanan > 80 ? 'bg-rose-500' : ($persenlayanan > 60 ? 'bg-amber-500' : 'bg-bq-primary') }}" style="width: {{ $persenlayanan }}%"></div>
            </div>
            <p class="mt-2 text-xs text-bq-text-muted">{{ $persenlayanan }}% of your plan limit</p>
        </div>
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-bq-text">Bookings This Month</p>
                <span class="text-sm font-bold text-bq-text">{{ $jumlahbookingbulanini }} / {{ $isunlimited ? '∞' : $maxbooking }}</span>
            </div>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-bq-background">
                <div class="h-full rounded-full transition-all duration-500 {{ $persenbooking > 80 ? 'bg-rose-500' : ($persenbooking > 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $isunlimited ? 30 : $persenbooking }}%"></div>
            </div>
            <p class="mt-2 text-xs text-bq-text-muted">{{ $isunlimited ? 'Unlimited bookings' : $persenbooking . '% of your plan limit' }}</p>
        </div>
    </div>

    {{-- Plans Comparison --}}
    <h2 class="text-lg font-semibold text-bq-text">Available Plans</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($semuapaket as $paket)
            @php
                $adalahaktif = $langgananaktif?->plan?->id === $paket->id;
                $warnapaket = match($paket->namapaket) {
                    'small' => 'border-bq-border',
                    'medium' => 'border-bq-primary ring-1 ring-bq-primary/20',
                    'pro' => 'border-violet-400 ring-1 ring-violet-400/20',
                    default => 'border-bq-border',
                };
            @endphp
            <div class="relative rounded-xl border bg-bq-surface p-6 transition-all hover:shadow-md {{ $warnapaket }}">
                @if ($paket->namapaket === 'medium')
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-bq-primary px-3 py-0.5 text-xs font-bold text-white shadow-sm">Popular</span>
                @endif
                <h3 class="text-lg font-bold text-bq-text capitalize">{{ $paket->namapaket }}</h3>
                <p class="mt-1 text-2xl font-bold text-bq-text">Rp {{ number_format($paket->hargabulanan, 0, ',', '.') }}<span class="text-sm font-normal text-bq-text-muted">/mo</span></p>
                <ul class="mt-4 space-y-2.5 text-sm text-bq-text-muted">
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Up to {{ $paket->maxlayanan }} services
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ $paket->isunlimited ? 'Unlimited' : number_format($paket->maxbooking) }} bookings/mo
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ $paket->namapaket === 'pro' ? 'Priority support' : 'Email support' }}
                    </li>
                </ul>
                <button class="mt-5 w-full rounded-lg py-2.5 text-sm font-semibold transition-all
                    {{ $adalahaktif ? 'border border-bq-border bg-bq-background text-bq-text-muted cursor-default' : 'bg-bq-primary text-white shadow-md shadow-bq-primary/25 hover:bg-bq-primary-hover hover:shadow-lg' }}">
                    {{ $adalahaktif ? 'Current Plan' : 'Select Plan' }}
                </button>
            </div>
        @endforeach
    </div>

    {{-- Payment History --}}
    @if ($riwayatpembayaran->count() > 0)
        <div class="rounded-xl border border-bq-border bg-bq-surface">
            <div class="border-b border-bq-border px-5 py-4">
                <h2 class="text-base font-semibold text-bq-text">Billing History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead><tr class="border-b border-bq-border">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Method</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-bq-text-muted">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-bq-border">
                        @foreach ($riwayatpembayaran as $bayar)
                            <tr class="hover:bg-bq-background/50">
                                <td class="px-5 py-3.5 text-sm text-bq-text">{{ $bayar->created_at->format('d M Y') }}</td>
                                <td class="px-5 py-3.5 text-sm font-medium text-bq-text">Rp {{ number_format($bayar->jumlah, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 text-sm text-bq-text-muted capitalize">{{ str_replace('_', ' ', $bayar->metode) }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase ring-1 ring-inset {{ $bayar->status === 'sukses' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20' }}">{{ $bayar->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
