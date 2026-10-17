<section>
    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Data Pemesan<span class="sr-only"> Isi Data Diri</span></h1>
        <p class="mt-1 text-sm text-[#64748B]">
            Pastikan nama, email, dan nomor WhatsApp Anda sudah benar.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
            <p class="font-bold mb-1">Terdapat kesalahan pada input Anda:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-[#E2E8F0] bg-white p-4 sm:p-7 shadow-xs space-y-5">
        <div class="border-b border-[#F1F5F9] pb-3">
            <h2 class="text-base font-bold text-[#0F172A]">Informasi Pemesan</h2>
            <p class="text-xs text-[#64748B] mt-0.5">Pastikan data yang dimasukkan aktif dan valid.</p>
        </div>

        {{-- Security Trust Banner (S4.3) --}}
        <div class="flex items-center gap-2.5 rounded-xl bg-emerald-50/80 border border-emerald-200/70 px-3.5 py-2.5 text-xs text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span class="font-medium">Data kontak Anda aman, terenkripsi, dan hanya digunakan untuk konfirmasi reservasi.</span>
        </div>

        {{-- Nama Lengkap --}}
        <div>
            <label for="namapelanggan" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                Nama Lengkap <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input
                    type="text"
                    name="namapelanggan"
                    id="namapelanggan"
                    required
                    x-model="name"
                    @blur="touched.name = true; validateName()"
                    @input="validateName()"
                    :class="nameError ? 'border-red-400 bg-red-50/30' : (touched.name && !nameError && name ? 'border-emerald-400 bg-emerald-50/20' : 'border-[#CBD5E1] bg-[#F8FAFC]')"
                    class="w-full rounded-xl border px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                    value="{{ old('namapelanggan') }}"
                    placeholder="Contoh: Budi Santoso"
                />
                <template x-if="touched.name && !nameError && name">
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-emerald-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </template>
            </div>
            <p x-show="nameError" x-text="nameError" x-cloak class="mt-1.5 text-xs text-red-600 font-medium"></p>
            @error('namapelanggan')
                <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                Alamat Email <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input
                    type="email"
                    name="email"
                    id="email"
                    required
                    x-model="email"
                    @blur="touched.email = true; validateEmail()"
                    @input="validateEmail()"
                    :class="emailError ? 'border-red-400 bg-red-50/30' : (touched.email && !emailError && email ? 'border-emerald-400 bg-emerald-50/20' : 'border-[#CBD5E1] bg-[#F8FAFC]')"
                    class="w-full rounded-xl border px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                    value="{{ old('email') }}"
                    placeholder="Contoh: budi@gmail.com"
                />
                <template x-if="touched.email && !emailError && email">
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-emerald-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </template>
            </div>
            <p x-show="emailError" x-text="emailError" x-cloak class="mt-1.5 text-xs text-red-600 font-medium"></p>
            <p class="mt-1.5 text-xs text-[#64748B]">Bukti reservasi &amp; e-ticket invoice akan dikirimkan ke email ini.</p>
            @error('email')
                <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- WhatsApp / No. HP --}}
        <div>
            <label for="nomorhp" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                Nomor WhatsApp / HP <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input
                    type="tel"
                    name="nomorhp"
                    id="nomorhp"
                    required
                    x-model="phone"
                    @blur="touched.phone = true; validatePhone()"
                    @input="validatePhone()"
                    :class="phoneError ? 'border-red-400 bg-red-50/30' : (touched.phone && !phoneError && phone ? 'border-emerald-400 bg-emerald-50/20' : 'border-[#CBD5E1] bg-[#F8FAFC]')"
                    class="w-full rounded-xl border px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                    value="{{ old('nomorhp') }}"
                    placeholder="Contoh: 081234567890"
                />
                <template x-if="touched.phone && !phoneError && phone">
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-emerald-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </template>
            </div>
            <p x-show="phoneError" x-text="phoneError" x-cloak class="mt-1.5 text-xs text-red-600 font-medium"></p>
            <p class="mt-1.5 text-xs text-[#64748B]">Digunakan untuk pengingat jadwal dan konfirmasi langsung.</p>
            @error('nomorhp')
                <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Catatan Opsional --}}
        <div>
            <label for="catatan" class="block text-xs sm:text-sm font-bold text-[#0F172A] mb-1.5">
                Catatan Khusus <span class="text-xs font-normal text-[#94A3B8]">(Opsional)</span>
            </label>
            <textarea
                name="catatan"
                id="catatan"
                rows="3"
                class="w-full rounded-xl border border-[#CBD5E1] bg-[#F8FAFC] px-4 py-3 text-sm text-[#0F172A] transition focus:border-[#4F46E5] focus:bg-white focus:ring-2 focus:ring-[#EEF2FF] focus:outline-none"
                placeholder="Tuliskan catatan khusus atau permintaan tambahan untuk penyedia layanan jika ada..."
            >{{ old('catatan') }}</textarea>
        </div>

        {{-- Remember Me / Auto-fill Checkbox --}}
        <div class="pt-1 border-t border-[#F1F5F9]">
            <label class="flex items-start sm:items-center gap-2.5 cursor-pointer select-none">
                <input
                    type="checkbox"
                    id="remember-customer-data"
                    class="mt-0.5 sm:mt-0 h-4 w-4 rounded border-gray-300 text-[#4F46E5] focus:ring-[#EEF2FF] cursor-pointer"
                />
                <span class="text-xs font-semibold text-[#475569]">
                    Simpan data kontak di perangkat ini untuk pemesanan berikutnya.
                </span>
            </label>
        </div>
    </div>

    {{-- Mobile Reservation Review Card --}}
    @include('customer.partials.booking.checkout-mobile-review')

    {{-- Policy --}}
    <div class="mt-6">
        <x-customer.booking-policy />
    </div>
</section>
