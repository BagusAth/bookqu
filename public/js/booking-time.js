document.addEventListener('alpine:init', () => {
    Alpine.data('bookingTimeSelection', () => ({
        service: null,
        selectedDate: '',
        selectedDateDisplay: '',
        selectedTime: '',
        selectedScheduleId: '',
        timeSlots: [],
        groupedSlots: {
            morning: [],
            afternoon: [],
            evening: [],
        },
        simulateAvailability: false,

        init() {
            const root = document.getElementById('booking-time-root');
            const serviceEl = document.getElementById('booking-service-data');
            const slotsEl = document.getElementById('booking-time-slots-data');

            this.selectedDate = root?.dataset.selectedDate || '';
            this.selectedDateDisplay = root?.dataset.selectedDateLabel || '';
            this.selectedTime = root?.dataset.selectedTime || '';
            this.simulateAvailability = root?.dataset.simulate === 'true';

            this.service = serviceEl ? JSON.parse(serviceEl.textContent || 'null') : null;

            let rawSlots = slotsEl ? JSON.parse(slotsEl.textContent || '[]') : [];

            if (this.simulateAvailability && rawSlots.length === 0) {
                rawSlots = this.buildSimulatedSlots();
            }

            this.timeSlots = rawSlots.map((slot, index) => ({
                ...slot,
                id: slot.id ?? index + 1,
                isAvailable: slot.is_available ?? true,
                isDisabled: slot.is_disabled ?? false,
                isSelected: false,
            }));

            this.groupSlots();

            if (this.selectedTime) {
                const match = this.timeSlots.find((slot) => slot.time === this.selectedTime && slot.isAvailable);
                if (match) {
                    this.selectSlot(match);
                } else {
                    this.selectedTime = '';
                    this.selectedScheduleId = '';
                }
            }
        },

        buildSimulatedSlots() {
            const template = [
                '08:00',
                '09:30',
                '11:00',
                '11:30',
                '13:00',
                '14:30',
                '16:00',
                '17:30',
                '19:00',
            ];

            const selectedDate = this.parseDate(this.selectedDate);
            const now = new Date();
            const isToday = selectedDate ? this.isSameDay(selectedDate, now) : false;

            return template.map((time, index) => {
                const session = this.resolveSession(time);
                const period = this.resolvePeriod(time);
                const isPast = isToday ? this.isTimePast(selectedDate, time, now) : false;
                const isAvailable = !isPast;

                return {
                    id: index + 1,
                    time,
                    label: time,
                    period,
                    session,
                    is_available: isAvailable,
                    is_disabled: !isAvailable,
                    is_booked: false,
                    is_past: isPast,
                };
            });
        },

        resolveSession(time) {
            const hour = parseInt(time.split(':')[0], 10);
            if (hour >= 5 && hour <= 11) {
                return 'morning';
            }
            if (hour >= 12 && hour <= 17) {
                return 'afternoon';
            }
            return 'evening';
        },

        resolvePeriod(time) {
            const hour = parseInt(time.split(':')[0], 10);
            return hour >= 12 ? 'PM' : 'AM';
        },

        isTimePast(dateObj, time, now) {
            if (!dateObj) {
                return false;
            }

            const parts = time.split(':').map((value) => parseInt(value, 10));
            if (parts.length !== 2 || parts.some(Number.isNaN)) {
                return false;
            }

            const slotDate = new Date(
                dateObj.getFullYear(),
                dateObj.getMonth(),
                dateObj.getDate(),
                parts[0],
                parts[1]
            );

            return slotDate <= now;
        },

        isSameDay(left, right) {
            return left.getFullYear() === right.getFullYear()
                && left.getMonth() === right.getMonth()
                && left.getDate() === right.getDate();
        },

        groupSlots() {
            const groups = {
                morning: [],
                afternoon: [],
                evening: [],
            };

            this.timeSlots.forEach((slot) => {
                if (groups[slot.session]) {
                    groups[slot.session].push(slot);
                }
            });

            this.groupedSlots = groups;
        },

        selectSlot(slot) {
            if (!slot || slot.isDisabled) {
                return;
            }

            this.timeSlots.forEach((item) => {
                item.isSelected = item.id === slot.id;
            });

            this.selectedScheduleId = slot.id;
            this.selectedTime = slot.time;
        },

        formatDate(dateString) {
            if (!dateString) {
                return '';
            }
            const parsed = this.parseDate(dateString);
            if (!parsed) {
                return dateString;
            }

            return parsed.toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });
        },

        parseDate(dateString) {
            const parts = dateString.split('-').map((part) => parseInt(part, 10));
            if (parts.length !== 3 || parts.some(Number.isNaN)) {
                return null;
            }

            return new Date(parts[0], parts[1] - 1, parts[2]);
        },

        get selectedDateLabel() {
            if (this.selectedDateDisplay) {
                return this.selectedDateDisplay;
            }

            return this.selectedDate ? this.formatDate(this.selectedDate) : 'Choose a date';
        },

        get selectedTimeLabel() {
            if (!this.selectedTime) {
                return 'Select a time slot';
            }

            const selectedSlot = this.timeSlots.find((slot) => slot.id === this.selectedScheduleId);
            if (selectedSlot) {
                return `${selectedSlot.label} ${selectedSlot.period}`;
            }

            return this.selectedTime;
        },

        get totalLabel() {
            return this.service ? this.service.price_label : 'Rp0.00';
        },

        get hasSlots() {
            return this.timeSlots.length > 0;
        },
    }));
});
