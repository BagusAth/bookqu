document.addEventListener('alpine:init', () => {
    // Display helpers only (BQ-165 design A §7.7): availability comes from the server.
    const monthOf = date => date.slice(0, 7);
    const isoDate = day => [day.getFullYear(), String(day.getMonth() + 1).padStart(2, '0'), String(day.getDate()).padStart(2, '0')].join('-');
    const monthName = month => {
        const [year, number] = month.split('-').map(Number);
        return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(year, number - 1, 1));
    };

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
        dates: config.dates,
        viewMonth: monthOf(config.previousDate || config.currentDate || config.dates[0]?.date || isoDate(new Date())),

        get monthLabel() {
            return monthName(this.viewMonth);
        },

        get stripMonthLabel() {
            return monthName(monthOf(this.selectedDate || config.currentDate || this.viewMonth + '-01'));
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
                    isToday: date === config.currentDate,
                    isSelected: date === this.selectedDate,
                    isDisabled: !entry || !entry.is_available,
                    hasSessions: Boolean(entry?.is_available),
                    isFull: Boolean(entry && !entry.is_available),
                    ariaLabel: (entry?.full_label || date) + (date === config.currentDate ? ', jadwal saat ini' : '') + (entry?.is_available ? '' : ', tidak ada sesi'),
                });
            }
            return days;
        },

        pickFromCalendar(date) {
            this.selectDate(date);
            this.$dispatch('close-dialog', { id: 'kalender-ubah' });
        },

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
            this.viewMonth = monthOf(date);
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
