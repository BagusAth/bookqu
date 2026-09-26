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
