@extends('layouts.admin-layout')

@section('title', 'Dashboard Superadmin')

@section('content')
    <x-owner.page-header title="Dashboard Superadmin" purpose="Ringkasan seluruh bisnis dan aktivitas platform BookQu." />

    @if ($pendingPayoutCount > 0 || $pendingRefundCount > 0)
        <div class="grid gap-3 md:grid-cols-2">
            @if ($pendingPayoutCount > 0)
                <x-ui.banner tone="warning" icon="wallet"
                    title="{{ $pendingPayoutCount }} pengajuan withdraw menunggu review"
                    description="Periksa rekening tujuan, lalu setujui atau tolak.">
                    <x-slot:action>
                        <x-ui.button variant="primary" size="sm" :href="route('admin.payouts', ['status' => 'pending'])">Review withdraw</x-ui.button>
                    </x-slot:action>
                </x-ui.banner>
            @endif
            @if ($pendingRefundCount > 0)
                <x-ui.banner tone="warning" icon="card"
                    title="{{ $pendingRefundCount }} refund menunggu transfer"
                    description="Transfer ke rekening pelanggan, lalu catat referensinya.">
                    <x-slot:action>
                        <x-ui.button size="sm" :href="route('admin.refunds')">Proses refund</x-ui.button>
                    </x-slot:action>
                </x-ui.banner>
            @endif
        </div>
    @endif

    <section aria-label="Bisnis dan langganan" class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
        <x-ui.stat label="Total bisnis" :value="number_format($totalTenant, 0, ',', '.')" />
        <x-ui.stat label="Total owner" :value="number_format($totalUser, 0, ',', '.')" />
        <x-ui.stat label="Langganan trial" :value="number_format($tenantTrial, 0, ',', '.')" tone="warning" />
        <x-ui.stat label="Langganan aktif" :value="number_format($tenantAktif, 0, ',', '.')" tone="success" />
        <x-ui.stat label="Langganan berakhir" :value="number_format($tenantExpired, 0, ',', '.')" tone="danger" />
    </section>

    <section aria-label="Pendapatan langganan dan early access" class="grid gap-3 md:grid-cols-3">
        <x-ui.stat label="Pendapatan langganan bulan ini" :value="'Rp'.number_format($revenuebulanini, 0, ',', '.')" hero
            context="Pembayaran langganan yang sukses di bulan berjalan" />
        <x-ui.stat label="Pendapatan langganan total" :value="'Rp'.number_format($totalRevenuePlatform, 0, ',', '.')"
            context="Sejak platform berjalan" />
        <x-ui.stat label="Pendaftar early access" :value="number_format($earlyAccessTotal, 0, ',', '.')"
            :context="number_format($earlyAccessLastWeek, 0, ',', '.').' dalam 7 hari terakhir'" :href="route('admin.early-access')" />
    </section>

    <x-ui.panel title="Bisnis terbaru" description="10 bisnis yang terakhir mendaftar." flush>
        <div class="overflow-x-auto">
            <table class="w-full text-bq-body">
                <thead>
                    <tr class="border-b border-bq-border text-left text-bq-small text-bq-text-muted">
                        <th scope="col" class="px-4 py-2.5 font-semibold">Nama bisnis</th>
                        <th scope="col" class="px-4 py-2.5 font-semibold">Owner</th>
                        <th scope="col" class="px-4 py-2.5 font-semibold">Slug</th>
                        <th scope="col" class="px-4 py-2.5 font-semibold">Langganan</th>
                        <th scope="col" class="px-4 py-2.5 font-semibold">Terdaftar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @forelse ($tenantTerbaru as $tenant)
                        @php
                            $subscriptionStatus = $tenant->subscriptions->first()?->status;
                            [$subscriptionLabel, $subscriptionTone] = match ($subscriptionStatus) {
                                'trial' => ['Trial', 'warning'],
                                'active' => ['Aktif', 'success'],
                                'expired' => ['Berakhir', 'danger'],
                                null => ['Belum ada', 'neutral'],
                                default => [ucfirst($subscriptionStatus), 'neutral'],
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-bq-ink">{{ $tenant->namabisnis ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <p>{{ $tenant->user?->namalengkap ?? '-' }}</p>
                                <p class="text-bq-small text-bq-text-muted">{{ $tenant->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-bq-text-muted">{{ $tenant->slug ?? '-' }}</td>
                            <td class="px-4 py-3"><x-ui.badge :tone="$subscriptionTone">{{ $subscriptionLabel }}</x-ui.badge></td>
                            <td class="bq-num px-4 py-3 text-bq-text-muted">{{ $tenant->created_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-ui.empty title="Belum ada bisnis terdaftar" description="Bisnis baru akan muncul di sini setelah owner mendaftar." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>
@endsection
