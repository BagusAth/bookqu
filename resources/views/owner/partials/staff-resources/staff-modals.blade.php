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
