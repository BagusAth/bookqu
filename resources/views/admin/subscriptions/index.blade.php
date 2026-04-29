@extends('layouts.admin')

@section('content')
    @php($title = 'Subscriptions')
    <x-admin.topbar :title="$title" />

    <div class="bg-white border border-slate-200 rounded-lg">
        <div class="px-5 py-4 border-b border-slate-200">
            <div class="text-sm font-semibold">Daftar Langganan</div>
            <div class="text-xs text-slate-500">Status langganan tiap tenant</div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Tenant</th>
                        <th class="text-left font-medium px-5 py-3">Paket</th>
                        <th class="text-left font-medium px-5 py-3">Status</th>
                        <th class="text-left font-medium px-5 py-3">Berakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($subscriptions as $subscription)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $subscription->namabisnis }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ ucfirst($subscription->namapaket) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $subscription->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $subscription->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $subscription->langganan_berakhir ? \Illuminate\Support\Carbon::parse($subscription->langganan_berakhir)->format('d M Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-slate-500">Belum ada data langganan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
