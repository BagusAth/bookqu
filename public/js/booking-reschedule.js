document.addEventListener('alpine:init', () => {
    Alpine.data('customerReschedule', config => ({
        selectedDate: '',
        selectedDayLabel: '',
        selectedSlot: null,
        timeSlots: [],
        loadingSlots: false,
        slotMessage: '',
        loadError: false,
        submitting: false,
        request: null,

        init() {
            if (config.previousDate) this.selectDate(config.previousDate, config.previousSlot);
        },

        destroy() {
            this.request?.abort();
        },

        async selectDate(date, restoreId = null) {
            const day = config.dates.find(item => item.date === date);
            if (!day?.is_available || this.submitting) return;
            this.request?.abort();
            const request = new AbortController();
            this.request = request;
            this.selectedDate = date;
            this.selectedDayLabel = day.full_label;
            this.selectedSlot = null;
            this.timeSlots = [];
            this.slotMessage = '';
            this.loadError = false;
            this.loadingSlots = true;

            try {
                const url = new URL(config.slotUrl, window.location.origin);
                url.searchParams.set('tanggal', date);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error('Unable to load sessions');
                const data = await response.json();
                if (request.signal.aborted) return;
                this.timeSlots = data.slots;
                this.slotMessage = data.message || '';
                this.selectedSlot = this.timeSlots.find(slot => slot.id === restoreId && slot.is_available) || null;
            } catch {
                if (!request.signal.aborted) {
                    this.slotMessage = 'Jadwal belum dapat dimuat.';
                    this.loadError = true;
                }
            } finally {
                if (!request.signal.aborted) this.loadingSlots = false;
            }
        },

        selectSlot(slot) {
            if (!this.loadingSlots && slot.is_available) this.selectedSlot = slot;
        },

        isSlotSelected(slot) {
            return this.selectedSlot?.id === slot.id;
        },

        submit(event) {
            if (!this.selectedSlot || this.loadingSlots || this.submitting) {
                event.preventDefault();
                return;
            }
            this.submitting = true;
        },
    }));
});
