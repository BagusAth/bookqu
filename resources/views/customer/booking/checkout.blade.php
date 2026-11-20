@extends('customer.layouts.booking-shell')

@section('title', 'Data Pemesan')
@section('current_step', 3)
@section('back_url', \App\Support\CustomerBookingRoutes::url('customer.booking.schedule', $tenant->slug))
@section('back_label', 'Kembali ke Jadwal')

@section('content')
@php($oldAddOnQuantities = collect(old('selected_addons', []))->pluck('quantity', 'id')->filter(fn ($quantity) => is_numeric($quantity))->all())
@php($oldPromoCode = is_string(old('voucher_code')) ? old('voucher_code') : '')
<div id="booking-checkout-root"
     data-tenant-slug="{{ $tenant->slug }}"
     x-data="{
        participants: @js((int) old('peserta', 1)),
        voucherCode: @js($oldPromoCode),
        addOnOptions: @js($addOnOptions),
        quantities: @js($oldAddOnQuantities),
        quote: null,
        quoteBusy: false,
        quoteError: '',
        quoteKey: '',
        quoteRequest: 0,
        get selectedAddOns() {
            return this.addOnOptions.filter(item => Number(this.quantities[item.id]) > 0)
                .map(item => ({ id: item.id, quantity: Number(this.quantities[item.id]) }));
        },
        get quoteInput() {
            return { peserta: this.participants, voucher_code: this.voucherCode, selected_addons: this.selectedAddOns };
        },
        get reviewed() {
            return this.quoteKey === JSON.stringify(this.quoteInput)
                || (!this.quote && Number(this.participants) === 1 && !this.voucherCode && !this.selectedAddOns.length);
        },
        money(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        },
        async refreshQuote() {
            const request = ++this.quoteRequest;
            const input = JSON.stringify(this.quoteInput);
            this.quoteBusy = true;
            this.quoteError = '';
            try {
                const response = await fetch(@js(\App\Support\CustomerBookingRoutes::url('customer.booking.validate-voucher', $tenant->slug)), {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.$refs.checkoutToken.value },
                    body: input,
                });
                const data = await response.json();
                if (request !== this.quoteRequest || input !== JSON.stringify(this.quoteInput)) return;
                if (!response.ok) {
                    this.quoteError = Object.values(data.errors || {}).flat()[0] || 'Harga tidak dapat diperbarui. Coba lagi.';
                    return;
                }
                this.quote = data.quote;
                this.$refs.checkoutFingerprint.value = data.checkout_fingerprint;
                this.quoteKey = input;
            } catch {
                if (request === this.quoteRequest) this.quoteError = 'Harga tidak dapat diperbarui. Coba lagi.';
            } finally {
                if (request === this.quoteRequest) this.quoteBusy = false;
            }
        },
        name: {{ \Illuminate\Support\Js::from(old('namapelanggan', '')) }},
        email: {{ \Illuminate\Support\Js::from(old('email', '')) }},
        phone: {{ \Illuminate\Support\Js::from(old('nomorhp', '')) }},
        nameError: '',
        emailError: '',
        phoneError: '',
        touched: { name: false, email: false, phone: false },
        validateName() {
            if (!this.touched.name) return;
            const val = (this.name || '').trim();
            if (!val) {
                this.nameError = 'Nama lengkap wajib diisi.';
            } else if (val.length < 2) {
                this.nameError = 'Nama lengkap minimal 2 karakter.';
            } else {
                this.nameError = '';
            }
        },
        validateEmail() {
            if (!this.touched.email) return;
            const val = (this.email || '').trim();
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!val) {
                this.emailError = 'Alamat email wajib diisi.';
            } else if (!re.test(val)) {
                this.emailError = 'Format email tidak valid (contoh: nama@domain.com).';
            } else {
                this.emailError = '';
            }
        },
        validatePhone() {
            if (!this.touched.phone) return;
            const val = (this.phone || '').trim();
            if (!val) {
                this.phoneError = 'Nomor WhatsApp / HP wajib diisi.';
            } else if (!/^\+?(?:\d[\s\-]?){9,14}\d$/.test(val)) {
                this.phoneError = 'Nomor WhatsApp / HP harus berisi 10–15 digit angka (boleh diawali +62).';
            } else {
                this.phoneError = '';
            }
        }
     }">
    <form
        id="booking-checkout-form"
        class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_340px]"
        method="POST"
        action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.process-checkout', $tenant->slug) }}"
    >
        <input type="hidden" name="_token" value="{{ csrf_token() }}" x-ref="checkoutToken">
        <input type="hidden" name="checkout_fingerprint" value="{{ $checkoutFingerprint }}" x-ref="checkoutFingerprint">

        {{-- Left Column: Form Fields, Mobile Review & Policies --}}
        @include('customer.partials.booking.checkout-form')

        {{-- Right Column: Sticky Booking Summary --}}
        @include('customer.partials.booking.checkout-summary-desktop')

        {{-- Mobile Bottom Floating Action Bar --}}
        @include('customer.partials.booking.checkout-mobile-bar')
    </form>
</div>
@endsection

@section('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.12/dist/cdn.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputName = document.getElementById('namapelanggan');
        const inputEmail = document.getElementById('email');
        const inputPhone = document.getElementById('nomorhp');
        const chkRemember = document.getElementById('remember-customer-data');
        const checkoutForm = document.getElementById('booking-checkout-form');
        const submitBtn = document.getElementById('submit-checkout-btn');
        const mobileSubmitBtn = document.getElementById('mobile-submit-checkout-btn');

        const STORAGE_KEY = 'bookqu_saved_customer';

        // Auto-fill from localStorage if available
        try {
            const savedData = localStorage.getItem(STORAGE_KEY);
            if (savedData) {
                const parsed = JSON.parse(savedData);
                if (parsed && typeof parsed === 'object') {
                    if (inputName && !inputName.value && parsed.name) {
                        inputName.value = parsed.name;
                        inputName.dispatchEvent(new Event('input'));
                    }
                    if (inputEmail && !inputEmail.value && parsed.email) {
                        inputEmail.value = parsed.email;
                        inputEmail.dispatchEvent(new Event('input'));
                    }
                    if (inputPhone && !inputPhone.value && parsed.phone) {
                        inputPhone.value = parsed.phone;
                        inputPhone.dispatchEvent(new Event('input'));
                    }
                    if (chkRemember) chkRemember.checked = true;
                }
            }
        } catch (e) {
            console.warn('LocalStorage not accessible:', e);
        }

        // Save or remove customer data upon submit & prevent double submission
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function (event) {
                // Field errors shown on screen must also stop the submit; the server re-validates.
                const form = window.Alpine ? window.Alpine.$data(document.getElementById('booking-checkout-root')) : null;
                if (form && (!form.reviewed || form.quoteBusy)) {
                    event.preventDefault();
                    form.quoteError = 'Perbarui ringkasan harga sebelum membayar.';
                    document.getElementById('refresh-checkout-quote').focus();
                    return;
                }
                if (form) {
                    form.touched = { name: true, email: true, phone: true };
                    form.validateName();
                    form.validateEmail();
                    form.validatePhone();
                    if (form.nameError || form.emailError || form.phoneError) {
                        event.preventDefault();
                        const invalid = form.nameError ? inputName : (form.emailError ? inputEmail : inputPhone);
                        if (invalid) invalid.focus();
                        return;
                    }
                }

                if (chkRemember && chkRemember.checked) {
                    const dataToSave = {
                        name: inputName ? inputName.value.trim() : '',
                        email: inputEmail ? inputEmail.value.trim() : '',
                        phone: inputPhone ? inputPhone.value.trim() : ''
                    };
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(dataToSave));
                } else {
                    localStorage.removeItem(STORAGE_KEY);
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                }
                if (mobileSubmitBtn) {
                    mobileSubmitBtn.disabled = true;
                    mobileSubmitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                }
            });
        }

        // A page restored from the back-forward cache must be submittable again (BQ-063 T3-06)
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) {
                return;
            }
            [submitBtn, mobileSubmitBtn].forEach(function (button) {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('opacity-75', 'cursor-not-allowed');
                }
            });
        });
    });
</script>
@endsection
