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
