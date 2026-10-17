@extends('customer.layouts.booking-shell')

@section('title', 'Data Pemesan')
@section('current_step', 4)
@section('back_url', route(\App\Support\CustomerBookingRoutes::name('customer.booking.time'), $tenant->slug))
@section('back_label', 'Kembali ke Pilih Waktu')

@section('content')
<div id="booking-checkout-root"
     data-tenant-slug="{{ $tenant->slug }}"
     x-data="{
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
            } else if (val.length < 3) {
                this.nameError = 'Nama lengkap minimal 3 karakter.';
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
            const digits = val.replace(/\D/g, '');
            if (!val) {
                this.phoneError = 'Nomor WhatsApp / HP wajib diisi.';
            } else if (digits.length < 10 || digits.length > 15) {
                this.phoneError = 'Nomor HP harus antara 10 - 15 digit.';
            } else {
                this.phoneError = '';
            }
        }
     }">
    <form
        id="booking-checkout-form"
        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]"
        method="POST"
        action="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.process-checkout'), $tenant->slug) }}"
    >
        @csrf

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
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
            checkoutForm.addEventListener('submit', function () {
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
    });
</script>
@endsection
