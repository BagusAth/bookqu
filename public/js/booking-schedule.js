document.addEventListener('alpine:init', () => {
    const visibleSlots = slots => slots.filter(slot => !slot.is_past);

    Alpine.data('schedulePicker', (config) => ({
        ...config,
        selectedDate: config.initialDate,
        selectedDateFormatted: config.initialDateLabel,
        morningSlots: visibleSlots(config.initialMorningSlots),
        afternoonSlots: visibleSlots(config.initialAfternoonSlots),
        eveningSlots: visibleSlots(config.initialEveningSlots),
        selectedSlots: [],
        loadingSlots: false,
        isSubmitting: false,
        errorMessage: '',
        requestController: null,
        requestNumber: 0,
        resetSubmitting: null,

        init() {
            this.selectedSlots = this.allCurrentSlots.filter(slot => slot.is_available
                && config.selectedScheduleIds.includes(slot.id)
                && config.selectedTimes.includes(slot.time));
            if (!this.isContiguous(this.selectedSlots)) this.selectedSlots = [];
            this.resetSubmitting = () => { this.isSubmitting = false; };
            ['pageshow', 'pagehide', 'popstate', 'booking-reset-submitting'].forEach(event => {
                window.addEventListener(event, this.resetSubmitting);
            });
        },

        destroy() {
            this.requestController?.abort();
            ['pageshow', 'pagehide', 'popstate', 'booking-reset-submitting'].forEach(event => {
                window.removeEventListener(event, this.resetSubmitting);
            });
        },

        get allCurrentSlots() {
            return [...this.morningSlots, ...this.afternoonSlots, ...this.eveningSlots]
                .sort((a, b) => a.time.localeCompare(b.time));
        },

        get sortedSelectedSlots() {
            return [...this.selectedSlots].sort((a, b) => a.time.localeCompare(b.time));
        },

        async selectDate(date) {
            if (this.isSubmitting || !this.dates.some(day => day.date === date && day.is_available)) return;
            this.requestController?.abort();
            this.requestController = new AbortController();
            const requestNumber = ++this.requestNumber;
            this.selectedDate = date;
            this.selectedDateFormatted = this.dates.find(day => day.date === date).label;
            this.selectedSlots = [];
            this.morningSlots = [];
            this.afternoonSlots = [];
            this.eveningSlots = [];
            this.errorMessage = '';
            this.loadingSlots = true;
            try {
                const url = new URL(config.slotsUrl, window.location.origin);
                url.searchParams.set('tanggal', date);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: this.requestController.signal,
                    cache: 'no-store',
                });
                const data = await response.json();
                if (requestNumber !== this.requestNumber) return;
                if (!response.ok) {
                    this.errorMessage = data.error || data.message || 'Jadwal belum dapat dimuat. Silakan coba lagi.';
                    return;
                }
                this.morningSlots = visibleSlots(data.morning);
                this.afternoonSlots = visibleSlots(data.afternoon);
                this.eveningSlots = visibleSlots(data.evening);
                this.selectedDateFormatted = data.date_label;
            } catch (error) {
                if (error.name !== 'AbortError' && requestNumber === this.requestNumber) {
                    this.errorMessage = 'Jadwal belum dapat dimuat. Silakan coba lagi.';
                }
            } finally {
                if (requestNumber === this.requestNumber) this.loadingSlots = false;
            }
        },

        isContiguous(slots) {
            const sorted = [...slots].sort((a, b) => a.time.localeCompare(b.time));
            return sorted.every((slot, index) => index === 0 || sorted[index - 1].end_time === slot.time);
        },

        selectSlot(slot) {
            if (this.isSubmitting || this.loadingSlots || !slot.is_available) return;
            this.errorMessage = '';
            const sorted = this.sortedSelectedSlots;
            const existing = sorted.findIndex(item => item.id === slot.id);
            if (existing >= 0) {
                this.selectedSlots = existing === 0 ? sorted.slice(1) : sorted.slice(0, existing);
            } else {
                const candidate = [...sorted, slot];
                if (!this.isContiguous(candidate)) {
                    this.errorMessage = 'Pilih sesi yang berdekatan agar jadwal berurutan.';
                    return;
                }
                this.selectedSlots = candidate;
            }
        },

        isSlotSelected(slot) {
            return this.selectedSlots.some(item => item.id === slot.id);
        },

        submit(event) {
            if (this.isSubmitting || this.loadingSlots || this.selectedSlots.length === 0 || !this.isContiguous(this.selectedSlots)) {
                event.preventDefault();
                this.errorMessage = 'Pilih sesi yang tersedia dan berurutan terlebih dahulu.';
                return;
            }
            this.isSubmitting = true;
        },

    }));
});
