document.addEventListener('alpine:init', () => {
    Alpine.data('bookingDateSelection', () => ({
        tenantSlug: '',
        service: null,
        availabilityByDate: {},
        minDate: '',
        maxDate: '',
        minDateObj: null,
        maxDateObj: null,
        selectedDate: '',
        currentYear: null,
        currentMonth: null,
        today: null,
        simulateAvailability: false,

        init() {
            const root = document.getElementById('booking-date-root');
            const serviceEl = document.getElementById('booking-service-data');
            const availabilityEl = document.getElementById('booking-availability-data');

            this.tenantSlug = root?.dataset.tenantSlug || '';
            this.minDate = root?.dataset.minDate || '';
            this.maxDate = root?.dataset.maxDate || '';
            this.selectedDate = root?.dataset.selectedDate || '';
            this.simulateAvailability = root?.dataset.simulate === 'true';

            this.service = serviceEl ? JSON.parse(serviceEl.textContent || 'null') : null;

            const availability = availabilityEl ? JSON.parse(availabilityEl.textContent || '[]') : [];
            this.availabilityByDate = availability.reduce((acc, item) => {
                acc[item.date] = item;
                return acc;
            }, {});

            this.today = new Date();
            this.today.setHours(0, 0, 0, 0);

            this.minDateObj = this.minDate ? this.parseDate(this.minDate) : this.today;
            this.maxDateObj = this.maxDate ? this.parseDate(this.maxDate) : null;

            if (this.simulateAvailability && Object.keys(this.availabilityByDate).length === 0) {
                this.seedSimulatedAvailability();
            }

            if (this.selectedDate && (this.isOutsideRange(this.selectedDate) || !this.isAvailable(this.selectedDate))) {
                this.selectedDate = '';
            }

            const baseDate = this.selectedDate ? this.parseDate(this.selectedDate) : this.today;
            this.currentYear = baseDate.getFullYear();
            this.currentMonth = baseDate.getMonth();
        },

        seedSimulatedAvailability() {
            if (!this.minDateObj || !this.maxDateObj) {
                return;
            }

            const cursor = new Date(this.minDateObj.getTime());
            const end = new Date(this.maxDateObj.getTime());

            while (cursor <= end) {
                const dateString = this.formatDateString(cursor);
                const slots = this.simulateSlotCount(cursor);
                this.availabilityByDate[dateString] = {
                    date: dateString,
                    total_slots: slots,
                    available_slots: slots,
                };
                cursor.setDate(cursor.getDate() + 1);
            }
        },

        simulateSlotCount(dateObj) {
            const seed = dateObj.getDate();
            return (seed % 4) + 2;
        },

        get currentMonthLabel() {
            return new Date(this.currentYear, this.currentMonth, 1).toLocaleString('en-US', {
                month: 'long',
                year: 'numeric',
            });
        },

        get calendarDays() {
            const days = [];
            const firstOfMonth = new Date(this.currentYear, this.currentMonth, 1);
            const startIndex = (firstOfMonth.getDay() + 6) % 7;
            const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

            for (let i = 0; i < startIndex; i += 1) {
                const date = new Date(this.currentYear, this.currentMonth, i - startIndex + 1);
                days.push(this.buildDay(date, false));
            }

            for (let day = 1; day <= daysInMonth; day += 1) {
                const date = new Date(this.currentYear, this.currentMonth, day);
                days.push(this.buildDay(date, true));
            }

            const totalCells = 42;
            while (days.length < totalCells) {
                const date = new Date(this.currentYear, this.currentMonth, daysInMonth + (days.length - (startIndex + daysInMonth)) + 1);
                days.push(this.buildDay(date, false));
            }

            return days;
        },

        buildDay(date, isCurrentMonth) {
            const dateString = this.formatDateString(date);
            const available = this.isAvailable(dateString);
            const full = this.isFull(dateString);
            const outsideRange = this.isOutsideRange(dateString);
            const disabled = !isCurrentMonth || outsideRange || !available;

            return {
                key: `${dateString}-${isCurrentMonth ? 'current' : 'adjacent'}`,
                date: dateString,
                label: date.getDate(),
                isCurrentMonth,
                isAvailable: isCurrentMonth && available,
                isFull: isCurrentMonth && full,
                isDisabled: disabled,
                isSelected: this.selectedDate === dateString,
                isToday: this.isToday(dateString),
                showSlots: isCurrentMonth && !outsideRange && (available || full),
            };
        },

        prevMonth() {
            if (!this.canGoPrev) {
                return;
            }

            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear -= 1;
                return;
            }

            this.currentMonth -= 1;
        },

        nextMonth() {
            if (!this.canGoNext) {
                return;
            }

            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear += 1;
                return;
            }

            this.currentMonth += 1;
        },

        get canGoPrev() {
            if (!this.minDateObj) {
                return true;
            }

            const current = new Date(this.currentYear, this.currentMonth, 1);
            const min = new Date(this.minDateObj.getFullYear(), this.minDateObj.getMonth(), 1);
            return current > min;
        },

        get canGoNext() {
            if (!this.maxDateObj) {
                return true;
            }

            const current = new Date(this.currentYear, this.currentMonth, 1);
            const max = new Date(this.maxDateObj.getFullYear(), this.maxDateObj.getMonth(), 1);
            return current < max;
        },

        selectDate(date) {
            if (!date || this.isOutsideRange(date) || !this.isAvailable(date)) {
                return;
            }

            this.selectedDate = date;
        },

        isAvailable(date) {
            const entry = this.availabilityByDate[date];
            if (!entry) {
                return false;
            }

            return entry.available_slots > 0;
        },

        isFull(date) {
            const entry = this.availabilityByDate[date];
            if (!entry) {
                return false;
            }

            return entry.available_slots === 0 && entry.total_slots > 0;
        },

        isOutsideRange(date) {
            const parsed = this.parseDate(date);

            if (this.minDateObj && parsed < this.minDateObj) {
                return true;
            }

            if (this.maxDateObj && parsed > this.maxDateObj) {
                return true;
            }

            return false;
        },

        isToday(date) {
            const parsed = this.parseDate(date);
            return parsed.getTime() === this.today.getTime();
        },

        slotLabel(date) {
            const entry = this.availabilityByDate[date];
            if (!entry) {
                return '';
            }

            if (entry.available_slots === 0) {
                return 'Full';
            }

            return entry.available_slots === 1 ? '1 slot' : `${entry.available_slots} slots`;
        },

        formatDateString(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        parseDate(dateString) {
            const parts = dateString.split('-').map((part) => parseInt(part, 10));
            if (parts.length !== 3) {
                return new Date();
            }

            return new Date(parts[0], parts[1] - 1, parts[2]);
        },

        formatDate(dateString) {
            const parsed = this.parseDate(dateString);
            return parsed.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            });
        },

        get selectedDateLabel() {
            return this.selectedDate ? this.formatDate(this.selectedDate) : 'Choose a date';
        },

        get selectedTimeLabel() {
            return this.selectedDate ? 'Select time next' : 'Time not selected';
        },

        get totalLabel() {
            return this.service ? this.service.price_label : 'Rp0.00';
        },
    }));
});
