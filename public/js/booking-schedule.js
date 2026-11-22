document.addEventListener('alpine:init', () => {
    const visibleSlots = slots => slots.filter(slot => !slot.is_past);
    // Display helpers only (BQ-165 design A): availability and prices come from the server.
    const monthOf = date => date.slice(0, 7);
    const isoDate = day => [day.getFullYear(), String(day.getMonth() + 1).padStart(2, '0'), String(day.getDate()).padStart(2, '0')].join('-');
    const rupiah = amount => 'Rp' + new Intl.NumberFormat('id-ID').format(Math.round(amount));

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
        viewMonth: monthOf(config.initialDate),

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

        get monthLabel() {
            const [year, month] = this.viewMonth.split('-').map(Number);
            return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
        },

        get stripMonthLabel() {
            const [year, month] = monthOf(this.selectedDate).split('-').map(Number);
            return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
        },

        get canGoPrev() {
            return this.dates.length > 0 && this.viewMonth > monthOf(this.dates[0].date);
        },

        get canGoNext() {
            return this.dates.length > 0 && this.viewMonth < monthOf(this.dates[this.dates.length - 1].date);
        },

        shiftMonth(step) {
            const [year, month] = this.viewMonth.split('-').map(Number);
            this.viewMonth = isoDate(new Date(year, month - 1 + step, 1)).slice(0, 7);
        },

        prevMonth() {
            if (this.canGoPrev) this.shiftMonth(-1);
        },

        nextMonth() {
            if (this.canGoNext) this.shiftMonth(1);
        },

        get calendarDays() {
            const [year, month] = this.viewMonth.split('-').map(Number);
            const first = new Date(year, month - 1, 1);
            const start = new Date(year, month - 1, 1 - ((first.getDay() + 6) % 7));
            const today = this.dates[0]?.date;
            const days = [];
            for (let index = 0; index < 42; index++) {
                const day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + index);
                if (index >= 35 && day.getMonth() !== month - 1) break;
                const date = isoDate(day);
                const entry = this.dates.find(item => item.date === date);
                days.push({
                    key: date,
                    date,
                    label: day.getDate(),
                    inMonth: day.getMonth() === month - 1,
                    isToday: date === today,
                    isSelected: date === this.selectedDate,
                    isDisabled: !entry,
                    hasSessions: Boolean(entry?.is_available),
                    ariaLabel: (entry?.label || date) + (entry?.is_available ? '' : ', tidak ada sesi'),
                });
            }
            return days;
        },

        get selectedDayShort() {
            return this.dates.find(day => day.date === this.selectedDate)?.label || '';
        },

        get nearestAvailableDay() {
            const later = this.dates.find(day => day.is_available && day.date > this.selectedDate);
            return later || [...this.dates].reverse().find(day => day.is_available && day.date < this.selectedDate) || null;
        },

        get selectedTimeLabel() {
            const sorted = this.sortedSelectedSlots;
            if (!sorted.length) return 'Belum dipilih';
            return this.startLabel(sorted[0]) + ' – ' + sorted[sorted.length - 1].end_time.replace(':', '.');
        },

        get subtotalLabel() {
            return rupiah(this.selectedSlots.reduce((sum, slot) => sum + Number(slot.price || 0), 0));
        },

        startLabel(slot) {
            return (slot.time || '').replace(':', '.');
        },

        pickFromCalendar(date) {
            this.selectDate(date);
            this.$dispatch('close-dialog', { id: 'kalender-jadwal' });
        },

        async selectDate(date) {
            if (this.isSubmitting || !this.dates.some(day => day.date === date)) return;
            this.viewMonth = monthOf(date);
            this.requestController?.abort();
            this.requestController = new AbortController();
            const requestNumber = ++this.requestNumber;
            this.selectedDate = date;
            this.$nextTick(() => this.$refs.dateStrip?.querySelector('[aria-pressed="true"]')?.scrollIntoView({ block: 'nearest', inline: 'center' }));
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
