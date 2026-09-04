@extends('layouts.owner-layout')

@section('title', 'Staff & Resources')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    tab: 'staff',
    searchStaff: '',
    searchResource: '',
    addStaffModal: false,
    addResourceModal: false,
    notification: '',
    staffList: [
        { id: 1, name: 'Budi Santoso', role: 'Lead Specialist', services: ['Consultation', 'Treatment Plus'], availability: 'Mon - Fri (09:00 - 17:00)', is_active: true },
        { id: 2, name: 'Siti Rahma', role: 'Senior Therapist', services: ['Standard Service', 'Hair Spa'], availability: 'Tue - Sat (10:00 - 18:00)', is_active: true },
        { id: 3, name: 'Ahmad Fauzi', role: 'Junior Assistant', services: ['Express Service'], availability: 'Weekend Only', is_active: false }
    ],
    resourceList: [
        { id: 1, name: 'VIP Room 01', type: 'Private Suite', services: ['All VIP Services'], availability: 'Ready & Available', capacity: '1 - 2 Guests', is_active: true },
        { id: 2, name: 'Studio Main Court', type: 'Activity Hall', services: ['Group Sessions', 'Rental'], availability: 'In Use / Scheduled', capacity: '10 Persons', is_active: true },
        { id: 3, name: 'Styling Station #3', type: 'Dedicated Chair', services: ['Standard Service'], availability: 'Maintenance', capacity: '1 Person', is_active: false }
    ],
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
    },
    toggleStaff(s) {
        s.is_active = !s.is_active;
        this.showToast('Status ' + s.name + ' diperbarui.');
    },
    toggleResource(r) {
        r.is_active = !r.is_active;
        this.showToast('Status ' + r.name + ' diperbarui.');
    },
    filteredStaff() {
        if (!this.searchStaff.trim()) return this.staffList;
        const q = this.searchStaff.toLowerCase();
        return this.staffList.filter(s => s.name.toLowerCase().includes(q) || s.role.toLowerCase().includes(q));
    },
    filteredResources() {
        if (!this.searchResource.trim()) return this.resourceList;
        const q = this.searchResource.toLowerCase();
        return this.resourceList.filter(r => r.name.toLowerCase().includes(q) || r.type.toLowerCase().includes(q));
    }
}">

    {{-- Toast Notification --}}
    <div x-show="notification"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 rounded-xl bg-slate-900 text-white px-4 py-3 shadow-xl text-xs font-medium flex items-center gap-2"
         style="display: none;">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
        <span x-text="notification"></span>
    </div>

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Staff &amp; Resources</h1>
            <p class="text-sm text-bq-text-muted mt-1">Kelola tim staf profesional dan aset fisik operasional untuk melayani customer Anda.</p>
        </div>
        <div class="flex items-center gap-2">
            <template x-if="tab === 'staff'">
                <button type="button" @click="addStaffModal = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    + Tambah Staff
                </button>
            </template>
            <template x-if="tab === 'resources'">
                <button type="button" @click="addResourceModal = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition">
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
        <button type="button"
            @click="tab = 'staff'"
            class="pb-3 text-sm font-bold border-b-2 transition flex items-center gap-2"
            :class="tab === 'staff' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted hover:text-bq-text'">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Staff Team
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600" x-text="staffList.length"></span>
        </button>
        <button type="button"
            @click="tab = 'resources'"
            class="pb-3 text-sm font-bold border-b-2 transition flex items-center gap-2"
            :class="tab === 'resources' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted hover:text-bq-text'">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Physical Resources &amp; Rooms
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600" x-text="resourceList.length"></span>
        </button>
    </div>

    {{-- ── TAB 1: STAFF ── --}}
    <div x-show="tab === 'staff'" class="space-y-4">
        {{-- Search staff --}}
        <div class="flex items-center justify-between gap-3">
            <div class="relative w-full sm:max-w-xs">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchStaff" placeholder="Cari nama atau role..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2 pl-9 pr-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
            </div>
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
                        <template x-for="staff in filteredStaff()" :key="staff.id">
                            <tr class="hover:bg-bq-background/40 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-700 text-xs">
                                            <span x-text="staff.name.charAt(0)"></span>
                                        </div>
                                        <span class="font-semibold text-bq-text text-sm" x-text="staff.name"></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-bq-text font-medium" x-text="staff.role"></td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="svc in staff.services" :key="svc">
                                            <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium" x-text="svc"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted font-mono" x-text="staff.availability"></td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <button type="button" @click="toggleStaff(staff)"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition"
                                        :class="staff.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-500'">
                                        <span x-text="staff.is_active ? 'Active' : 'Inactive'"></span>
                                    </button>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="showToast('Edit staff: ' + staff.name)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button" @click="staffList = staffList.filter(s => s.id !== staff.id); showToast('Staff dihapus.');" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Delete">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Empty Staff --}}
        <div x-show="filteredStaff().length === 0" class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-10 text-center" style="display: none;">
            <p class="font-bold text-bq-text text-sm">Belum ada anggota staf</p>
            <p class="text-xs text-bq-text-muted mt-1">Tambahkan terapis atau staf Anda agar customer bisa memilih staf saat booking.</p>
            <button type="button" @click="addStaffModal = true" class="mt-4 rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white">
                + Tambah Staff Baru
            </button>
        </div>
    </div>

    {{-- ── TAB 2: RESOURCES ── --}}
    <div x-show="tab === 'resources'" style="display: none;" class="space-y-4">
        {{-- Search resource --}}
        <div class="flex items-center justify-between gap-3">
            <div class="relative w-full sm:max-w-xs">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchResource" placeholder="Cari resource atau ruangan..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2 pl-9 pr-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
            </div>
        </div>

        {{-- Resource Table --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                            <th class="px-5 py-3.5">Resource Name</th>
                            <th class="px-5 py-3.5">Type &amp; Capacity</th>
                            <th class="px-5 py-3.5">Assigned Services</th>
                            <th class="px-5 py-3.5">Current Availability</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bq-border">
                        <template x-for="res in filteredResources()" :key="res.id">
                            <tr class="hover:bg-bq-background/40 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 font-bold text-purple-700 text-xs">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <span class="font-semibold text-bq-text text-sm" x-text="res.name"></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-medium text-bq-text" x-text="res.type"></span>
                                    <span class="text-bq-text-subtle block text-[11px]" x-text="res.capacity"></span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="svc in res.services" :key="svc">
                                            <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium" x-text="svc"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium"
                                          :class="res.availability.includes('Ready') ? 'text-emerald-700' : 'text-amber-700'">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="res.availability.includes('Ready') ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        <span x-text="res.availability"></span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <button type="button" @click="toggleResource(res)"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition"
                                        :class="res.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-500'">
                                        <span x-text="res.is_active ? 'Active' : 'Inactive'"></span>
                                    </button>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="showToast('Edit resource: ' + res.name)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button" @click="resourceList = resourceList.filter(r => r.id !== res.id); showToast('Resource dihapus.');" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Delete">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Empty Resource --}}
        <div x-show="filteredResources().length === 0" class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-10 text-center" style="display: none;">
            <p class="font-bold text-bq-text text-sm">Belum ada aset fisik / ruangan</p>
            <p class="text-xs text-bq-text-muted mt-1">Tambahkan ruangan atau kursi agar jadwal booking tidak bentrok.</p>
            <button type="button" @click="addResourceModal = true" class="mt-4 rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white">
                + Tambah Resource Baru
            </button>
        </div>
    </div>

    {{-- Add Staff Modal --}}
    <div x-show="addStaffModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addStaffModal = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Staff Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Daftarkan terapis, instruktur, atau staf layanan.</p>
            <form @submit.prevent="staffList.push({ id: Date.now(), name: $refs.stfName.value, role: $refs.stfRole.value, services: ['General'], availability: 'Standard Hours', is_active: true }); addStaffModal = false; showToast('Staff berhasil ditambahkan!')" class="mt-4 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Lengkap</label>
                    <input type="text" x-ref="stfName" required placeholder="Contoh: Rian Pratama" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Role / Jabatan</label>
                    <input type="text" x-ref="stfRole" required placeholder="Contoh: Senior Stylist" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addStaffModal = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Staff</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Resource Modal --}}
    <div x-show="addResourceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addResourceModal = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Resource / Ruangan Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Daftarkan aset fisik seperti lapangan, ruangan, atau kursi.</p>
            <form @submit.prevent="resourceList.push({ id: Date.now(), name: $refs.resName.value, type: $refs.resType.value, services: ['General'], availability: 'Ready & Available', capacity: '1 Unit', is_active: true }); addResourceModal = false; showToast('Resource berhasil ditambahkan!')" class="mt-4 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Resource / Room</label>
                    <input type="text" x-ref="resName" required placeholder="Contoh: VIP Studio 02, Court B" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Tipe Resource</label>
                    <input type="text" x-ref="resType" required placeholder="Contoh: Badminton Court, Private Room" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addResourceModal = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Resource</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
