    {{-- Detail Modal Drawer (All 10 required fields, responsive bottom sheet on mobile) --}}
    <div
        id="calendar-detail-modal"
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        @click.self="modalOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-lg rounded-t-3xl sm:rounded-3xl bg-white p-4 sm:p-6 shadow-2xl border border-[#e7e2f7] space-y-4 max-h-[90vh] sm:max-h-[85vh] overflow-y-auto transform transition-all duration-200"
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 sm:scale-95"
        >
            {{-- Mobile Drag Handle --}}
            <div class="sm:hidden flex justify-center -mt-1 pb-1">
                <div class="w-10 h-1 rounded-full bg-[#e7e2f7]"></div>
            </div>

            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3 sm:pb-3.5">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#231a3d]">Detail Booking Calendar</h3>
                        <span class="inline-block mt-0.5 rounded-md bg-[#e7e2f7] px-2 py-0.5 text-[11px] sm:text-xs font-mono font-bold text-[#382186]" x-text="selectedSlot ? selectedSlot.booking_id : ''"></span>
                    </div>
                </div>
                <button
                    @click="modalOpen = false"
                    class="rounded-xl p-1.5 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa] transition active:scale-95"
                    aria-label="Tutup"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedSlot">
                <div class="space-y-3 sm:space-y-3.5 text-sm">
                    {{-- 1. Service & Amount Banner --}}
                    <div class="rounded-2xl bg-[#f7f7fa] p-3.5 sm:p-4 border border-[#e7e2f7] flex items-center justify-between">
                        <div>
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-extrabold">Layanan / Service</p>
                            <p class="text-sm sm:text-base font-black text-[#231a3d] mt-0.5" x-text="selectedSlot.service"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-extrabold">Amount / Tarif</p>
                            <p class="text-base sm:text-lg font-black text-[#382186] mt-0.5" x-text="selectedSlot.amount"></p>
                        </div>
                    </div>

                    {{-- 2. Date & Time --}}
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Tanggal / Date</p>
                            <p class="text-xs sm:text-sm font-extrabold text-[#231a3d] mt-0.5" x-text="selectedSlot.date"></p>
                        </div>
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Waktu / Time</p>
                            <p class="text-xs sm:text-sm font-black text-[#382186] mt-0.5" x-text="selectedSlot.time"></p>
                        </div>
                    </div>

                    {{-- 3. Customer Info --}}
                    <div class="rounded-xl border border-[#e7e2f7] bg-white p-3 sm:p-3.5 space-y-1">
                        <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Informasi Pelanggan</p>
                        <p class="font-black text-[#231a3d] text-xs sm:text-sm" x-text="selectedSlot.customer"></p>
                        <div class="flex flex-wrap items-center gap-2.5 pt-0.5 text-xs text-[#6e6584] font-medium">
                            <span class="inline-flex items-center gap-1">📞 <span x-text="selectedSlot.phone"></span></span>
                            <span class="inline-flex items-center gap-1">✉️ <span x-text="selectedSlot.email"></span></span>
                        </div>
                    </div>

                    {{-- 4. Status Separation: Booking Status & Payment Status --}}
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Booking Status</p>
                            <div class="mt-1 sm:mt-1.5">
                                <span
                                    class="inline-flex rounded-full px-2 sm:px-2.5 py-0.5 text-[10px] sm:text-xs font-black uppercase border"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': selectedSlot.booking_status === 'Confirmed',
                                        'bg-[#fff8eb] text-[#875000] border-[#ffb84d]': selectedSlot.booking_status === 'Pending',
                                        'bg-sky-100 text-sky-800 border-sky-200': selectedSlot.booking_status === 'Completed',
                                        'bg-rose-100 text-rose-800 border-rose-200': selectedSlot.booking_status === 'Cancelled',
                                        'bg-[#f3effe] text-[#382186] border-[#b499ff]': selectedSlot.booking_status === 'Available',
                                        'bg-[#f7f7fa] text-[#231a3d] border-[#e7e2f7]': !['Confirmed', 'Pending', 'Completed', 'Cancelled', 'Available'].includes(selectedSlot.booking_status)
                                    }"
                                    x-text="selectedSlot.booking_status"
                                ></span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-[#e7e2f7] bg-white p-2.5 sm:p-3">
                            <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Payment Status</p>
                            <div class="mt-1 sm:mt-1.5">
                                <span
                                    class="inline-flex rounded-full px-2 sm:px-2.5 py-0.5 text-[10px] sm:text-xs font-black uppercase border"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': selectedSlot.payment_status.toLowerCase() === 'sukses',
                                        'bg-[#fff8eb] text-[#875000] border-[#ffb84d]': selectedSlot.payment_status.toLowerCase() === 'pending',
                                        'bg-rose-100 text-rose-800 border-rose-200': ['gagal', 'failed', 'expired'].includes(selectedSlot.payment_status.toLowerCase()),
                                        'bg-[#f7f7fa] text-[#6e6584] border-[#e7e2f7]': !['sukses', 'pending', 'gagal', 'failed', 'expired'].includes(selectedSlot.payment_status.toLowerCase())
                                    }"
                                    x-text="selectedSlot.payment_status"
                                ></span>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Notes --}}
                    <div class="rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] p-2.5 sm:p-3">
                        <p class="text-[10px] sm:text-xs text-[#6e6584] uppercase tracking-wider font-bold">Catatan / Notes</p>
                        <p class="text-xs text-[#231a3d] mt-1 italic" x-text="selectedSlot.notes"></p>
                    </div>

                    {{-- Walk-in Invitation Banner when available slot is selected and walkinMode is false --}}
                    <template x-if="selectedSlot && !selectedSlot.is_booking && selectedSlot.raw_status === 'available' && !walkinMode">
                        <div class="mt-3 rounded-2xl border border-dashed border-[#b499ff] bg-[#f3effe]/60 p-3.5 text-center space-y-2">
                            <p class="text-xs font-bold text-[#382186]">Slot ini masih kosong &amp; siap diisi reservasi walk-in tamu di tempat.</p>
                            <button
                                type="button"
                                @click="walkinMode = true"
                                class="craft-btn w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Buka Form Walk-in Tamu</span>
                            </button>
                        </div>
                    </template>

                    {{-- Walk-in Booking Form (When Available slot is selected and walkinMode is active) --}}
                    <div x-show="walkinMode" class="mt-4 pt-4 border-t border-[#e7e2f7] space-y-3 rounded-2xl bg-[#f3effe]/40 p-3.5 sm:p-4 border border-[#b499ff]/50">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-[#382186] uppercase tracking-wider">Form Reservasi Walk-in Langsung</h4>
                            <span class="text-[10px] text-[#6e6584] font-semibold">Tamu Datang Langsung</span>
                        </div>
                        <form method="POST" action="{{ route('owner.bookings.walkin') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="idschedule" :value="selectedSlot.raw_schedule_id">
                            <div>
                                <label class="block text-xs font-bold text-[#231a3d]">Nama Pelanggan <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    name="namapelanggan"
                                    required
                                    placeholder="Nama tamu walk-in..."
                                    class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                >
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-bold text-[#231a3d]">No. WhatsApp / HP <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        name="nomorhp"
                                        required
                                        placeholder="08..."
                                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[#231a3d]">Email (Opsional)</label>
                                    <input
                                        type="email"
                                        name="email"
                                        placeholder="email@tamu.com"
                                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#231a3d]">Metode Pembayaran</label>
                                <select
                                    name="metode"
                                    class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                >
                                    <option value="cash">Tunai (Cash di Tempat)</option>
                                    <option value="transfer">Transfer Bank Manual</option>
                                    <option value="qris">QRIS Langsung</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#231a3d]">Catatan Khusus (Opsional)</label>
                                <input
                                    type="text"
                                    name="catatan"
                                    placeholder="Catatan tamu atau permintaan khusus..."
                                    class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
                                >
                            </div>
                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    @click="walkinMode = false"
                                    class="craft-btn flex-1 sm:flex-none justify-center rounded-xl px-3.5 py-2 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs"
                                >
                                    Konfirmasi Walk-in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

            <div class="pt-3 border-t border-[#e7e2f7] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Quick Action for Confirmed Booking --}}
                    <template x-if="selectedSlot && selectedSlot.is_booking && selectedSlot.raw_status === 'paid'">
                        <div class="flex items-center gap-1.5 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="modalOpen = false; $dispatch('open-owner-reschedule', {
                                    id: selectedSlot.raw_booking_id,
                                    booking_code: selectedSlot.booking_id,
                                    namapelanggan: selectedSlot.customer,
                                    idschedule: selectedSlot.raw_schedule_id,
                                    tanggalbooking: selectedSlot.raw_date || selectedSlot.date,
                                    jam: selectedSlot.time,
                                    layanan: { namalayanan: selectedSlot.service }
                                })"
                                class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer flex items-center gap-1.5"
                                title="Ubah jadwal / slot reservasi ini"
                            >
                                <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Ubah Jadwal</span>
                            </button>
                            <form method="POST" :action="`/owner/bookings/${selectedSlot.raw_booking_id}/status`" class="flex-1 sm:flex-none inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer">
                                    ✓ Selesai
                                </button>
                            </form>
                            <form method="POST" :action="`/owner/bookings/${selectedSlot.raw_booking_id}/status`" :id="`form-calendar-cancel-${selectedSlot.raw_booking_id}`" class="flex-1 sm:flex-none inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button
                                    type="button"
                                    @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan booking ' + selectedSlot.booking_id + ' untuk ' + selectedSlot.customer + '?', formId: `form-calendar-cancel-${selectedSlot.raw_booking_id}`, confirmText: 'Ya, Batalkan' })"
                                    class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer"
                                >
                                    Batalkan
                                </button>
                            </form>
                        </div>
                    </template>

                    {{-- Quick Action for Pending Booking --}}
                    <template x-if="selectedSlot && selectedSlot.is_booking && selectedSlot.raw_status === 'pending'">
                        <div class="flex items-center gap-1.5 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="modalOpen = false; $dispatch('open-owner-reschedule', {
                                    id: selectedSlot.raw_booking_id,
                                    booking_code: selectedSlot.booking_id,
                                    namapelanggan: selectedSlot.customer,
                                    idschedule: selectedSlot.raw_schedule_id,
                                    tanggalbooking: selectedSlot.raw_date || selectedSlot.date,
                                    jam: selectedSlot.time,
                                    layanan: { namalayanan: selectedSlot.service }
                                })"
                                class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer flex items-center gap-1.5"
                                title="Ubah jadwal / slot reservasi ini"
                            >
                                <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Ubah Jadwal</span>
                            </button>
                            <form method="POST" :action="`/owner/bookings/${selectedSlot.raw_booking_id}/status`" :id="`form-calendar-cancel-${selectedSlot.raw_booking_id}`" class="w-full sm:w-auto inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button
                                    type="button"
                                    @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan pesanan pending ' + selectedSlot.booking_id + ' ini?', formId: `form-calendar-cancel-${selectedSlot.raw_booking_id}`, confirmText: 'Ya, Batalkan' })"
                                    class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer"
                                >
                                    Batalkan
                                </button>
                            </form>
                        </div>
                    </template>

                    {{-- Actions for Available Slot (Walk-in & Delete Slot) --}}
                    <template x-if="selectedSlot && !selectedSlot.is_booking && selectedSlot.raw_status === 'available' && !walkinMode">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="walkinMode = true"
                                class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#382186] hover:bg-[#2d1a6d] px-3.5 py-2 text-xs font-bold text-white shadow-xs cursor-pointer"
                            >
                                + Walk-in Booking
                            </button>
                            <form method="POST" :action="`/owner/schedule/slots/${selectedSlot.raw_schedule_id}`" :id="`form-delete-calendar-slot-${selectedSlot.raw_schedule_id}`" class="flex-1 sm:flex-none inline">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="button"
                                    @click="$dispatch('open-confirm', { title: 'Hapus Slot Jadwal?', message: 'Slot ketersediaan pada jam ' + selectedSlot.time + ' ini akan dihapus dari jadwal operasional. Lanjutkan?', formId: `form-delete-calendar-slot-${selectedSlot.raw_schedule_id}`, confirmText: 'Ya, Hapus Slot' })"
                                    class="craft-btn w-full sm:w-auto justify-center rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-3.5 py-2 text-xs font-bold shadow-2xs cursor-pointer"
                                    title="Hapus slot jadwal ini"
                                >
                                    Hapus Slot
                                </button>
                            </form>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="modalOpen = false"
                        class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#f7f7fa] border border-[#e7e2f7] px-4 py-2 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#e7e2f7] transition cursor-pointer"
                    >
                        Tutup
                    </button>
                    <a
                        href="{{ route('owner.bookings') }}"
                        class="craft-btn flex-1 sm:flex-none justify-center rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition cursor-pointer"
                    >
                        Lihat di Daftar Booking
                    </a>
                </div>
            </div>
        </div>
</div>
