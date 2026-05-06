document.addEventListener('alpine:init', () => {
    Alpine.data('bookingProgram', () => ({
        services: [],
        servicesById: {},
        selectedServiceId: null,
        selectedService: null,
        tenantSlug: '',
        storageKey: '',

        init() {
            const dataEl = document.getElementById('booking-services-data');
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
        },

        handleConfirm() {
            if (!this.selectedServiceId || !this.tenantSlug) {
                return;
            }

            window.location.href = this.confirmUrl;
        },

        get confirmUrl() {
            if (!this.selectedServiceId || !this.tenantSlug) {
                return '#';
            }

            return `/${this.tenantSlug}/booking/date?service=${this.selectedServiceId}`;
        },

        get totalLabel() {
            return this.selectedService ? this.selectedService.price_label : 'Rp0.00';
        },
    }));
});
