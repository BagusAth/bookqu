    {{-- ── Centered Booking Detail Modal ── --}}
    <div x-show="detailOpen"
         class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="detailOpen = false">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="detailOpen = false"></div>

        {{-- Centered Dialog Card --}}
        <div x-show="detailOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] z-10 border border-[#e7e2f7]">

            {{-- Modal Header --}}
            <div class="p-4 sm:p-5 border-b border-[#e7e2f7] flex items-center justify-between bg-[#fbfaff]">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#b499ff]/30 shadow-2xs">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-extrabold text-[#231a3d]">Detail Reservasi</h3>
                            <span class="font-mono text-xs px-2 py-0.5 rounded-lg bg-[#f3effe] text-[#382186] font-bold border border-[#b499ff]/30" x-text="'#' + (activeBooking ? activeBooking.code : '')"></span>
                        </div>
                        <p class="text-[11px] text-[#6e6584]">Informasi lengkap pesanan dan aksi reservasi pelanggan.</p>
                    </div>
                </div>
                <button type="button" @click="detailOpen = false" class="rounded-xl p-2 text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition cursor-pointer" aria-label="Close modal">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                <template x-if="activeBooking">
                    <div class="space-y-4">
                        {{-- Status Banner --}}
                        <div class="flex items-center justify-between p-3.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]">
                            <div>
                                <p class="text-[10px] uppercase font-bold text-[#6e6584] tracking-wider">Status Booking</p>
                                <p class="text-xs sm:text-sm font-extrabold uppercase mt-0.5"
                                   :class="{
                                       'text-emerald-700': activeBooking.status === 'completed' || activeBooking.status === 'paid',
                                       'text-amber-700': activeBooking.status === 'pending',
                                       'text-rose-700': activeBooking.status === 'cancelled'
                                   }"
                                   x-text="activeBooking.status === 'paid' ? 'Confirmed' : activeBooking.status"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase font-bold text-[#6e6584] tracking-wider">Status Pembayaran</p>
                                <p class="text-xs sm:text-sm font-extrabold uppercase mt-0.5"
                                   :class="activeBooking.payment_status === 'sukses' ? 'text-emerald-700' : 'text-amber-700'"
                                   x-text="activeBooking.payment_status === 'sukses' ? 'Paid' : activeBooking.payment_status"></p>
                            </div>
                        </div>

                        {{-- Customer Card --}}
                        <div class="rounded-2xl border border-[#e7e2f7] p-4 space-y-2.5 bg-white shadow-2xs">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-[#6e6584] flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Data Pelanggan
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Nama Pelanggan:</span>
                                    <span class="font-bold text-[#231a3d]" x-text="activeBooking.name"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[#6e6584]">Nomor WhatsApp:</span>
                                    <a :href="'https://wa.me/' + (activeBooking.phone ? activeBooking.phone.replace(/[^0-9]/g, '') : '')" target="_blank" class="font-mono font-bold text-emerald-700 hover:underline flex items-center gap-1">
                                        <span x-text="activeBooking.phone || '-'"></span>
                                        <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1 py-0.2 rounded font-bold">Chat WA</span>
                                    </a>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Email:</span>
                                    <span class="text-[#231a3d] font-medium" x-text="activeBooking.email || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Catatan Pelanggan:</span>
                                    <span class="text-[#231a3d] italic text-right max-w-[200px]" x-text="activeBooking.notes || 'Tidak ada catatan'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Service & Schedule Card --}}
                        <div class="rounded-2xl border border-[#e7e2f7] p-4 space-y-2.5 bg-white shadow-2xs">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-[#6e6584] flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Layanan &amp; Jadwal
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Layanan:</span>
                                    <span class="font-bold text-[#231a3d]" x-text="activeBooking.service"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Tanggal:</span>
                                    <span class="font-bold text-[#231a3d]" x-text="activeBooking.date"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Waktu / Jam:</span>
                                    <span class="font-mono font-bold text-[#231a3d]" x-text="activeBooking.time"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Staff Ditugaskan:</span>
                                    <span class="font-medium text-[#231a3d]" x-text="activeBooking.staff || 'General Staff'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Fasilitas / Resource:</span>
                                    <span class="font-medium text-[#231a3d]" x-text="activeBooking.resource || 'General Facility'"></span>
                                </div>
                                <template x-if="activeBooking.rescheduled_from_date">
                                    <div class="mt-2 rounded-xl bg-amber-50 p-2.5 border border-amber-200 text-xs text-amber-800">
                                        <span class="font-semibold">Reschedule History:</span> Dipindahkan dari jadwal sebelumnya pada <span class="font-mono" x-text="activeBooking.rescheduled_from_date"></span> pukul <span class="font-mono" x-text="activeBooking.rescheduled_from_time || '-'"></span>.
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Payment Summary Card --}}
                        <div class="rounded-2xl border border-[#e7e2f7] p-4 space-y-2.5 bg-[#f8f6ff]">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-[#6e6584] flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Rincian Biaya &amp; Order
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-[#6e6584]">Total Biaya:</span>
                                    <span class="font-black text-[#382186] text-base" x-text="activeBooking.formatted_price"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#6e6584]">Order ID:</span>
                                    <span class="font-mono text-xs text-[#6e6584]" x-text="activeBooking.order_id"></span>
                                </div>
                                <template x-if="activeBooking.manage_url">
                                    <div class="pt-2 border-t border-[#e7e2f7] flex items-center justify-between">
                                        <span class="text-xs text-[#6e6584]">Link Mandiri Pelanggan:</span>
                                        <a :href="activeBooking.manage_url" target="_blank" class="text-xs font-bold text-[#382186] hover:underline flex items-center gap-1">
                                            <span>Buka Halaman Manage</span>
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 sm:p-5 border-t border-[#e7e2f7] bg-[#fbfaff] flex items-center justify-between gap-2">
                <button type="button" @click="detailOpen = false" class="px-4 py-2 rounded-xl border border-[#e7e2f7] bg-white text-xs font-bold text-[#231a3d] hover:bg-[#f7f7fa] transition cursor-pointer shrink-0 shadow-2xs">
                    Tutup
                </button>
                <template x-if="activeBooking && (activeBooking.status === 'paid' || activeBooking.status === 'pending')">
                        {{-- Tombol Ubah Jadwal (Reschedule) Khusus Owner --}}
                        <button
                            type="button"
                            @click="detailOpen = false; $dispatch('open-owner-reschedule', { booking: activeBooking })"
                            class="craft-btn px-3.5 py-2 rounded-xl border border-indigo-200 text-indigo-700 bg-indigo-50 text-xs font-bold hover:bg-indigo-100 transition cursor-pointer flex items-center gap-1.5 shadow-2xs"
                            title="Ubah jadwal / slot reservasi"
                        >
                            <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Ubah Jadwal</span>
                        </button>

                        <template x-if="activeBooking.status === 'pending'">
                            <form method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="paid">
                                <button type="submit" class="craft-btn px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                    ✓ Konfirmasi Lunas
                                </button>
                            </form>
                        </template>
                        <template x-if="activeBooking.status === 'paid'">
                            <form method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="craft-btn px-3.5 py-2 rounded-xl bg-[#382186] hover:bg-[#2d1a6d] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                    ✓ Selesai
                                </button>
                            </form>
                        </template>
                        <form :id="'form-drawer-cancel-' + activeBooking.id" method="POST" :action="'/owner/bookings/' + activeBooking.id + '/status'">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="button"
                                class="craft-btn px-3.5 py-2 rounded-xl border border-rose-200 text-rose-700 bg-rose-50 text-xs font-bold hover:bg-rose-100 transition cursor-pointer"
                                @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan booking ini?', formId: 'form-drawer-cancel-' + activeBooking.id })">
                                Batalkan
                            </button>
                        </form>
                    </div>
                </template>
            </div>

        </div>
    </div>

