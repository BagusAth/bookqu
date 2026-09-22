document.addEventListener('alpine:init', () => {
    Alpine.data('bookingProgram', () => ({
        services: [],
        servicesById: {},
        selectedServiceId: null,
        selectedService: null,
        tenantSlug: '',
        storageKey: '',
        isSubmitting: false,
        searchQuery: '',
        activeCategory: 'all',
        mounted: false,

        isCardVisible(serviceId, categoryId, serviceName) {
            const matchesCat = this.activeCategory === 'all' || String(categoryId) === String(this.activeCategory);
            const matchesSearch = !this.searchQuery || serviceName.toLowerCase().includes(this.searchQuery.toLowerCase().trim());
            return matchesCat && matchesSearch;
        },

        setCategory(catId) {
            this.activeCategory = catId;
        },

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

            // Clean up any legacy localStorage entry so no lingering selection persists
            try {
                if (this.storageKey) localStorage.removeItem(this.storageKey);
            } catch (e) {}

            this.selectedServiceId = null;
            this.selectedService = null;
            this.isSubmitting = false;

            const resetState = () => {
                this.isSubmitting = false;
                this.selectedServiceId = null;
                this.selectedService = null;
            };

            window.addEventListener('pageshow', resetState);
            window.addEventListener('pagehide', resetState);
            window.addEventListener('popstate', resetState);
            window.addEventListener('booking-reset-submitting', resetState);

            this.mounted = true;
        },

        selectServiceById(id) {
            if (this.isSubmitting) return;

            const service = this.servicesById[String(id)];
            this.selectedServiceId = service ? service.id : id;
            this.selectedService = service || null;

            // Update DOM input synchronously so native form.submit() immediately receives the service_id
            const form = this.$refs?.confirmForm || document.getElementById('booking-program-form');
            if (form) {
                let input = form.querySelector('input[name="service_id"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'service_id';
                    form.appendChild(input);
                }
                input.value = String(this.selectedServiceId);
            }

            // Immediately proceed to Step 2 (Date selection)
            this.handleConfirm();
        },

        handleConfirm() {
            if (!this.selectedServiceId || this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            // Safety reset timeout: Ensures if page is cached via BFCache or navigation is slow,
            // isSubmitting never remains permanently true when navigating back.
            setTimeout(() => {
                this.isSubmitting = false;
            }, 1200);

            const form = this.$refs?.confirmForm || document.getElementById('booking-program-form');
            if (form) {
                let input = form.querySelector('input[name="service_id"]');
                if (input && this.selectedServiceId) {
                    input.value = String(this.selectedServiceId);
                }
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
            const dur = this.selectedService.duration ?? this.selectedService.durasi ?? null;
            if (!dur) return '-';
            const unit = this.selectedService.duration_unit || this.selectedService.satuan_durasi || 'menit';
            return `${dur} ${unit}`;
        },
    }));
});
