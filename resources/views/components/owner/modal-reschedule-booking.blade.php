{{-- Owner Direct Reschedule Booking Modal Component --}}
<div
    id="modal-owner-reschedule"
    x-data="{
        buka: false,
        sedangMemuat: false,
        sedangKirim: false,
        booking: null,
        selectedDate: '',
        selectedScheduleId: null,
        alasan: '',
        slots: [],
        pesanError: '',
        minDate: new Date().toISOString().split('T')[0],

        bukaModal(data) {
            this.booking = data.booking || data;
            this.pesanError = '';
            this.alasan = 'Permintaan langsung customer walk-in di tempat';
            
            // Inisialisasi tanggal dengan tanggal booking saat ini jika belum lewat, atau hari ini
            const tglBooking = this.booking.tanggalbooking ? String(this.booking.tanggalbooking).split('T')[0].split(' ')[0] : '';
            if (tglBooking && tglBooking >= this.minDate) {
                this.selectedDate = tglBooking;
            } else {
                this.selectedDate = this.minDate;
            }
            
            this.selectedScheduleId = this.booking.idschedule || null;
            this.buka = true;
            this.muatSlot();
        },

        async muatSlot() {
            if (!this.booking || !this.selectedDate) return;
            this.sedangMemuat = true;
            this.pesanError = '';
            this.slots = [];

            try {
                const res = await fetch(`/owner/bookings/${this.booking.id}/available-slots?tanggal=${this.selectedDate}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.slots = data.slots || [];
                    // Jika slot saat ini berada di tanggal ini, pastikan terpilih secara default
                    const adaSlotTerpilih = this.slots.some(s => s.id === this.selectedScheduleId);
                    if (!adaSlotTerpilih) {
                        this.selectedScheduleId = null;
                    }
                } else {
                    this.pesanError = data.message || 'Gagal memuat slot jadwal.';
                }
            } catch (err) {
                this.pesanError = 'Terjadi kendala saat memuat slot jadwal.';
            } finally {
                this.sedangMemuat = false;
            }
        },

        formatTanggal(tgl) {
            if (!tgl) return '-';
            try {
                const d = new Date(tgl);
                return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            } catch(e) {
                return tgl;
            }
        }
    }"
    @open-owner-reschedule.window="bukaModal($event.detail)"
    @keydown.escape.window="buka = false"
    x-cloak
>
    {{-- Overlay --}}
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[80] bg-black/40 backdrop-blur-sm"
        @click="buka = false"
    ></div>

    {{-- Modal Dialog --}}
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="fixed inset-0 z-[90] flex items-center justify-center p-3 sm:p-4"
    >
        <div class="w-full max-w-lg rounded-2xl border border-bq-border bg-bq-surface shadow-2xl overflow-hidden flex flex-col max-h-[90vh]" @click.stop>
            
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-bq-border px-4 sm:px-6 py-4 bg-[#fbfaff]">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-bq-text">Ubah Jadwal Reservasi</h2>
                        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-[10px] font-bold text-indigo-800" x-text="'#' + (booking?.booking_code || booking?.id || '')"></span>
                    </div>
                    <p class="text-xs text-bq-text-muted mt-0.5">Penyesuaian jadwal langsung khusus Owner tanpa perlu token pelanggan.</p>
                </div>
                <button @click="buka = false" class="rounded-xl p-1.5 text-bq-text-subtle transition-colors hover:bg-slate-100 hover:text-bq-text cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body Scrollable --}}
            <form :action="'/owner/bookings/' + (booking?.id || '') + '/reschedule'" method="POST" @submit="sedangKirim = true" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4.5">
                @csrf

                {{-- Alert Error --}}
                <template x-if="pesanError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-800 flex items-center gap-2">
                        <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="pesanError"></span>
                    </div>
                </template>

                {{-- Info Pelanggan & Jadwal Saat Ini --}}
                <div class="rounded-xl border border-indigo-100 bg-[#f8f6ff] p-3.5 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-bq-text-muted font-medium">Pelanggan:</span>
                        <span class="font-bold text-bq-text" x-text="booking?.namapelanggan"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-bq-text-muted font-medium">Layanan:</span>
                        <span class="font-bold text-bq-primary" x-text="booking?.layanan?.namalayanan || 'Layanan Utama'"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-indigo-200/50">
                        <span class="text-bq-text-muted font-medium">Jadwal Lama:</span>
                        <span class="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">
                            <span x-text="formatTanggal(booking?.tanggalbooking)"></span> &bull; <span x-text="(booking?.jam || '').substring(0, 5) + ' WIB'"></span>
                        </span>
                    </div>
                </div>

                {{-- Input Tanggal Baru --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-bq-text">
                        Pilih Tanggal Baru <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="date"
                        x-model="selectedDate"
                        :min="minDate"
                        @change="muatSlot()"
                        class="w-full rounded-xl border border-bq-border bg-white px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-bq-text shadow-2xs focus:border-bq-primary focus:ring-1 focus:ring-bq-primary"
                        required
                    />
                </div>

                {{-- Pemilih Slot Waktu --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-bq-text">
                            Pilih Slot Jam Baru <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] text-bq-text-muted" x-show="!sedangMemuat && slots.length > 0">
                            <span class="font-bold text-bq-primary" x-text="slots.filter(s => s.is_available).length"></span> slot tersedia
                        </span>
                    </div>

                    {{-- Loading Indicator --}}
                    <div x-show="sedangMemuat" class="py-8 text-center space-y-2">
                        <svg class="h-6 w-6 animate-spin mx-auto text-bq-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <p class="text-xs text-bq-text-muted">Memuat slot jadwal tersedia...</p>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!sedangMemuat && slots.length === 0" class="rounded-xl border border-dashed border-bq-border bg-slate-50/70 p-5 text-center space-y-1.5">
                        <p class="text-xs font-semibold text-bq-text-muted">Tidak ada slot jadwal operasional pada tanggal ini.</p>
                        <p class="text-[11px] text-[#6e6584]">Silakan pilih tanggal lain di atas atau buat slot baru di menu Schedule.</p>
                    </div>

                    {{-- Slots Grid --}}
                    <div x-show="!sedangMemuat && slots.length > 0" class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1">
                        <template x-for="slot in slots" :key="slot.id">
                            <label
                                :class="{
                                    'opacity-40 cursor-not-allowed bg-slate-100 border-slate-200 text-slate-400': !slot.is_available,
                                    'border-bq-primary bg-[#f3effe] text-bq-primary ring-2 ring-bq-primary/20': selectedScheduleId === slot.id && slot.is_available,
                                    'border-bq-border bg-white text-bq-text hover:border-indigo-200 hover:bg-slate-50': selectedScheduleId !== slot.id && slot.is_available
                                }"
                                class="rounded-xl border p-2.5 transition-all flex flex-col justify-between gap-1 relative cursor-pointer shadow-2xs"
                            >
                                <input
                                    type="radio"
                                    name="schedule_id"
                                    :value="slot.id"
                                    x-model="selectedScheduleId"
                                    :disabled="!slot.is_available"
                                    class="sr-only"
                                />
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black" x-text="slot.formatted_time"></span>
                                    <template x-if="slot.is_current">
                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800">Saat ini</span>
                                    </template>
                                    <template x-if="!slot.is_available && !slot.is_current">
                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-slate-200 text-slate-600">Penuh</span>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between text-[11px] font-semibold text-bq-text-muted">
                                    <span x-text="slot.formatted_price"></span>
                                    <template x-if="slot.is_available">
                                        <span class="text-emerald-600 text-[10px] font-bold">✓ Pilih</span>
                                    </template>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Input Alasan Reschedule --}}
                <div class="space-y-1.5 pt-1">
                    <label class="block text-xs font-bold text-bq-text">
                        Catatan / Alasan Perubahan <span class="text-bq-text-muted font-normal text-[11px]">(opsional)</span>
                    </label>
                    <input
                        type="text"
                        name="alasan"
                        x-model="alasan"
                        placeholder="Contoh: Permintaan langsung customer walk-in di kasir"
                        class="w-full rounded-xl border border-bq-border bg-white px-3.5 py-2.5 text-xs sm:text-sm text-bq-text shadow-2xs focus:border-bq-primary focus:ring-1 focus:ring-bq-primary"
                    />
                </div>

                {{-- Footer Aksi --}}
                <div class="pt-3 border-t border-bq-border flex items-center justify-between gap-2">
                    <button
                        type="button"
                        @click="buka = false"
                        class="rounded-xl border border-bq-border bg-white px-4 py-2.5 text-xs font-bold text-bq-text hover:bg-slate-50 transition cursor-pointer shadow-2xs"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="!selectedScheduleId || sedangKirim || sedangMemuat"
                        :class="(!selectedScheduleId || sedangKirim || sedangMemuat) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-bq-primary-hover active:scale-95 cursor-pointer'"
                        class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-5 py-2.5 text-xs font-bold text-white shadow-sm shadow-bq-primary/25 transition"
                    >
                        <svg x-show="sedangKirim" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Simpan Perubahan Jadwal</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
