document.addEventListener('alpine:init', () => {
    Alpine.data('customerCheckout', config => ({
        ...config,
        initialTotal: config.total,
        storageKey: 'bookqu_saved_customer:' + config.slug,
        display: null,
        quoteKey: JSON.stringify({ peserta: 1, voucher_code: '', selected_addons: [] }),
        quotePending: false,
        quoteError: '',
        quoteTimer: null,
        request: null,
        remember: false,
        hasSavedContact: false,
        submitting: false,

        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(this.storageKey) ?? 'null');
                if (saved && typeof saved === 'object') {
                    this.name ||= typeof saved.name === 'string' ? saved.name : '';
                    this.email ||= typeof saved.email === 'string' ? saved.email : '';
                    this.phone ||= typeof saved.phone === 'string' ? saved.phone : '';
                    this.remember = this.hasSavedContact = true;
                }
            } catch {
                // Storage may be unavailable in private or restricted browsers.
            }

            this.$watch('quoteInput', () => this.queueQuote());
            this.queueQuote();
        },

        destroy() {
            clearTimeout(this.quoteTimer);
            this.request?.abort();
        },

        get selectedAddOns() {
            return this.addOnOptions
                .filter(item => Number(this.quantities[item.id]) > 0)
                .map(item => ({ id: item.id, quantity: Number(this.quantities[item.id]) }));
        },

        get quoteInput() {
            return { peserta: this.participants, voucher_code: this.voucherCode, selected_addons: this.selectedAddOns };
        },

        get ready() {
            return !this.quotePending && JSON.stringify(this.quoteInput) === this.quoteKey;
        },

        get totalLabel() {
            if (this.ready) return this.display?.total ?? this.initialTotal;
            return this.quoteError ? 'Belum tersedia' : 'Menghitung…';
        },

        get feedback() {
            if (this.quoteError) return this.quoteError;
            return this.ready ? '' : 'Tunggu sampai total pesanan selesai dihitung.';
        },

        queueQuote() {
            clearTimeout(this.quoteTimer);
            this.request?.abort();
            this.quoteError = '';
            const input = JSON.stringify(this.quoteInput);
            this.quotePending = input !== this.quoteKey;
            if (this.quotePending) this.quoteTimer = setTimeout(() => this.refreshQuote(input), 400);
        },

        async refreshQuote(input) {
            const request = new AbortController();
            this.request = request;
            try {
                const response = await fetch(this.quoteUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.$refs.checkoutToken.value },
                    body: input,
                    signal: request.signal,
                });
                const data = await response.json();
                if (request.signal.aborted || input !== JSON.stringify(this.quoteInput)) return;
                if (!response.ok) {
                    this.quoteError = Object.values(data.errors || {}).flat()[0] || 'Harga tidak dapat dihitung. Coba lagi.';
                    return;
                }
                this.display = data.display;
                this.$refs.checkoutFingerprint.value = data.checkout_fingerprint;
                this.quoteKey = input;
            } catch {
                if (!request.signal.aborted) this.quoteError = 'Harga tidak dapat dihitung. Coba lagi.';
            } finally {
                if (!request.signal.aborted) this.quotePending = false;
            }
        },

        forgetContact() {
            try {
                localStorage.removeItem(this.storageKey);
            } catch {
                return;
            }
            this.remember = this.hasSavedContact = false;
        },

        submit(event) {
            if (!this.ready || this.submitting) {
                event.preventDefault();
                return;
            }
            if (!/^\+?(?:\d[\s-]?){9,14}\d$/.test(this.phone)) {
                event.preventDefault();
                const phone = event.currentTarget.elements.nomorhp;
                phone.setCustomValidity('Nomor WhatsApp harus berisi 10–15 digit angka, boleh diawali +62.');
                phone.reportValidity();
                return;
            }
            if (this.remember) {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify({ name: this.name, email: this.email, phone: this.phone }));
                } catch {
                    // Booking can continue when browser storage is unavailable.
                }
            } else if (this.hasSavedContact) {
                this.forgetContact();
            }
            this.submitting = true;
        },
    }));
});
