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
        <form method="POST" class="space-y-4" @submit="saving = true"
            :action="endpoint(@js(route('owner.bookings.reschedule', ['booking' => '__ID__'])))">
            @csrf
            <input type="hidden" name="_form" :value="'reschedule-' + booking?.id">
            <div class="rounded-lg bg-bq-background px-3 py-2">
                <p class="font-semibold" x-text="booking?.booking_code"></p>
                <p class="text-bq-text-muted" x-text="[booking?.namapelanggan, booking?.layanan?.namalayanan].filter(Boolean).join(' · ')"></p>
            </div>
            <x-owner.field-error name="error" x-cloak x-show="@js($rescheduleForm) === 'reschedule-' + booking?.id" />
            <div>
                <label for="reschedule-date" class="font-medium">Tanggal baru</label>
                <input id="reschedule-date" type="date" name="tanggal" x-model="date" :min="minDate" @change="load()" required
                    class="mt-1 block w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 sm:w-auto">
                <x-owner.field-error name="tanggal" x-cloak x-show="@js($rescheduleForm) === 'reschedule-' + booking?.id" />
            </div>
            <fieldset class="space-y-2">
                <legend class="font-medium">Slot baru</legend>
                <p x-show="loading" class="text-bq-text-muted" role="status">Memuat slot…</p>
                <p x-show="failed" x-text="failed" class="text-bq-danger" role="alert"></p>
                <p x-show="!loading && !failed && slots.length === 0" class="text-bq-text-muted">Belum ada slot pada tanggal ini. Pilih tanggal lain.</p>
                <div class="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto">
                    <template x-for="slot in slots" :key="slot.id">
                        <label class="flex items-start gap-2 rounded-lg border border-bq-border bg-bq-surface p-2 has-[:checked]:border-bq-primary has-[:checked]:bg-bq-primary-light has-[:disabled]:bg-bq-background">
                            <input type="radio" name="schedule_id" x-model="selected" :value="slot.id" :disabled="!slot.is_available">
                            <span class="min-w-0">
                                <span class="block font-semibold" x-text="slot.formatted_time"></span>
                                <span class="block text-xs text-bq-text-muted" x-text="slot.formatted_price"></span>
                                <span class="block text-xs text-bq-text-muted" x-text="slot.is_current ? 'Slot saat ini' : (slot.is_available ? '' : 'Tidak tersedia')"></span>
                                <span class="block text-xs text-bq-text-muted" x-show="slot.is_available && slot.price_difference != null"
                                    x-text="'Selisih ' + formatDifference(slot.price_difference)"></span>
                            </span>
                        </label>
                    </template>
                </div>
                <x-owner.field-error name="schedule_id" x-cloak x-show="@js($rescheduleForm) === 'reschedule-' + booking?.id" />
            </fieldset>
            <p x-show="selectedSlot?.price_difference != null" class="rounded-lg bg-bq-info-bg px-3 py-2 text-bq-info" aria-live="polite">
                Selisih harga slot pilihan <strong x-text="selectedSlot ? formatDifference(selectedSlot.price_difference) : ''"></strong>
            </p>
            <div>
                <label for="reschedule-reason" class="font-medium">Alasan pindah jadwal (opsional)</label>
                <input id="reschedule-reason" name="alasan" x-model="reason" type="text" placeholder="Contoh: Permintaan pelanggan"
                    class="mt-1 block w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2">
                <x-owner.field-error name="alasan" x-cloak x-show="@js($rescheduleForm) === 'reschedule-' + booking?.id" />
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="hide()" class="rounded-lg border border-bq-border px-4 py-2 font-semibold">Batal</button>
                <button type="submit" :disabled="saving || !selectedSlot" class="rounded-lg bg-bq-primary px-4 py-2 font-semibold text-white disabled:opacity-50"
                    x-text="saving ? 'Menyimpan…' : 'Simpan jadwal baru'">Simpan jadwal baru</button>
            </div>
        </form>
    </x-owner.dialog>
</div>
