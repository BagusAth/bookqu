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
        errorMessage: '',
        hasBounced: false,

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
                    if (prevTimesRaw && prevTimesRaw !== '[]') {
                        prevTimes = [prevTimesRaw];
                    }
                }
            }

            let rawSlots = slotsEl ? JSON.parse(slotsEl.textContent || '[]') : [];

            if (this.simulateAvailability && rawSlots.length === 0) {
                rawSlots = this.buildSimulatedSlots();
            }

            const duration = this.service?.duration || 60;

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

                const startTime = slot.start_time || slot.time;
                let endTime = slot.end_time;
                if (!endTime && startTime) {
                    const [sh, sm] = startTime.split(':').map(Number);
                    const totalMins = sh * 60 + sm + duration;
                    const eh = Math.floor((totalMins / 60) % 24).toString().padStart(2, '0');
                    const em = (totalMins % 60).toString().padStart(2, '0');
                    endTime = `${eh}:${em}`;
                }
                const rangeLabel = slot.range_label || `${startTime} – ${endTime}`;

                return {
                    ...slot,
                    id: slot.id ?? index + 1,
                    time: startTime,
                    start_time: startTime,
                    end_time: endTime,
                    range_label: rangeLabel,
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

            const resetSubmit = () => {
                this.isSubmitting = false;
            };

            window.addEventListener('pageshow', resetSubmit);
            window.addEventListener('pagehide', resetSubmit);
            window.addEventListener('popstate', resetSubmit);
            window.addEventListener('booking-reset-submitting', resetSubmit);
        },

        buildSimulatedSlots() {
            const template = [
                '08:00',
                '09:00',
                '10:00',
                '11:00',
                '13:00',
                '14:00',
                '15:00',
                '16:00',
                '17:00',
            ];

            const selectedDate = this.parseDate(this.selectedDate);
            const now = new Date();
            const isToday = selectedDate ? this.isSameDay(selectedDate, now) : false;
            const duration = this.service?.duration || 60;
            const price = this.service?.price || 0;
            const priceLabel = this.service?.price_label || ('Rp ' + new Intl.NumberFormat('id-ID').format(price));

            return template.map((time, index) => {
                const session = this.resolveSession(time);
                const period = 'WIB';
                const isPast = isToday ? this.isTimePast(selectedDate, time, now) : false;
                const isAvailable = !isPast;
                const [sh, sm] = time.split(':').map(Number);
                const totalMins = sh * 60 + sm + duration;
                const eh = Math.floor((totalMins / 60) % 24).toString().padStart(2, '0');
                const em = (totalMins % 60).toString().padStart(2, '0');
                const endTime = `${eh}:${em}`;
                const rangeLabel = `${time} – ${endTime}`;

                return {
                    id: index + 1,
                    time,
                    start_time: time,
                    end_time: endTime,
                    range_label: rangeLabel,
                    label: rangeLabel,
                    period,
                    session,
                    price: price,
                    price_label: priceLabel,
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
         * Returns selected slot objects sorted chronologically.
         */
        get sortedSelectedSlots() {
            return this.timeSlots
                .filter(s => this.selectedTimes.some(st => st.id === s.id))
                .sort((a, b) => a.time.localeCompare(b.time));
        },

        /**
         * Early contiguous validation & slot selection.
         * Enforces that multiple slots MUST be contiguous without gaps.
         */
        selectSlot(slot) {
            if (this.isSubmitting) return;
            if (!slot || slot.isDisabled) return;

            this.errorMessage = '';

            const existingIndex = this.selectedTimes.findIndex(
                (item) => item.id === slot.id
            );

            if (existingIndex >= 0) {
                // Deselection handling
                if (this.selectedTimes.length <= 1) {
                    this.selectedTimes = [];
                    slot.isSelected = false;
                    return;
                }

                const sorted = this.sortedSelectedSlots;
                const isFirst = sorted[0].id === slot.id;
                const isLast = sorted[sorted.length - 1].id === slot.id;

                if (isFirst || isLast) {
                    // Clicking an outer boundary slot simply removes it
                    this.selectedTimes = this.selectedTimes.filter(item => item.id !== slot.id);
                    slot.isSelected = false;
                } else {
                    // Clicking a middle slot trims the selection from that slot onwards
                    const slotIndexInSorted = sorted.findIndex(s => s.id === slot.id);
                    const slotsToKeep = sorted.slice(0, slotIndexInSorted);
                    sorted.slice(slotIndexInSorted).forEach(s => {
                        s.isSelected = false;
                    });
                    this.selectedTimes = slotsToKeep.map(s => ({ id: s.id, time: s.time }));
                }
                return;
            }

            // Selecting a new slot
            if (this.selectedTimes.length === 0) {
                this.selectedTimes.push({ id: slot.id, time: slot.time });
                slot.isSelected = true;
                return;
            }

            // Verify contiguousness against current selected range
            const sorted = this.sortedSelectedSlots;
            const earliest = sorted[0];
            const latest = sorted[sorted.length - 1];

            const followsLatest = slot.start_time === latest.end_time || slot.time === latest.end_time;
            const precedesEarliest = slot.end_time === earliest.start_time || slot.end_time === earliest.time;

            if (followsLatest || precedesEarliest) {
                this.selectedTimes.push({ id: slot.id, time: slot.time });
                slot.isSelected = true;
                this.triggerBounce();
            } else {
                // Prescriptive copy from Section 22 & 52 (NO window.alert)
                this.errorMessage = 'Slot harus berurutan. Pilih sesi yang berdekatan terlebih dahulu.';
            }
        },

        triggerBounce() {
            this.hasBounced = true;
            setTimeout(() => {
                this.hasBounced = false;
            }, 350);
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

        get sortedSelectedTimes() {
            return [...this.selectedTimes].sort((a, b) => a.time.localeCompare(b.time));
        },

        /**
         * Prescriptive Mobile Time Summary (Section 27)
         * If 1 slot: '10:00 – 11:00 WIB'
         * If >= 2 slots: '2 sesi: 10:00 – 12:00 WIB' (compact, won't truncate on 360px phones)
         */
        get selectedTimesLabel() {
            const slots = this.sortedSelectedSlots;
            if (slots.length === 0) {
                return 'Belum ada waktu yang dipilih';
            }
            if (slots.length === 1) {
                const s = slots[0];
                return (s.range_label || `${s.time} – ${s.end_time}`) + ' WIB';
            }
            const earliest = slots[0];
            const latest = slots[slots.length - 1];
            return `${slots.length} sesi: ${earliest.start_time || earliest.time} – ${latest.end_time} WIB`;
        },

        get selectedCount() {
            return this.selectedTimes.length;
        },

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

        get perSlotLabel() {
            return this.service ? this.service.price_label : 'Rp 0';
        },

        get hasSlots() {
            return this.timeSlots.length > 0;
        },

        get canSubmit() {
            return this.selectedTimes.length > 0 && !this.isSubmitting;
        },

        /**
         * Lifecycle-bound submission locking (Section 28)
         */
        handleConfirm() {
            if (!this.canSubmit) return;
            this.isSubmitting = true;
            const form = this.$refs?.confirmForm || document.getElementById('booking-time-form');
            if (form) {
                form.submit();
            }
        }
    }));
});
