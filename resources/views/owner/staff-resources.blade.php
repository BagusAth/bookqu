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
        this.activeStaff = { ...staff, service_ids: Array.isArray(staff.service_ids) ? [...staff.service_ids] : [] };
        this.editStaffModal = true;
    },
    openEditResource(res) {
        this.activeResource = { ...res, service_ids: Array.isArray(res.service_ids) ? [...res.service_ids] : [] };
        this.editResourceModal = true;
    },
    toggleStaffService(id) {
        if (!this.activeStaff.service_ids) this.activeStaff.service_ids = [];
        const index = this.activeStaff.service_ids.indexOf(id);
        if (index > -1) {
            this.activeStaff.service_ids.splice(index, 1);
        } else {
            this.activeStaff.service_ids.push(id);
        }
    },
    toggleResourceService(id) {
        if (!this.activeResource.service_ids) this.activeResource.service_ids = [];
        const index = this.activeResource.service_ids.indexOf(id);
        if (index > -1) {
            this.activeResource.service_ids.splice(index, 1);
        } else {
            this.activeResource.service_ids.push(id);
        }
    }
}">

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#231a3d] sm:text-3xl">Staff &amp; Resources</h1>
            <p class="text-sm text-[#6e6584] mt-1">Kelola tim staf profesional dan aset fisik operasional untuk melayani customer Anda.</p>
        </div>
        <div class="flex items-center gap-2">
            <template x-if="tab === 'staff'">
                <button
                    type="button"
                    @click="addStaffModal = true"
                    class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-[#2d1a6d]"
                    id="btn-add-staff"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    + Tambah Staff
                </button>
            </template>
            <template x-if="tab === 'resources'">
                <button
                    type="button"
                    @click="addResourceModal = true"
                    class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-[#2d1a6d]"
                    id="btn-add-resource"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    + Tambah Resource
                </button>
            </template>
        </div>
    </div>

    {{-- ── Tabs Switcher ── --}}
    <div class="border-b border-[#e7e2f7] flex items-center gap-6">
        <a
            href="{{ route('owner.staff-resources', ['tab' => 'staff']) }}"
            class="pb-3 text-sm font-extrabold border-b-2 transition flex items-center gap-2 {{ $tab === 'staff' ? 'border-[#382186] text-[#382186]' : 'border-transparent text-[#6e6584] hover:text-[#231a3d]' }}"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Staff Team
            <span class="rounded-full bg-[#f3effe] px-2.5 py-0.5 text-[11px] font-bold text-[#382186] border border-[#b499ff]/40">{{ $staff->count() }}</span>
        </a>
        <a
            href="{{ route('owner.staff-resources', ['tab' => 'resources']) }}"
            class="pb-3 text-sm font-extrabold border-b-2 transition flex items-center gap-2 {{ $tab === 'resources' ? 'border-[#382186] text-[#382186]' : 'border-transparent text-[#6e6584] hover:text-[#231a3d]' }}"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Fasilitas Fisik &amp; Ruangan
            <span class="rounded-full bg-[#f3effe] px-2.5 py-0.5 text-[11px] font-bold text-[#382186] border border-[#b499ff]/40">{{ $resources->count() }}</span>
        </a>
    </div>

    {{-- ── TAB 1: STAFF ── --}}
    @if($tab === 'staff')
    <div class="space-y-4">
        {{-- Search staff --}}
        <div class="flex items-center justify-between gap-3">
            <form method="GET" action="{{ route('owner.staff-resources') }}" class="relative w-full sm:max-w-xs">
                <input type="hidden" name="tab" value="staff">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Cari nama atau role..."
                    class="w-full rounded-xl border border-[#e7e2f7] bg-white py-2 pl-9 pr-3 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                >
            </form>
        </div>

        {{-- Staff Table --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_24px_rgba(35,26,61,0.03)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#e7e2f7] bg-[#f7f7fa] font-extrabold uppercase tracking-wider text-[#6e6584]">
                            <th class="px-5 py-3.5">Staff Member</th>
                            <th class="px-5 py-3.5">Role</th>
                            <th class="px-5 py-3.5">Assigned Services</th>
                            <th class="px-5 py-3.5">Availability</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e7e2f7]">
                        @forelse($staff as $item)
                            @php
                                $staffPayload = [
                                    'id'           => $item->id,
                                    'name'         => $item->name,
                                    'role'         => $item->role,
                                    'phone'        => $item->phone ?? '',
                                    'email'        => $item->email ?? '',
                                    'availability' => $item->availability_schedule ?? $item->availability ?? '',
                                    'is_active'    => (int) $item->is_active,
                                    'service_ids'  => $item->services->pluck('id')->toArray(),
                                ];
                            @endphp
                            <tr class="hover:bg-[#f7f7fa]/60 transition duration-150">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] font-black text-[#382186] text-xs border border-[#e7e2f7]">
                                            <span>{{ strtoupper(substr($item->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <span class="font-extrabold text-[#231a3d] text-sm">{{ $item->name }}</span>
                                            @if($item->phone || $item->email)
                                                <div class="text-[10px] text-[#6e6584] font-medium">{{ $item->phone ?? $item->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-[#231a3d] font-bold">{{ $item->role }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @forelse($item->services->take(2) as $svc)
                                            <span class="rounded-md bg-[#f3effe] text-[#382186] border border-[#b499ff]/40 px-2 py-0.5 text-[10px] font-bold">{{ $svc->namalayanan }}</span>
                                        @empty
                                            <span class="text-[#6e6584] text-[11px] italic">Semua Layanan</span>
                                        @endforelse
                                        @if($item->services->count() > 2)
                                            <span
                                                class="rounded-md bg-[#f7f7fa] text-[#6e6584] border border-[#e7e2f7] px-1.5 py-0.5 text-[10px] font-bold cursor-help"
                                                title="{{ $item->services->pluck('namalayanan')->join(', ') }}"
                                            >
                                                +{{ $item->services->count() - 2 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-[#6e6584] font-medium">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#f7f7fa] px-2.5 py-1 text-xs border border-[#e7e2f7] text-[#231a3d] font-semibold">
                                        <svg class="h-3 w-3 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $item->availability_schedule ?: ($item->availability ?: 'Jadwal Standar') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <form method="POST" action="{{ route('owner.staff.toggle', $item->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="craft-btn inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-extrabold uppercase border {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-[#f7f7fa] text-[#6e6584] border-[#e7e2f7] hover:bg-[#e7e2f7]' }}"
                                        >
                                            <span>{{ $item->is_active ? 'Active' : 'Inactive' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="openEditStaff(@json($staffPayload))"
                                            class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-[#f3effe] hover:text-[#382186] transition"
                                            title="Edit"
                                            id="btn-edit-staff-{{ $item->id }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('owner.staff.destroy', $item->id) }}" id="form-delete-staff-{{ $item->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                @click="$dispatch('open-confirm', { title: 'Hapus Staff?', message: 'Yakin ingin menghapus staff {{ $item->name }}?', formId: 'form-delete-staff-{{ $item->id }}' })"
                                                class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-rose-50 hover:text-rose-600 transition"
                                                title="Delete"
                                                id="btn-delete-staff-{{ $item->id }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7] shadow-2xs mb-3">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-xs font-bold text-[#231a3d]">
                                        {{ !empty($search) ? 'Tidak ada staf yang sesuai dengan pencarian' : 'Belum ada staf terdaftar' }}
                                    </p>
                                    <p class="text-[11px] text-[#6e6584] mt-1 max-w-sm mx-auto">
                                        {{ !empty($search) ? 'Coba periksa kembali ejaan kata kunci atau reset filter pencarian.' : 'Tambahkan staf pertama Anda untuk mulai menugaskan layanan dan jadwal operasional.' }}
                                    </p>
                                    <div class="mt-4 flex items-center justify-center gap-2">
                                        @if(!empty($search))
                                            <a href="{{ route('owner.staff-resources', ['tab' => 'staff']) }}" class="craft-btn inline-flex items-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-3 py-1.5 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                                                Reset Pencarian
                                            </a>
                                        @endif
                                        <button type="button" @click="addStaffModal = true" class="craft-btn inline-flex items-center gap-1.5 rounded-xl bg-[#382186] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-2xs">
                                            + Tambah Staff
                                        </button>
                                    </div>
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
        {{-- Search resources --}}
        <div class="flex items-center justify-between gap-3">
            <form method="GET" action="{{ route('owner.staff-resources') }}" class="relative w-full sm:max-w-xs">
                <input type="hidden" name="tab" value="resources">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Cari resource atau tipe..."
                    class="w-full rounded-xl border border-[#e7e2f7] bg-white py-2 pl-9 pr-3 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                >
            </form>
        </div>

        {{-- Resources Table --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_24px_rgba(35,26,61,0.03)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#e7e2f7] bg-[#f7f7fa] font-extrabold uppercase tracking-wider text-[#6e6584]">
                            <th class="px-5 py-3.5">Resource / Room Name</th>
                            <th class="px-5 py-3.5">Type &amp; Capacity</th>
                            <th class="px-5 py-3.5">Assigned Services</th>
                            <th class="px-5 py-3.5">Location</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e7e2f7]">
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
                            <tr class="hover:bg-[#f7f7fa]/60 transition duration-150">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7]">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <span class="font-extrabold text-[#231a3d] text-sm">{{ $res->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-bold text-[#231a3d]">{{ $res->type }}</span>
                                    <span class="text-[#6e6584] block text-[11px] font-medium">{{ $res->capacity }} Orang</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @forelse($res->services->take(2) as $svc)
                                            <span class="rounded-md bg-[#f3effe] text-[#382186] border border-[#b499ff]/40 px-2 py-0.5 text-[10px] font-bold">{{ $svc->namalayanan }}</span>
                                        @empty
                                            <span class="text-[#6e6584] text-[11px] italic">Semua Layanan</span>
                                        @endforelse
                                        @if($res->services->count() > 2)
                                            <span
                                                class="rounded-md bg-[#f7f7fa] text-[#6e6584] border border-[#e7e2f7] px-1.5 py-0.5 text-[10px] font-bold cursor-help"
                                                title="{{ $res->services->pluck('namalayanan')->join(', ') }}"
                                            >
                                                +{{ $res->services->count() - 2 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-[#6e6584] font-medium">
                                    {{ $res->location ?: 'Ruang Utama' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <form method="POST" action="{{ route('owner.resources.toggle', $res->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="craft-btn inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-extrabold uppercase border {{ $res->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-[#f7f7fa] text-[#6e6584] border-[#e7e2f7] hover:bg-[#e7e2f7]' }}"
                                        >
                                            <span>{{ $res->is_active ? 'Active' : 'Inactive' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="openEditResource(@json($resPayload))"
                                            class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-[#f3effe] hover:text-[#382186] transition"
                                            title="Edit"
                                            id="btn-edit-resource-{{ $res->id }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('owner.resources.destroy', $res->id) }}" id="form-delete-resource-{{ $res->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                @click="$dispatch('open-confirm', { title: 'Hapus Resource?', message: 'Yakin ingin menghapus {{ $res->name }}?', formId: 'form-delete-resource-{{ $res->id }}' })"
                                                class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-rose-50 hover:text-rose-600 transition"
                                                title="Delete"
                                                id="btn-delete-resource-{{ $res->id }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7] shadow-2xs mb-3">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <p class="text-xs font-bold text-[#231a3d]">
                                        {{ !empty($search) ? 'Tidak ada resource yang sesuai dengan pencarian' : 'Belum ada fasilitas / ruangan terdaftar' }}
                                    </p>
                                    <p class="text-[11px] text-[#6e6584] mt-1 max-w-sm mx-auto">
                                        {{ !empty($search) ? 'Coba periksa kembali ejaan kata kunci atau reset filter pencarian.' : 'Daftarkan ruangan, studio, atau alat fisik pertama untuk mulai menghubungkannya dengan layanan.' }}
                                    </p>
                                    <div class="mt-4 flex items-center justify-center gap-2">
                                        @if(!empty($search))
                                            <a href="{{ route('owner.staff-resources', ['tab' => 'resources']) }}" class="craft-btn inline-flex items-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-3 py-1.5 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                                                Reset Pencarian
                                            </a>
                                        @endif
                                        <button type="button" @click="addResourceModal = true" class="craft-btn inline-flex items-center gap-1.5 rounded-xl bg-[#382186] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-2xs">
                                            + Tambah Resource
                                        </button>
                                    </div>
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
    <div
        x-show="addStaffModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        style="display: none;"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-[#e7e2f7]"
            @click.outside="addStaffModal = false"
            x-show="addStaffModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <h3 class="text-base font-extrabold text-[#231a3d]">Tambah Staff Baru</h3>
                <button @click="addStaffModal = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Daftarkan terapis, instruktur, atau staf layanan.</p>
            <form method="POST" action="{{ route('owner.staff.store') }}" class="mt-4 space-y-4" id="form-add-staff">
                @csrf
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Contoh: Rian Pratama"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Role / Jabatan <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="role"
                        required
                        placeholder="Contoh: Senior Stylist"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">No. Telepon</label>
                        <input
                            type="text"
                            name="phone"
                            placeholder="0812xxxx"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Email</label>
                        <input
                            type="email"
                            name="email"
                            placeholder="staff@mail.com"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Ketersediaan / Jam Kerja</label>
                    <input
                        type="text"
                        name="availability"
                        x-ref="addStaffAvailability"
                        placeholder="Contoh: Senin - Jumat (09:00 - 17:00)"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Senin - Jumat (09:00 - 17:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Senin - Jumat (09:00 - 17:00)</button>
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Shift Pagi (08:00 - 15:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Shift Pagi (08:00 - 15:00)</button>
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Shift Siang (13:00 - 20:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Shift Siang (13:00 - 20:00)</button>
                        <button type="button" @click="$refs.addStaffAvailability.value = 'Setiap Hari (08:00 - 21:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Setiap Hari (08:00 - 21:00)</button>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Status Awal</label>
                    <select
                        name="is_active"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option value="1" selected>Active (Siap Melayani)</option>
                        <option value="0">Inactive (Nonaktif)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Layanan yang Ditangani</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]/60 p-3">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-[#231a3d] font-semibold cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#e7e2f7]">
                    <button type="button" @click="addStaffModal = false" class="craft-btn rounded-xl px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition">Batal</button>
                    <button type="submit" class="craft-btn rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition">Simpan Staff</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Staff Modal --}}
    <div
        x-show="editStaffModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        style="display: none;"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-[#e7e2f7]"
            @click.outside="editStaffModal = false"
            x-show="editStaffModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <h3 class="text-base font-extrabold text-[#231a3d]">Edit Staff</h3>
                <button @click="editStaffModal = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Perbarui profil dan penugasan layanan staf.</p>
            <form method="POST" :action="`/owner/staff/${activeStaff.id}`" class="mt-4 space-y-4" id="form-edit-staff">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        x-model="activeStaff.name"
                        required
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Role / Jabatan <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="role"
                        x-model="activeStaff.role"
                        required
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">No. Telepon</label>
                        <input
                            type="text"
                            name="phone"
                            x-model="activeStaff.phone"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Email</label>
                        <input
                            type="email"
                            name="email"
                            x-model="activeStaff.email"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Ketersediaan / Jam Kerja</label>
                    <input
                        type="text"
                        name="availability"
                        x-model="activeStaff.availability"
                        placeholder="Contoh: Senin - Jumat (09:00 - 17:00)"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button type="button" @click="activeStaff.availability = 'Senin - Jumat (09:00 - 17:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Senin - Jumat (09:00 - 17:00)</button>
                        <button type="button" @click="activeStaff.availability = 'Shift Pagi (08:00 - 15:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Shift Pagi (08:00 - 15:00)</button>
                        <button type="button" @click="activeStaff.availability = 'Shift Siang (13:00 - 20:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Shift Siang (13:00 - 20:00)</button>
                        <button type="button" @click="activeStaff.availability = 'Setiap Hari (08:00 - 21:00)'" class="craft-btn rounded-lg bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-1 text-[10px] font-bold text-[#382186] hover:bg-[#f3effe]">Setiap Hari (08:00 - 21:00)</button>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Status</label>
                    <select
                        name="is_active"
                        x-model="activeStaff.is_active"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Layanan yang Ditangani</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]/60 p-3">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-[#231a3d] font-semibold cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" :checked="activeStaff.service_ids && activeStaff.service_ids.includes({{ $svc->id }})" @change="toggleStaffService({{ $svc->id }})" class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#e7e2f7]">
                    <button type="button" @click="editStaffModal = false" class="craft-btn rounded-xl px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition">Batal</button>
                    <button type="submit" class="craft-btn rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Resource Modal --}}
    <div
        x-show="addResourceModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        style="display: none;"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-[#e7e2f7]"
            @click.outside="addResourceModal = false"
            x-show="addResourceModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <h3 class="text-base font-extrabold text-[#231a3d]">Tambah Resource / Ruangan Baru</h3>
                <button @click="addResourceModal = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Daftarkan aset fisik seperti lapangan, ruangan, atau kursi.</p>
            <form method="POST" action="{{ route('owner.resources.store') }}" class="mt-4 space-y-4" id="form-add-resource">
                @csrf
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Nama Resource / Room <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Contoh: VIP Studio 02, Court B"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Tipe Resource <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            name="type"
                            required
                            placeholder="Contoh: Private Room"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Kapasitas (Orang)</label>
                        <input
                            type="number"
                            name="capacity"
                            min="1"
                            value="1"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Lokasi / Detail</label>
                    <input
                        type="text"
                        name="location"
                        placeholder="Lantai 2, Ruang Belakang"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Status Awal</label>
                    <select
                        name="is_active"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option value="1" selected>Active (Tersedia)</option>
                        <option value="0">Inactive (Perawatan / Nonaktif)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Layanan Terkait</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]/60 p-3">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-[#231a3d] font-semibold cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#e7e2f7]">
                    <button type="button" @click="addResourceModal = false" class="craft-btn rounded-xl px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition">Batal</button>
                    <button type="submit" class="craft-btn rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition">Simpan Resource</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Resource Modal --}}
    <div
        x-show="editResourceModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        style="display: none;"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-[#e7e2f7]"
            @click.outside="editResourceModal = false"
            x-show="editResourceModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <h3 class="text-base font-extrabold text-[#231a3d]">Edit Resource / Ruangan</h3>
                <button @click="editResourceModal = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Perbarui informasi fasilitas atau aset operasional.</p>
            <form method="POST" :action="`/owner/resources/${activeResource.id}`" class="mt-4 space-y-4" id="form-edit-resource">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Nama Resource / Room <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        x-model="activeResource.name"
                        required
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Tipe Resource <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            name="type"
                            x-model="activeResource.type"
                            required
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Kapasitas (Orang)</label>
                        <input
                            type="number"
                            name="capacity"
                            x-model="activeResource.capacity"
                            min="1"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Lokasi / Detail</label>
                    <input
                        type="text"
                        name="location"
                        x-model="activeResource.location"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Status</label>
                    <select
                        name="is_active"
                        x-model="activeResource.is_active"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Layanan Terkait</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]/60 p-3">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-[#231a3d] font-semibold cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" :checked="activeResource.service_ids && activeResource.service_ids.includes({{ $svc->id }})" @change="toggleResourceService({{ $svc->id }})" class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#e7e2f7]">
                    <button type="button" @click="editResourceModal = false" class="craft-btn rounded-xl px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition">Batal</button>
                    <button type="submit" class="craft-btn rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
