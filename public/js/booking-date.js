document.addEventListener('alpine:init', () => {
    const INDONESIAN_MONTHS = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const INDONESIAN_DAYS = [
        'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
    ];

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
        isSubmitting: false,

        init() {
            const root = document.getElementById('booking-date-root');
            const serviceEl = document.getElementById('booking-service-data') || document.getElementById('booking-services-data');
            const availabilityEl = document.getElementById('booking-availability-data');

            this.tenantSlug = root?.dataset.tenantSlug || '';
            this.minDate = root?.dataset.minDate || '';
            this.maxDate = root?.dataset.maxDate || '';
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

            // Restore date from session if available (Section 26: Date persistence on back navigation)
            this.selectedDate = root?.dataset.selectedDate || '';
            this.isSubmitting = false;

            const baseDate = this.selectedDate ? (this.parseDate(this.selectedDate) || this.today) : this.today;
            this.currentYear = baseDate.getFullYear();
            this.currentMonth = baseDate.getMonth();

            const resetSubmit = () => {
                this.isSubmitting = false;
            };

            window.addEventListener('pageshow', resetSubmit);
            window.addEventListener('pagehide', resetSubmit);
            window.addEventListener('popstate', resetSubmit);
            window.addEventListener('booking-reset-submitting', resetSubmit);
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
                    is_blocked: false,
                };
                cursor.setDate(cursor.getDate() + 1);
            }
        },

        simulateSlotCount(dateObj) {
            const seed = dateObj.getDate();
            return (seed % 4) + 2;
        },

        get currentMonthLabel() {
            const monthName = INDONESIAN_MONTHS[this.currentMonth] || '';
            return `${monthName} ${this.currentYear}`;
        },

        get calendarDays() {
            const days = [];
            const firstOfMonth = new Date(this.currentYear, this.currentMonth, 1);
            const startIndex = (firstOfMonth.getDay() + 6) % 7; // Monday-first index
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
            const blocked = this.isBlocked(dateString);
            const available = this.isAvailable(dateString);
            const full = this.isFull(dateString);
            const outsideRange = this.isOutsideRange(dateString);
            const disabled = !isCurrentMonth || outsideRange || !available || blocked;

            return {
                key: `${dateString}-${isCurrentMonth ? 'current' : 'adjacent'}`,
                date: dateString,
                label: date.getDate(),
                isCurrentMonth,
                isAvailable: isCurrentMonth && available && !blocked,
                isFull: isCurrentMonth && full,
                isBlocked: isCurrentMonth && blocked,
                isDisabled: disabled,
                isSelected: this.selectedDate === dateString,
                isToday: this.isToday(dateString),
                showSlots: isCurrentMonth && !outsideRange && (available || full || blocked),
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
            if (this.isSubmitting) return;

            const dayObj = this.calendarDays.find(d => d.date === date && d.isCurrentMonth);
            if (dayObj && dayObj.isDisabled) {
                return;
            }

            this.selectedDate = date;

            const form = this.$refs?.confirmForm || document.getElementById('booking-date-form');
            if (form) {
                let input = form.querySelector('input[name="tanggal"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tanggal';
                    form.appendChild(input);
                }
                input.value = String(date);
            }

            this.handleConfirm();
        },

        isAvailable(date) {
            const entry = this.availabilityByDate[date];
            if (!entry) {
                return false;
            }

            return !entry.is_blocked && entry.available_slots > 0;
        },

        isBlocked(date) {
            const entry = this.availabilityByDate[date];
            if (!entry) {
                return false;
            }

            return entry.is_blocked === true;
        },

        isFull(date) {
            const entry = this.availabilityByDate[date];
            if (!entry) {
                return false;
            }

            return !entry.is_blocked && entry.available_slots === 0 && entry.total_slots > 0;
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

        getRemainingSlots(date) {
            const entry = this.availabilityByDate[date];
            return entry ? entry.available_slots : 0;
        },

        /**
         * Prescriptive Semantics (Section 24 & 25)
         * is_blocked === true -> "Tidak tersedia"
         * available_slots === 0 && !is_blocked -> "Penuh"
         */
        slotLabel(date) {
            const entry = this.availabilityByDate[date];
            if (!entry) {
                return '';
            }

            if (entry.is_blocked === true) {
                return 'Tutup';
            }

            if (entry.available_slots === 0) {
                return 'Penuh';
            }

            return `${entry.available_slots} slot`;
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
            const dayName = INDONESIAN_DAYS[parsed.getDay()] || '';
            const dayNum = parsed.getDate();
            const monthName = INDONESIAN_MONTHS[parsed.getMonth()] || '';
            const year = parsed.getFullYear();
            return `${dayName}, ${dayNum} ${monthName} ${year}`;
        },

        get selectedDateLabel() {
            return this.selectedDate ? this.formatDate(this.selectedDate) : 'Pilih tanggal terlebih dahulu';
        },

        get selectedTimeLabel() {
            return this.selectedDate ? 'Pilih jam di langkah berikutnya' : 'Belum memilih jam';
        },

        get totalLabel() {
            return this.service ? this.service.price_label : 'Rp 0';
        },

        handleConfirm() {
            if (!this.selectedDate) {
                const cal = document.querySelector('.booking-calendar');
                if (cal) {
                    cal.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    cal.classList.add('ring-2', 'ring-[#4F46E5]', 'ring-offset-2');
                    setTimeout(() => cal.classList.remove('ring-2', 'ring-[#4F46E5]', 'ring-offset-2'), 1500);
                }
                return;
            }
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            const form = this.$refs?.confirmForm || document.getElementById('booking-date-form');
            if (form) {
                let input = form.querySelector('input[name="tanggal"]');
                if (input && this.selectedDate) {
                    input.value = String(this.selectedDate);
                }
                form.submit();
            }
        }
    }));
});
