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
