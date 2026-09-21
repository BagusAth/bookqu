document.addEventListener('alpine:init', () => {
    const INDONESIAN_MONTHS = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const INDONESIAN_DAYS = [
        'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
    ];

    Alpine.data('bookingTimeSelection', () => ({
        service: null,
        selectedDate: '',
        selectedDateDisplay: '',
        selectedTimes: [],       // Array of {id, time} objects
        timeSlots: [],
        groupedSlots: {
            morning: [],
            afternoon: [],
            evening: [],
        },
        simulateAvailability: false,
        isSubmitting: false,

        init() {
            const root = document.getElementById('booking-time-root');
            const serviceEl = document.getElementById('booking-service-data') || document.getElementById('booking-services-data');
            const slotsEl = document.getElementById('booking-time-slots-data');

            this.selectedDate = root?.dataset.selectedDate || '';
            this.selectedDateDisplay = root?.dataset.selectedDateLabel || '';
            this.simulateAvailability = root?.dataset.simulate === 'true';

            this.service = serviceEl ? JSON.parse(serviceEl.textContent || 'null') : null;

            // Parse previously selected times from data attribute (if returning from checkout)
            const prevTimesRaw = root?.dataset.selectedTimes || '';
            let prevTimes = [];
            if (prevTimesRaw) {
                try {
                    prevTimes = JSON.parse(prevTimesRaw);
                } catch (_) {
                    // If it's a single time string from old session format
                    if (prevTimesRaw && prevTimesRaw !== '[]') {
                        prevTimes = [prevTimesRaw];
                    }
                }
            }

            let rawSlots = slotsEl ? JSON.parse(slotsEl.textContent || '[]') : [];

            if (this.simulateAvailability && rawSlots.length === 0) {
                rawSlots = this.buildSimulatedSlots();
            }

            this.timeSlots = rawSlots.map((slot, index) => {
                const isAvailable = slot.is_available ?? true;
                const isBooked = slot.is_booked ?? (!isAvailable && !slot.is_past);
                const isPast = slot.is_past ?? false;

                let statusBadge = 'Tersedia';
                if (isPast) {
                    statusBadge = 'Waktu Terlewat';
                } else if (isBooked) {
                    statusBadge = 'Sudah Dipesan';
                }

                return {
                    ...slot,
                    id: slot.id ?? index + 1,
                    isAvailable: isAvailable,
                    isDisabled: !isAvailable,
                    isBooked: isBooked,
                    isPast: isPast,
                    statusBadge: statusBadge,
                    isSelected: false,
                };
            });

            this.groupSlots();

            // Restore previously selected times (if navigating back from checkout)
            if (prevTimes.length > 0) {
                prevTimes.forEach(prevTime => {
                    const match = this.timeSlots.find(
                        (slot) => slot.time === prevTime && slot.isAvailable
                    );
                    if (match) {
                        match.isSelected = true;
                        this.selectedTimes.push({ id: match.id, time: match.time });
                    }
                });
            }
            // Do NOT auto-select any slot on first visit

            window.addEventListener('pageshow', () => {
                this.isSubmitting = false;
            });
            window.addEventListener('pagehide', () => {
                this.isSubmitting = false;
            });
            window.addEventListener('popstate', () => {
                this.isSubmitting = false;
            });
            window.addEventListener('booking-reset-submitting', () => {
                this.isSubmitting = false;
            });
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
                const period = 'WIB';
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

        /**
         * Toggle a slot's selection on/off (multi-select).
         * No auto-submit — user must press the "Lanjut" button.
         */
        selectSlot(slot) {
            if (this.isSubmitting) return;
            if (!slot || slot.isDisabled) return;

            const existingIndex = this.selectedTimes.findIndex(
                (item) => item.id === slot.id
            );

            if (existingIndex >= 0) {
                // Deselect
                this.selectedTimes.splice(existingIndex, 1);
                slot.isSelected = false;
            } else {
                // Select
                this.selectedTimes.push({ id: slot.id, time: slot.time });
                slot.isSelected = true;
            }
        },

        formatDate(dateString) {
            if (!dateString) {
                return '';
            }
            const parsed = this.parseDate(dateString);
            if (!parsed) {
                return dateString;
            }

            const dayName = INDONESIAN_DAYS[parsed.getDay()] || '';
            const dayNum = parsed.getDate();
            const monthName = INDONESIAN_MONTHS[parsed.getMonth()] || '';
            const year = parsed.getFullYear();
            return `${dayName}, ${dayNum} ${monthName} ${year}`;
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

            return this.selectedDate ? this.formatDate(this.selectedDate) : 'Pilih tanggal';
        },

        /**
         * Returns a sorted array of selected time strings for display.
         */
        get sortedSelectedTimes() {
            return [...this.selectedTimes].sort((a, b) => a.time.localeCompare(b.time));
        },

        /**
         * Human-readable label for all selected times.
         */
        get selectedTimesLabel() {
            if (this.selectedTimes.length === 0) {
                return 'Belum ada jam yang dipilih';
            }
            return this.sortedSelectedTimes.map((t) => t.time + ' WIB').join(', ');
        },

        /**
         * How many slots are selected.
         */
        get selectedCount() {
            return this.selectedTimes.length;
        },

        /**
         * Price label × number of selected slots (respecting per-slot price override).
         */
        get totalLabel() {
            if (!this.service) return 'Rp 0';
            if (this.selectedTimes.length === 0) {
                const total = this.service.price || 0;
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
            }
            let total = 0;
            this.selectedTimes.forEach((st) => {
                const slotObj = this.timeSlots.find((s) => s.id === st.id);
                if (slotObj && slotObj.price !== undefined && slotObj.price !== null) {
                    total += Number(slotObj.price);
                } else {
                    total += Number(this.service.price || 0);
                }
            });
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        },

        /**
         * Price per slot label.
         */
        get perSlotLabel() {
            return this.service ? this.service.price_label : 'Rp 0';
        },

        get hasSlots() {
            return this.timeSlots.length > 0;
        },

        get canSubmit() {
            return this.selectedTimes.length > 0 && !this.isSubmitting;
        },

        handleConfirm() {
            if (!this.canSubmit) return;
            this.isSubmitting = true;
            setTimeout(() => {
                this.isSubmitting = false;
            }, 5000);
            const form = this.$refs?.confirmForm || document.getElementById('booking-time-form');
            if (form) form.submit();
        }
    }));
});
