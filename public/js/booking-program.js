document.addEventListener('alpine:init', () => {
    Alpine.data('bookingProgram', () => ({
        services: [],
        selectedServiceId: null,
        isSubmitting: false,
        searchQuery: '',
        activeCategory: 'all',

        init() {
            const source = document.getElementById('booking-services-data');
            this.services = JSON.parse(source?.textContent || '[]');
        },

        isCardVisible(categoryId, serviceName) {
            const categoryMatches = this.activeCategory === 'all' || String(categoryId) === String(this.activeCategory);
            return categoryMatches && serviceName.toLowerCase().includes(this.searchQuery.trim().toLowerCase());
        },

        visibleCount() {
            return this.services.filter((service) => this.isCardVisible(service.category_id || '', service.name)).length;
        },

        isFiltering() {
            return this.activeCategory !== 'all' || this.searchQuery.trim() !== '';
        },

        clearFilters() {
            this.searchQuery = '';
            this.activeCategory = 'all';
        },

        setCategory(categoryId) {
            this.activeCategory = categoryId;
        },

        keepSearchEnterLocal(event) {
            if (!event.isComposing && event.keyCode !== 229) event.preventDefault();
        },

        guardSubmit(event) {
            if (!this.selectedServiceId) event.preventDefault();
        },

        selectServiceById(id) {
            if (this.isSubmitting) return;
            this.selectedServiceId = id;
            this.isSubmitting = true;
            const form = this.$refs.confirmForm;
            form.querySelector('input[name="service_id"]').value = id;
            form.submit();
        },
    }));
});
