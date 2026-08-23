<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Superadmin - BookQu</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen">
        {{-- ── Topbar ── --}}
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-lg font-bold text-blue-700">BookQu</span>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-600">Superadmin Panel</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-rose-600 hover:underline">Logout</button>
            </form>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-10 space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dashboard Superadmin</h1>
                <p class="mt-1 text-sm text-slate-500">Ringkasan seluruh tenant dan aktivitas platform BookQu.</p>
            </div>

            {{-- ── Statistik Utama ── --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @php
                    $stats = [
                        ['label' => 'Total Bisnis', 'nilai' => $totalTenant, 'warna' => 'bg-blue-600'],
                        ['label' => 'Total Owner', 'nilai' => $totalUser, 'warna' => 'bg-indigo-600'],
                        ['label' => 'Tenant Trial', 'nilai' => $tenantTrial, 'warna' => 'bg-amber-500'],
                        ['label' => 'Tenant Aktif', 'nilai' => $tenantAktif, 'warna' => 'bg-emerald-600'],
                        ['label' => 'Tenant Expired', 'nilai' => $tenantExpired, 'warna' => 'bg-rose-600'],
                    ];
                @endphp
                @foreach ($stats as $stat)
                    <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($stat['nilai']) }}</p>
                        <span class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold text-white {{ $stat['warna'] }}">
                            {{ $stat['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- ── Revenue Langganan ── --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-white border border-slate-200 p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Revenue Langganan (Total)</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">
                        Rp {{ number_format($totalRevenuePlatform, 0, ',', '.') }}
                    </p>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Revenue Langganan (Bulan Ini)</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600">
                        Rp {{ number_format($revenuebulanini, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- ── Daftar Tenant Terbaru ── --}}
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-base font-semibold text-slate-900">Tenant Terbaru</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-3">Nama Bisnis</th>
                                <th class="px-6 py-3">Owner</th>
                                <th class="px-6 py-3">Slug</th>
                                <th class="px-6 py-3">Status Langganan</th>
                                <th class="px-6 py-3">Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tenantTerbaru as $tenant)
                                @php
                                    $sub = $tenant->subscriptions->first();
                                    $statusWarna = match($sub?->status) {
                                        'trial'   => 'bg-amber-100 text-amber-700',
                                        'active'  => 'bg-emerald-100 text-emerald-700',
                                        'expired' => 'bg-rose-100 text-rose-700',
                                        default   => 'bg-slate-100 text-slate-500',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ $tenant->namabisnis ?? '-' }}</td>
                                    <td class="px-6 py-3 text-slate-600">
                                        <p>{{ $tenant->user?->namalengkap ?? '-' }}</p>
                                        <p class="text-xs text-slate-400">{{ $tenant->user?->email }}</p>
                                    </td>
                                    <td class="px-6 py-3 text-slate-500">{{ $tenant->slug ?? '-' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusWarna }}">
                                            {{ ucfirst($sub?->status ?? 'Belum ada') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-slate-500">
                                        {{ $tenant->created_at?->format('d M Y') ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada tenant terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-center">
                <a href="/" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Landing Page</a>
            </div>
        </main>
    </div>
</body>
</html>
