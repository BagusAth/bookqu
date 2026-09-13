@extends('layouts.owner-layout')

@section('title', 'Staff & Resources')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    tab: '{{ $tab ?? 'staff' }}',
    search: '{{ addslashes($search ?? '') }}',
    addStaffModal: false,
    editStaffModal: false,
    activeStaff: { id: null, name: '', role: '', phone: '', email: '', availability: '', is_active: 1, service_ids: [] },
    addResourceModal: false,
    editResourceModal: false,
    activeResource: { id: null, name: '', type: '', capacity: 1, location: '', is_active: 1, service_ids: [] },
    openEditStaff(staff) {
        this.activeStaff = { ...staff };
        this.editStaffModal = true;
    },
    openEditResource(res) {
        this.activeResource = { ...res };
        this.editResourceModal = true;
    }
}">

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Staff &amp; Resources</h1>
            <p class="text-sm text-bq-text-muted mt-1">Kelola tim staf profesional dan aset fisik operasional untuk melayani customer Anda.</p>
        </div>
        <div class="flex items-center gap-2">
            <template x-if="tab === 'staff'">
                <button type="button" @click="addStaffModal = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition" id="btn-add-staff">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    + Tambah Staff
                </button>
            </template>
            <template x-if="tab === 'resources'">
                <button type="button" @click="addResourceModal = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition" id="btn-add-resource">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    + Tambah Resource
                </button>
            </template>
        </div>
    </div>

    {{-- ── Tabs Switcher ── --}}
    <div class="border-b border-bq-border flex items-center gap-6">
        <a href="{{ route('owner.staff-resources', ['tab' => 'staff']) }}"
            class="pb-3 text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $tab === 'staff' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted hover:text-bq-text' }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Staff Team
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ $staff->count() }}</span>
        </a>
        <a href="{{ route('owner.staff-resources', ['tab' => 'resources']) }}"
            class="pb-3 text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $tab === 'resources' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted hover:text-bq-text' }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Physical Resources &amp; Rooms
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ $resources->count() }}</span>
        </a>
    </div>

    {{-- ── TAB 1: STAFF ── --}}
    @if($tab === 'staff')
    <div class="space-y-4">
        {{-- Search staff --}}
        <div class="flex items-center justify-between gap-3">
            <form method="GET" action="{{ route('owner.staff-resources') }}" class="relative w-full sm:max-w-xs">
                <input type="hidden" name="tab" value="staff">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama atau role..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2 pl-9 pr-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
            </form>
        </div>

        {{-- Staff Table --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                            <th class="px-5 py-3.5">Staff Member</th>
                            <th class="px-5 py-3.5">Role</th>
                            <th class="px-5 py-3.5">Assigned Services</th>
                            <th class="px-5 py-3.5">Availability</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bq-border">
                        @forelse($staff as $item)
                            @php
                                $staffPayload = [
                                    'id'           => $item->id,
                                    'name'         => $item->name,
                                    'role'         => $item->role,
                                    'phone'        => $item->phone ?? '',
                                    'email'        => $item->email ?? '',
                                    'availability' => $item->availability ?? '',
                                    'is_active'    => (int) $item->is_active,
                                    'service_ids'  => $item->services->pluck('id')->toArray(),
                                ];
                            @endphp
                            <tr class="hover:bg-bq-background/40 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-700 text-xs">
                                            <span>{{ strtoupper(substr($item->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-bq-text text-sm">{{ $item->name }}</span>
                                            @if($item->phone || $item->email)
                                                <div class="text-[10px] text-bq-text-subtle">{{ $item->phone ?? $item->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-bq-text font-medium">{{ $item->role }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($item->services as $svc)
                                            <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium">{{ $svc->namalayanan }}</span>
                                        @empty
                                            <span class="text-bq-text-subtle text-[11px]">Semua Layanan</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">
                                    {{ $item->availability ?: 'Jadwal Standar' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <form method="POST" action="{{ route('owner.staff.toggle', $item->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                            <span>{{ $item->is_active ? 'Active' : 'Inactive' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditStaff(@json($staffPayload))" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit" id="btn-edit-staff-{{ $item->id }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('owner.staff.destroy', $item->id) }}" id="form-delete-staff-{{ $item->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                @click="$dispatch('open-confirm', { title: 'Hapus Staff?', message: 'Yakin ingin menghapus staff {{ $item->name }}?', formId: 'form-delete-staff-{{ $item->id }}' })"
                                                class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Delete" id="btn-delete-staff-{{ $item->id }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-bq-text-muted">
                                    Belum ada staf terdaftar. Klik "+ Tambah Staff" untuk menambahkan anggota tim.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ── TAB 2: RESOURCES ── --}}
    @if($tab === 'resources')
    <div class="space-y-4">
        {{-- Search resource --}}
        <div class="flex items-center justify-between gap-3">
            <form method="GET" action="{{ route('owner.staff-resources') }}" class="relative w-full sm:max-w-xs">
                <input type="hidden" name="tab" value="resources">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama atau tipe resource..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2 pl-9 pr-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
            </form>
        </div>

        {{-- Resource Table --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                            <th class="px-5 py-3.5">Resource / Facility</th>
                            <th class="px-5 py-3.5">Type &amp; Capacity</th>
                            <th class="px-5 py-3.5">Assigned Services</th>
                            <th class="px-5 py-3.5">Location</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bq-border">
                        @forelse($resources as $res)
                            @php
                                $resPayload = [
                                    'id'          => $res->id,
                                    'name'        => $res->name,
                                    'type'        => $res->type,
                                    'capacity'    => $res->capacity ?? 1,
                                    'location'    => $res->location ?? '',
                                    'is_active'   => (int) $res->is_active,
                                    'service_ids' => $res->services->pluck('id')->toArray(),
                                ];
                            @endphp
                            <tr class="hover:bg-bq-background/40 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-purple-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-bq-text text-sm">{{ $res->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-medium text-bq-text">{{ $res->type }}</span>
                                    <span class="text-bq-text-subtle block text-[11px]">{{ $res->capacity }} Orang</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($res->services as $svc)
                                            <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium">{{ $svc->namalayanan }}</span>
                                        @empty
                                            <span class="text-bq-text-subtle text-[11px]">Semua Layanan</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">
                                    {{ $res->location ?: 'Ruang Utama' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <form method="POST" action="{{ route('owner.resources.toggle', $res->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition {{ $res->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                            <span>{{ $res->is_active ? 'Active' : 'Inactive' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditResource(@json($resPayload))" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit" id="btn-edit-resource-{{ $res->id }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('owner.resources.destroy', $res->id) }}" id="form-delete-resource-{{ $res->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                @click="$dispatch('open-confirm', { title: 'Hapus Resource?', message: 'Yakin ingin menghapus {{ $res->name }}?', formId: 'form-delete-resource-{{ $res->id }}' })"
                                                class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Delete" id="btn-delete-resource-{{ $res->id }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-bq-text-muted">
                                    Belum ada resource / ruangan terdaftar. Klik "+ Tambah Resource" untuk menambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Add Staff Modal --}}
    <div x-show="addStaffModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addStaffModal = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Staff Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Daftarkan terapis, instruktur, atau staf layanan.</p>
            <form method="POST" action="{{ route('owner.staff.store') }}" class="mt-4 space-y-4" id="form-add-staff">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Rian Pratama" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Role / Jabatan <span class="text-rose-500">*</span></label>
                    <input type="text" name="role" required placeholder="Contoh: Senior Stylist" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">No. Telepon</label>
                        <input type="text" name="phone" placeholder="0812xxxx" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Email</label>
                        <input type="email" name="email" placeholder="staff@mail.com" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Ketersediaan / Jam Kerja</label>
                    <input type="text" name="availability" x-ref="addStaffAvailability" placeholder="Contoh: Senin - Jumat (09:00 - 17:00)" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    <div class="mt-1.5 flex flex-wrap gap-1">
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Senin - Jumat (09:00 - 17:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Senin - Jumat (09:00 - 17:00)</button>
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Shift Pagi (08:00 - 15:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Shift Pagi (08:00 - 15:00)</button>
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Shift Siang (13:00 - 20:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Shift Siang (13:00 - 20:00)</button>
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Setiap Hari (08:00 - 21:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Setiap Hari (08:00 - 21:00)</button>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Layanan yang Ditangani</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-xl border border-bq-border p-2.5">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded border-bq-border text-bq-primary">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addStaffModal = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Staff</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Staff Modal --}}
    <div x-show="editStaffModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editStaffModal = false">
            <h3 class="text-base font-bold text-bq-text">Edit Staff</h3>
            <p class="text-xs text-bq-text-muted mt-1">Perbarui profil dan penugasan layanan staf.</p>
            <form method="POST" :action="`/owner/staff/${activeStaff.id}`" class="mt-4 space-y-4" id="form-edit-staff">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="activeStaff.name" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Role / Jabatan <span class="text-rose-500">*</span></label>
                    <input type="text" name="role" x-model="activeStaff.role" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">No. Telepon</label>
                        <input type="text" name="phone" x-model="activeStaff.phone" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Email</label>
                        <input type="email" name="email" x-model="activeStaff.email" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Ketersediaan / Jam Kerja</label>
                    <input type="text" name="availability" x-model="activeStaff.availability" placeholder="Contoh: Senin - Jumat (09:00 - 17:00)" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    <div class="mt-1.5 flex flex-wrap gap-1">
                        <button type="button" @click="activeStaff.availability = 'Senin - Jumat (09:00 - 17:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Senin - Jumat (09:00 - 17:00)</button>
                        <button type="button" @click="activeStaff.availability = 'Shift Pagi (08:00 - 15:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Shift Pagi (08:00 - 15:00)</button>
                        <button type="button" @click="activeStaff.availability = 'Shift Siang (13:00 - 20:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Shift Siang (13:00 - 20:00)</button>
                        <button type="button" @click="activeStaff.availability = 'Setiap Hari (08:00 - 21:00)'" class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700 hover:bg-slate-200 transition">Setiap Hari (08:00 - 21:00)</button>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Status</label>
                    <select name="is_active" x-model="activeStaff.is_active" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Layanan yang Ditangani</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-xl border border-bq-border p-2.5">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" :checked="activeStaff.service_ids && activeStaff.service_ids.includes({{ $svc->id }})" class="rounded border-bq-border text-bq-primary">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="editStaffModal = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Resource Modal --}}
    <div x-show="addResourceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addResourceModal = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Resource / Ruangan Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Daftarkan aset fisik seperti lapangan, ruangan, atau kursi.</p>
            <form method="POST" action="{{ route('owner.resources.store') }}" class="mt-4 space-y-4" id="form-add-resource">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Resource / Room <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: VIP Studio 02, Court B" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Tipe Resource <span class="text-rose-500">*</span></label>
                        <input type="text" name="type" required placeholder="Contoh: Private Room" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Kapasitas (Orang)</label>
                        <input type="number" name="capacity" min="1" value="1" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Lokasi / Detail</label>
                    <input type="text" name="location" placeholder="Lantai 2, Ruang Belakang" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Layanan Terkait</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-xl border border-bq-border p-2.5">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded border-bq-border text-bq-primary">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addResourceModal = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Resource</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Resource Modal --}}
    <div x-show="editResourceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editResourceModal = false">
            <h3 class="text-base font-bold text-bq-text">Edit Resource / Ruangan</h3>
            <p class="text-xs text-bq-text-muted mt-1">Perbarui informasi fasilitas atau aset operasional.</p>
            <form method="POST" :action="`/owner/resources/${activeResource.id}`" class="mt-4 space-y-4" id="form-edit-resource">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Resource / Room <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="activeResource.name" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Tipe Resource <span class="text-rose-500">*</span></label>
                        <input type="text" name="type" x-model="activeResource.type" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Kapasitas (Orang)</label>
                        <input type="number" name="capacity" x-model="activeResource.capacity" min="1" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Lokasi / Detail</label>
                    <input type="text" name="location" x-model="activeResource.location" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Status</label>
                    <select name="is_active" x-model="activeResource.is_active" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Layanan Terkait</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-xl border border-bq-border p-2.5">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" :checked="activeResource.service_ids && activeResource.service_ids.includes({{ $svc->id }})" class="rounded border-bq-border text-bq-primary">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="editResourceModal = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
