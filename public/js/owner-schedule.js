document.addEventListener('alpine:init', () => {
    async function json(url, options = {}) {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', ...options.headers } });
        if (!response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('Permintaan gagal. Muat ulang halaman lalu coba lagi.');
        }
        const data = await response.json();
        if (!response.ok) {
            const messages = Object.values(data.errors ?? {}).flat();
            throw new Error(messages.join(' ') || data.error || 'Permintaan tidak dapat diproses.');
        }
        return data;
    }

    Alpine.data('scheduleOpeningHours', (openingHours) => ({
        nextKey: 0,
        days: {},
        closed: {},
        errors: [],
        busy: false,
        init() {
            for (let day = 1; day <= 7; day++) {
                this.days[day] = (openingHours[day] ?? []).map(interval => ({ ...interval, key: ++this.nextKey }));
                this.closed[day] = this.days[day].length === 0;
            }
        },
        add(day) {
            this.days[day].push({ key: ++this.nextKey, opens_at: '', closes_at: '' });
        },
        toggle(day) {
            if (!this.closed[day] && this.days[day].length === 0) this.add(day);
        },
        copyToAll(source) {
            for (let day = 1; day <= 7; day++) {
                if (day === source) continue;
                this.days[day] = this.days[source].map(interval => ({ ...interval, key: ++this.nextKey }));
                this.closed[day] = this.closed[source];
            }
        },
        async save(form) {
            this.busy = true;
            this.errors = [];
            const fields = new FormData(form);
            const days = Object.fromEntries(Object.entries(this.days).map(([day, intervals]) => [
                day, this.closed[day] ? [] : intervals.map(({ opens_at, closes_at }) => ({ opens_at, closes_at })),
            ]));
            try {
                const response = await fetch(form.action, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': fields.get('_token'),
                    },
                    body: JSON.stringify({ days, slot_open_days_ahead: fields.get('slot_open_days_ahead') || null }),
                });
                if (response.redirected && response.ok) {
                    window.location.assign(response.url);
                    return;
                }
                if (response.headers.get('content-type')?.includes('application/json')) {
                    this.errors = Object.values((await response.json()).errors ?? {}).flat();
                }
                if (!this.errors.length) this.errors = ['Jam buka gagal disimpan.'];
            } catch {
                this.errors = ['Jam buka gagal disimpan.'];
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('scheduleBulkPreview', (openingHours) => ({
        period: 'harian',
        preview: null,
        checkedFields: '',
        error: '',
        busy: false,
        openingChoices: [],
        openingWarning: '',
        useOpeningHours(form) {
            const value = this.period === 'harian' ? form.elements.tanggal.value : form.elements.tanggalmulai.value;
            if (!value) {
                this.openingWarning = 'Pilih tanggal terlebih dahulu.';
                return;
            }
            const weekday = new Date(value + 'T00:00:00Z').getUTCDay() || 7;
            this.openingChoices = openingHours[weekday] || [];
            this.openingWarning = this.openingChoices.length ? '' : 'Jam buka untuk hari ini belum diatur.';
            if (this.openingChoices.length === 1) this.applyOpeningHours(form, this.openingChoices[0]);
        },
        applyOpeningHours(form, interval) {
            form.elements.jammulai.value = interval.opens_at;
            form.elements.jamselesai.value = interval.closes_at;
            this.preview = null;
            this.openingChoices = [];
            this.openingWarning = '';
        },
        fingerprint(form) {
            return new URLSearchParams(new FormData(form)).toString();
        },
        canSave(form) {
            return !this.busy && this.preview?.to_create > 0 && this.checkedFields === this.fingerprint(form);
        },
        async check(form) {
            if (this.busy || !form.reportValidity()) return;
            this.preview = null;
            this.error = '';
            this.busy = true;
            const fields = this.fingerprint(form);
            try {
                const result = await json(form.dataset.previewUrl, { method: 'POST', body: new URLSearchParams(fields) });
                if (fields === this.fingerprint(form)) {
                    this.preview = result;
                    this.checkedFields = fields;
                }
            } catch (error) {
                this.error = error.message;
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('schedulePriceSimulator', () => ({
        hours: [],
        error: '',
        busy: false,
        rupiah: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }),
        async simulate(form) {
            if (this.busy) return;
            this.busy = true;
            this.hours = [];
            this.error = '';
            const params = new URLSearchParams(new FormData(form)).toString();
            try {
                const data = await json(form.action + '?' + params);
                if (params === new URLSearchParams(new FormData(form)).toString()) this.hours = data.hours;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.busy = false;
            }
        },
    }));
});
