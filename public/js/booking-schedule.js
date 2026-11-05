document.addEventListener('alpine:init', () => {
    Alpine.data('schedulePicker', (config) => ({
        ...config,
        selectedDate: config.initialDate,
        selectedDateFormatted: config.initialDateLabel,
        morningSlots: config.initialMorningSlots,
        afternoonSlots: config.initialAfternoonSlots,
        eveningSlots: config.initialEveningSlots,
        selectedSlots: [],
        loadingSlots: false,
        isSubmitting: false,
        mounted: false,
        errorMessage: '',
        serviceFee: 0,
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
            this.mounted = true;
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
            this.selectedDateFormatted = new Intl.DateTimeFormat('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta',
            }).format(new Date(`${date}T00:00:00+07:00`));
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
                this.morningSlots = data.morning;
                this.afternoonSlots = data.afternoon;
                this.eveningSlots = data.evening;
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

        toggleSlot(slot) {
            if (this.isSubmitting || this.loadingSlots || !slot.is_available) return;
            this.errorMessage = '';
            const sorted = this.sortedSelectedSlots;
            const existing = sorted.findIndex(item => item.id === slot.id);
            if (existing >= 0) {
                this.selectedSlots = existing === 0 ? sorted.slice(1) : sorted.slice(0, existing);
            } else {
                const candidate = [...sorted, slot];
                if (!this.isContiguous(candidate)) {
                    this.errorMessage = 'Slot harus berurutan. Pilih sesi yang berdekatan terlebih dahulu.';
                    return;
                }
                this.selectedSlots = candidate;
            }
        },

        isSlotSelected(slot) {
            return this.selectedSlots.some(item => item.id === slot.id);
        },

        getSlotOrder(slot) {
            return this.sortedSelectedSlots.findIndex(item => item.id === slot.id) + 1;
        },

        scrollDates(direction) {
            this.$refs.dateStrip?.scrollBy({ left: direction === 'left' ? -220 : 220, behavior: 'smooth' });
        },

        submit(event) {
            if (this.isSubmitting || this.loadingSlots || this.selectedSlots.length === 0 || !this.isContiguous(this.selectedSlots)) {
                event.preventDefault();
                this.errorMessage = 'Pilih sesi yang tersedia dan berurutan terlebih dahulu.';
                return;
            }
            this.isSubmitting = true;
        },

        get selectedTimeFormatted() {
            const slots = this.sortedSelectedSlots;
            if (slots.length === 0) return 'Belum pilih waktu';
            return `${slots[0].time} – ${slots[slots.length - 1].end_time} WIB`;
        },

        get selectedDurationFormatted() {
            // BQ-114: each slot's own length, falling back to the service duration for old payloads
            const minutes = this.selectedSlots.reduce((sum, slot) => sum + Number(slot.duration_minutes ?? this.service.duration), 0);
            return `${minutes} menit`;
        },

        get subtotal() {
            return this.selectedSlots.reduce((sum, slot) => sum + Number(slot.price ?? this.service.price), 0);
        },

        get total() {
            return this.subtotal + this.serviceFee;
        },

        formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        },
    }));
});
