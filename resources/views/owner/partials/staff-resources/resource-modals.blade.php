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
