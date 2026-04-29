@extends('layouts.admin')

@section('content')
    @php($title = 'Tenants')
    <x-admin.topbar :title="$title" />

    <div class="bg-white border border-slate-200 rounded-lg">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold">Daftar Tenant</div>
                <div class="text-xs text-slate-500">Monitoring bisnis yang aktif di platform</div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Nama Bisnis</th>
                        <th class="text-left font-medium px-5 py-3">Slug</th>
                        <th class="text-left font-medium px-5 py-3">Jenis Bisnis</th>
                        <th class="text-left font-medium px-5 py-3">Nomor HP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($tenants as $tenant)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $tenant->namabisnis }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $tenant->slug }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $tenant->jenisbisnis }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $tenant->nomorhp }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-slate-500">Belum ada data tenant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
