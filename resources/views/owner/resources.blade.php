@extends('layouts.owner-layout')

@section('title', 'Resource')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: @js($search),
    addResourceModal: false,
    editResourceModal: false,
    activeResource: { id: null, name: '', type: '', capacity: 1, location: '', is_active: 1, service_ids: [] },
    openEditResource(res) {
        this.activeResource = { ...res, service_ids: Array.isArray(res.service_ids) ? [...res.service_ids] : [] };
        this.editResourceModal = true;
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



    <h1 class="text-2xl font-bold">Resource</h1>
    @include('owner.partials.staff-resources.resource-section')


    {{-- ── Resource Modals (Add & Edit) ── --}}
    @include('owner.partials.staff-resources.resource-modals')

</div>
@endsection
