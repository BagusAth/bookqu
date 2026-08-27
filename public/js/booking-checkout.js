document.addEventListener('alpine:init', () => {
    Alpine.data('bookingCheckout', () => ({
        nama: '',
        email: '',
        nomorhp: '',
        catatan: '',
        
        errors: {},
        isSubmitting: false,
        serverError: null,

        submitCheckout() {
            this.errors = {};
            this.serverError = null;

            // Basic client-side validation
            let isValid = true;
            if (!this.nama.trim()) {
                this.errors.nama = 'Name is required';
                isValid = false;
            }
            if (!this.email.trim() || !/^\S+@\S+\.\S+$/.test(this.email)) {
                this.errors.email = 'Valid email is required';
                isValid = false;
            }
            if (!this.nomorhp.trim()) {
                this.errors.nomorhp = 'Phone number is required';
                isValid = false;
            }

            if (!isValid) return;

            this.isSubmitting = true;

            const form = document.getElementById('checkout-form');
            const url = form.getAttribute('action');
            const token = document.querySelector('input[name="_token"]').value;
            const scheduleId = document.querySelector('input[name="schedule_id"]').value;

            const payload = {
                nama: this.nama,
                email: this.email,
                nomorhp: this.nomorhp,
                catatan: this.catatan,
                schedule_id: scheduleId
            };

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        // Laravel validation errors
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                        throw new Error('Validation failed');
                    } else if (data.error) {
                        throw new Error(data.error);
                    } else {
                        throw new Error('An unexpected error occurred. Please try again.');
                    }
                }
                return data;
            })
            .then(data => {
                if (data.snap_token) {
                    const snapUrl = document.getElementById('checkout-root').dataset.snapUrl;
                    const clientKey = document.getElementById('checkout-root').dataset.clientKey;
                    const slug = document.getElementById('checkout-root').dataset.tenantSlug;
                    
                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result){
                            window.location.href = `/${slug}/booking/success`;
                        },
                        onPending: function(result){
                            // Midtrans treats some completed pending steps as pending callback
                            // we redirect to success and let webhook handle real settlement
                            window.location.href = `/${slug}/booking/success`;
                        },
                        onError: function(result){
                            window.location.href = `/${slug}/booking/failed`;
                        },
                        onClose: function(){
                            // User closed the popup, reset button state
                            document.dispatchEvent(new CustomEvent('checkout-reset'));
                        }
                    });
                }
            })
            .catch(error => {
                if (error.message !== 'Validation failed') {
                    this.serverError = error.message;
                }
                this.isSubmitting = false;
            });
        },

        init() {
            // Listen for popup close event to reset the submit button
            document.addEventListener('checkout-reset', () => {
                this.isSubmitting = false;
            });
        }
    }));
});
