document.addEventListener('alpine:init', () => {
    Alpine.data('bookingProgram', () => ({
        services: [],
        servicesById: {},
        selectedServiceId: null,
        selectedService: null,
        tenantSlug: '',
        storageKey: '',
        isSubmitting: false,

        init() {
            // Support both IDs consistently to prevent mismatch bugs
            const dataEl = document.getElementById('booking-services-data') || document.getElementById('booking-service-data');
            const rootEl = document.getElementById('booking-program-root');

            this.services = dataEl ? JSON.parse(dataEl.textContent || '[]') : [];
            this.servicesById = this.services.reduce((acc, service) => {
                acc[String(service.id)] = service;
                return acc;
            }, {});

            this.tenantSlug = rootEl?.dataset.tenantSlug || '';
            this.storageKey = this.tenantSlug
                ? `bookqu:selected-service:${this.tenantSlug}`
                : 'bookqu:selected-service';

            this.restoreSelection();
        },

        restoreSelection() {
            if (!this.storageKey) {
                return;
            }

            const raw = localStorage.getItem(this.storageKey);
            if (!raw) {
                return;
            }

            try {
                const saved = JSON.parse(raw);
                const service = this.servicesById[String(saved.id)];
                if (service) {
                    this.selectedServiceId = service.id;
                    this.selectedService = service;
                } else {
                    localStorage.removeItem(this.storageKey);
                }
            } catch (error) {
                localStorage.removeItem(this.storageKey);
            }
        },

        selectServiceById(id) {
            const service = this.servicesById[String(id)];
            if (!service) {
                return;
            }

            this.selectedServiceId = service.id;
            this.selectedService = service;

            if (this.storageKey) {
                localStorage.setItem(this.storageKey, JSON.stringify({ id: service.id }));
            }

            // Selection strictly updates state and summary. NO auto-submit!
            // Customer reviews the summary and clicks "Lanjutkan" button.
        },

        handleConfirm() {
            if (!this.selectedServiceId || this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            const form = this.$refs?.confirmForm || document.getElementById('booking-program-form');
            if (form) {
                form.submit();
            }
        },

        get totalLabel() {
            return this.selectedService ? this.selectedService.price_label : 'Rp 0';
        },

        get serviceName() {
            return this.selectedService ? this.selectedService.name : 'Belum memilih layanan';
        },

        get serviceDuration() {
            if (!this.selectedService) return '-';
            const unit = this.selectedService.duration_unit || 'menit';
            return `${this.selectedService.duration} ${unit}`;
        },
    }));
});
