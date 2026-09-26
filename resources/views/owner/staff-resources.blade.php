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

    {{-- ── Header & Tabs ── --}}
    @include('owner.partials.staff-resources.header')

    {{-- ── TAB 1: STAFF ── --}}
    @include('owner.partials.staff-resources.staff-section')

    {{-- ── TAB 2: RESOURCES ── --}}
    @include('owner.partials.staff-resources.resource-section')

    {{-- ── Staff Modals (Add & Edit) ── --}}
    @include('owner.partials.staff-resources.staff-modals')

    {{-- ── Resource Modals (Add & Edit) ── --}}
    @include('owner.partials.staff-resources.resource-modals')

</div>
@endsection
