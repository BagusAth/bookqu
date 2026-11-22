@php
    $rescheduleForm = $errors->any() && is_string(old('_form')) ? old('_form') : '';
    $reopenReschedule = preg_match('/^reschedule-([1-9][0-9]*)$/', $rescheduleForm, $match)
        ? route('owner.bookings.panel', (int) $match[1])
        : null;
    $oldReschedule = $reopenReschedule ? \Illuminate\Support\Arr::only(old(), ['tanggal', 'schedule_id', 'alasan']) : [];
@endphp
<div x-data="{
    booking: null,
    date: '',
    minDate: @js(today()->toDateString()),
    slots: [],
    selected: '',
    reason: '',
    conflict: null,
    errors: {},
    failed: '',
    loading: false,
    saving: false,
    requestNumber: 0,
    get selectedSlot() {
        return this.slots.find(slot => String(slot.id) === String(this.selected) && slot.is_available);
    },
    endpoint(template) {
        return template.replace('__ID__', encodeURIComponent(this.booking?.id ?? ''));
    },
    async restore(url) {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const payload = await response.json();
            const action = payload.actions.find(action => action.reschedule);
            if (action) this.start(action.reschedule, null, @js($oldReschedule));
        } catch {
            $dispatch('toast', { type: 'error', message: 'Detail booking tidak dapat dimuat.' });
        }
    },
    start(booking, trigger, old = {}) {
        this.booking = booking;
        this.date = old.tanggal || (booking.tanggalbooking >= this.minDate ? booking.tanggalbooking : this.minDate);
        this.reason = old.alasan || '';
        this.conflict = null;
        this.errors = {};
        this.saving = false;
        $dispatch('open-dialog', { id: 'modal-owner-reschedule', trigger });
        this.load(old.schedule_id);
    },
    async load(preferred = null) {
        const sequence = ++this.requestNumber;
        this.slots = [];
        this.selected = '';
        this.failed = '';
        this.loading = false;
        if (!this.booking || !this.date) return;
        this.loading = true;
        try {
            const url = new URL(this.endpoint(@js(route('owner.bookings.available-slots', ['booking' => '__ID__']))));
            url.searchParams.set('tanggal', this.date);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (sequence !== this.requestNumber) return;
            if (!response.ok || !payload.success) {
                this.failed = payload.message || 'Slot tidak dapat dimuat.';
                return;
            }
            this.slots = payload.slots || [];
            this.selected = this.slots.find(slot => slot.is_available && String(slot.id) === String(preferred))?.id ?? '';
        } catch {
            if (sequence === this.requestNumber) this.failed = 'Slot tidak dapat dimuat. Coba lagi.';
        } finally {
            if (sequence === this.requestNumber) this.loading = false;
        }
    },
    async submit(form) {
        if (this.saving) return;
        const attempted = this.selectedSlot;
        this.saving = true;
        this.conflict = null;
        this.errors = {};
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            if (response.status === 419 || response.status === 401) return;
            if (response.status >= 500) throw new Error();
            const data = await response.json();
            if (data.code === 'SLOT_TAKEN') {
                await this.load();
                const minutes = time => Number(time.slice(0, 2)) * 60 + Number(time.slice(3, 5));
                const start = minutes(attempted.jam_mulai);
                const alternatives = this.slots.filter(slot => slot.is_available && !slot.is_current && slot.id !== attempted.id)
                    .sort((a, b) => Math.abs(minutes(a.jam_mulai) - start) - Math.abs(minutes(b.jam_mulai) - start)).slice(0, 2);
                this.conflict = { start: attempted.jam_mulai, alternatives };
                return;
            }
            if (!response.ok || !data.success) {
                this.errors = data.errors || { error: [data.message || 'Jadwal tidak dapat disimpan.'] };
                return;
            }
            $dispatch('close-dialog', { id: 'modal-owner-reschedule' });
            $dispatch('toast', { type: 'success', message: data.message });
            $dispatch('open-booking-panel', { url: this.endpoint(@js(route('owner.bookings.panel', ['booking' => '__ID__']))) });
        } catch {
            $dispatch('toast', { type: 'error', message: 'Status penyimpanan belum dapat diperiksa. Isian Anda masih ada.', action: () => this.$refs.rescheduleSubmit.focus() });
        } finally {
            this.saving = false;
        }
    },
    formatDifference(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency', currency: 'IDR', maximumFractionDigits: 0, signDisplay: 'exceptZero'
        }).format(value);
    }
}"
    @open-owner-reschedule.window="start($event.detail.booking, $event.detail.trigger)"
    @dialog-closed="if ($event.detail.id === 'modal-owner-reschedule') requestNumber++"
    @if ($reopenReschedule) x-init="restore(@js($reopenReschedule))" @endif
>
    <x-owner.dialog id="modal-owner-reschedule" title="Pindah jadwal" size="lg">
        <form method="POST" class="space-y-4" @submit.prevent="submit($el)"
            :action="endpoint(@js(route('owner.bookings.reschedule', ['booking' => '__ID__'])))">
            @csrf
            <input type="hidden" name="_form" :value="'reschedule-' + booking?.id">
            <div x-show="conflict" x-cloak>
                <x-ui.error-block>
                    <x-slot:title><span x-text="conflict ? 'Jam ' + conflict.start.replace(':', '.') + ' baru saja terisi booking lain' : ''"></span></x-slot:title>
                    <p class="mt-1">Jadwal belum berubah dan isian Anda tidak hilang.</p>
                    <p x-show="conflict?.alternatives.length" class="mt-1">Pilih jam lain yang masih kosong:</p>
                    <x-slot:action>
                        <template x-for="alternative in conflict?.alternatives || []" :key="alternative.id">
                            <x-ui.button size="sm" x-on:click="selected = alternative.id; conflict = null" x-text="alternative.jam_mulai.replace(':', '.')" />
                        </template>
                    </x-slot:action>
                </x-ui.error-block>
            </div>
            <template x-for="[field, messages] in Object.entries(errors)" :key="field">
                <p x-text="messages[0]" class="text-bq-danger" role="alert"></p>
            </template>
            <div class="rounded-lg bg-bq-background px-3 py-2">
                <p class="font-semibold" x-text="booking?.booking_code"></p>
                <p class="text-bq-text-muted" x-text="[booking?.namapelanggan, booking?.layanan?.namalayanan].filter(Boolean).join(' · ')"></p>
            </div>
            <x-owner.field-error name="error" x-cloak x-show="{{ \Illuminate\Support\Js::from($rescheduleForm) }} === 'reschedule-' + booking?.id" />
            <div>
                <label for="reschedule-date" class="font-medium">Tanggal baru</label>
                <input id="reschedule-date" type="date" name="tanggal" x-model="date" :min="minDate" @change="conflict = null; load()" :disabled="saving" required
                    class="bq-input mt-1 block w-full sm:w-auto">
                <x-owner.field-error name="tanggal" x-cloak x-show="{{ \Illuminate\Support\Js::from($rescheduleForm) }} === 'reschedule-' + booking?.id" />
            </div>
            <fieldset class="space-y-2">
                <legend class="font-medium">Slot baru</legend>
                <p x-show="loading" class="text-bq-text-muted" role="status">Memuat slot…</p>
                <p x-show="failed" x-text="failed" class="text-bq-danger" role="alert"></p>
                <p x-show="!loading && !failed && slots.length === 0" class="text-bq-text-muted">Belum ada slot pada tanggal ini. Pilih tanggal lain.</p>
                <div class="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto">
                    <template x-for="slot in slots" :key="slot.id">
                        <label class="flex items-start gap-2 rounded-lg border border-bq-border bg-bq-surface p-2 has-[:checked]:border-bq-primary has-[:checked]:bg-bq-primary-light has-[:disabled]:bg-bq-background">
                            <input type="radio" name="schedule_id" x-model="selected" :value="slot.id" :disabled="!slot.is_available || saving">
                            <span class="min-w-0">
                                <span class="block font-bq-display text-bq-clock font-semibold" :class="{ 'line-through': !slot.is_available }" x-text="slot.formatted_time"></span>
                                <span class="block text-xs text-bq-text-muted" x-text="slot.formatted_price"></span>
                                <span class="block text-xs text-bq-text-muted" x-text="slot.is_current ? 'Slot saat ini' : (slot.is_available ? '' : 'Tidak tersedia')"></span>
                                <span class="block text-xs text-bq-text-muted" x-show="slot.is_available && slot.price_difference != null"
                                    x-text="'Selisih ' + formatDifference(slot.price_difference)"></span>
                            </span>
                        </label>
                    </template>
                </div>
                <x-owner.field-error name="schedule_id" x-cloak x-show="{{ \Illuminate\Support\Js::from($rescheduleForm) }} === 'reschedule-' + booking?.id" />
            </fieldset>
            <p x-show="selectedSlot?.price_difference != null" class="rounded-lg bg-bq-info-bg px-3 py-2 text-bq-info" aria-live="polite">
                Selisih harga slot pilihan <strong x-text="selectedSlot ? formatDifference(selectedSlot.price_difference) : ''"></strong>
            </p>
            <div>
                <label for="reschedule-reason" class="font-medium">Alasan pindah jadwal (opsional)</label>
                <input id="reschedule-reason" name="alasan" x-model="reason" type="text" placeholder="Contoh: Permintaan pelanggan"
                    class="bq-input mt-1 block w-full">
                <x-owner.field-error name="alasan" x-cloak x-show="{{ \Illuminate\Support\Js::from($rescheduleForm) }} === 'reschedule-' + booking?.id" />
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="hide()" class="bq-btn bq-btn-secondary">Batal</button>
                <button type="submit" x-ref="rescheduleSubmit" :disabled="saving || !selectedSlot" class="bq-btn bq-btn-primary disabled:opacity-50"
                    x-text="saving ? 'Menyimpan…' : 'Simpan jadwal baru'">Simpan jadwal baru</button>
            </div>
        </form>
    </x-owner.dialog>
</div>
