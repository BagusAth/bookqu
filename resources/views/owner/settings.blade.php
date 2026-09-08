@extends('layouts.owner-layout')

@section('title', 'Settings')

@section('content')
<div class="mx-auto max-w-4xl space-y-6" x-data="{
    deleteModalOpen: {{ $errors->has('confirm_account') ? 'true' : 'false' }},
    confirmInput: '',
    targetAccount: '{{ strtolower(trim($tenant->user->email ?? '')) }}',
    targetBusiness: '{{ strtolower(trim($tenant->namabisnis ?? '')) }}'
}">

    {{-- ── Flash Notifications ── --}}
    @if (session('sukses'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-xs font-semibold text-emerald-800 shadow-2xs flex items-center gap-3">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>{{ session('sukses') }}</p>
        </div>
    @endif
    @if (session('pesan'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-xs font-semibold text-amber-800 shadow-2xs flex items-center gap-3">
            <svg class="h-5 w-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>{{ session('pesan') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-xs text-rose-800 shadow-2xs">
            <div class="flex items-center gap-2.5 font-bold text-rose-900">
                <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p>Periksa kembali input Anda:</p>
            </div>
            <ul class="mt-2 list-disc space-y-1 pl-7 font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('components.owner.page-header', [
        'judul' => 'Settings',
        'subjudul' => 'Manage your business profile, account credentials, and preferences.',
    ])

    {{-- ── 1. Business Profile ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-2xs" id="business-profile">
        <div class="border-b border-bq-border px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-bq-text">Business Profile</h2>
                <p class="text-xs text-bq-text-muted">Update your business information visible to customers.</p>
            </div>
            @if(!empty($tenant->slug))
                <a href="/{{ $tenant->slug }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-[#4F46E5] hover:underline">
                    <span>Lihat Halaman Booking</span>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            @endif
        </div>
        <form class="space-y-5 p-6" method="POST" action="{{ route('owner.settings.profile') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Business Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="namabisnis" required value="{{ old('namabisnis', $tenant->namabisnis) }}" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-namabisnis" placeholder="Nama Bisnis Anda">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Business Type <span class="text-rose-500">*</span></label>
                    <input type="text" name="jenisbisnis" required value="{{ old('jenisbisnis', $tenant->jenisbisnis) }}" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-jenisbisnis" placeholder="Contoh: Studio Foto, Barbershop, Klinik">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-bq-text">Slug (URL Publik)</label>
                <div class="flex items-center rounded-xl border border-bq-border bg-slate-50 px-3.5 py-2">
                    <span class="text-xs font-medium text-bq-text-muted">bookqu.com/</span>
                    <input type="text" value="{{ $tenant->slug }}" class="w-full border-0 bg-transparent px-1 text-xs font-bold text-bq-text focus:outline-none cursor-default" id="input-slug" readonly>
                    <button type="button" @click="navigator.clipboard.writeText('{{ url('/' . $tenant->slug) }}'); alert('Link booking berhasil disalin!')" class="text-xs font-bold text-[#4F46E5] hover:text-[#4338CA] whitespace-nowrap px-2 py-1 rounded-lg hover:bg-indigo-50 transition">
                        Salin Link
                    </button>
                </div>
                <p class="mt-1 text-[11px] text-bq-text-muted">Slug diperbarui otomatis saat nama bisnis diubah.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-bq-text">Address <span class="text-rose-500">*</span></label>
                <textarea rows="2" name="alamat" required class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-alamat" placeholder="Alamat lengkap lokasi bisnis">{{ old('alamat', $tenant->alamat) }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-bq-text">Description</label>
                <textarea rows="3" name="deskripsi" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-deskripsi" placeholder="Deskripsi singkat profil bisnis Anda yang akan dilihat pelanggan">{{ old('deskripsi', $tenant->deskripsi) }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Phone Number <span class="text-rose-500">*</span></label>
                    <input type="text" name="nomorhp" required value="{{ old('nomorhp', $tenant->nomorhp) }}" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-nomorhp" placeholder="08xxxxxxxxxx">
                </div>
                <div x-data="{ logoPreview: '{{ $tenant->logo_url ?? '' }}' }">
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Business Logo <span class="text-[11px] font-normal text-bq-text-muted">(JPG, PNG, WebP, SVG, maks 10MB)</span></label>
                    <input
                        type="file"
                        name="logo"
                        accept="image/jpeg,image/png,image/webp,image/svg+xml,image/gif"
                        class="w-full rounded-xl border border-bq-border bg-bq-surface px-3.5 py-2 text-xs text-bq-text file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-[#4F46E5] hover:file:bg-indigo-100"
                        id="input-logo"
                        @change="
                            const f = $event.target.files[0];
                            if (f) {
                                const r = new FileReader();
                                r.onload = ev => logoPreview = ev.target.result;
                                r.readAsDataURL(f);
                            }
                        "
                    >
                    <template x-if="logoPreview">
                        <div class="mt-2.5 flex items-center gap-3">
                            <img :src="logoPreview" alt="Logo preview" class="h-14 w-14 rounded-xl object-cover border border-[#e7e2f7] shadow-xs bg-[#f8f6ff]">
                            <span class="text-[11px] text-bq-text-muted">Logo saat ini / terpilih</span>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex justify-end border-t border-bq-border pt-5">
                <button type="submit" class="craft-btn inline-flex items-center gap-2 rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg" id="btn-save-profile">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- ── 2. Account Settings ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-2xs" id="account-settings" x-data="{ showPass: false, showConfirm: false }">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-bold text-bq-text">Account Settings</h2>
            <p class="text-xs text-bq-text-muted">Manage your personal account credentials and security.</p>
        </div>
        <form class="space-y-5 p-6" method="POST" action="{{ route('owner.settings.account') }}">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="namalengkap" required value="{{ old('namalengkap', $tenant->user->namalengkap ?? '') }}" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-namalengkap">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" required value="{{ old('email', $tenant->user->email ?? '') }}" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-email">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Phone Number <span class="text-rose-500">*</span></label>
                    <input type="text" name="nomorhp" required value="{{ old('nomorhp', $tenant->user->nomorhp ?? '') }}" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-account-nomorhp">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Role</label>
                    <div class="flex items-center rounded-xl border border-bq-border bg-slate-50 px-4 py-2.5">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-[#4F46E5] uppercase">
                            {{ $tenant->user->role ?? 'owner' }}
                        </span>
                        <span class="ml-2 text-[11px] text-bq-text-muted">(Akun Pemilik Bisnis)</span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 pt-2 border-t border-bq-border/60">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Change Password <span class="text-[11px] font-normal text-bq-text-muted">(Kosongkan jika tidak ingin ganti)</span></label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" name="password" placeholder="Minimal 8 karakter" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 pr-11 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-password">
                        <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-bq-text-muted hover:text-bq-text">
                            <span class="text-[11px] font-bold" x-text="showPass ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Confirm Password</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" placeholder="Ulangi password baru" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 pr-11 text-xs text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-password-confirmation">
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-bq-text-muted hover:text-bq-text">
                            <span class="text-[11px] font-bold" x-text="showConfirm ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex justify-end border-t border-bq-border pt-5">
                <button type="submit" class="craft-btn inline-flex items-center gap-2 rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg" id="btn-save-account">
                    Update Account
                </button>
            </div>
        </form>
    </div>

    {{-- ── 3. Payment Settings ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-2xs" id="payment-settings" x-data="{ paymentMode: '{{ old('payment_mode', $tenant->payment_mode ?? 'platform') }}' }">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-bold text-bq-text">Payment Settings</h2>
            <p class="text-xs text-bq-text-muted">Pilih metode gateway pembayaran pelanggan dan konfigurasi Midtrans jika diperlukan.</p>
        </div>
        <form class="space-y-5 p-6" method="POST" action="{{ route('owner.settings.payment') }}">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Payment Mode</label>
                    <select name="payment_mode" x-model="paymentMode" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        <option value="platform">Platform BookQu (Payout otomatis & penarikan saldo)</option>
                        <option value="owner">Owner Midtrans (Menggunakan Kredensial Pribadi)</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Midtrans Status</label>
                    <div class="flex items-center rounded-xl border border-bq-border bg-slate-50 px-4 py-2.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 font-bold uppercase
                            {{ ($tenant->midtrans_status ?? '') === 'approved' ? 'text-emerald-700' : (($tenant->midtrans_status ?? '') === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">
                            <span class="h-2 w-2 rounded-full {{ ($tenant->midtrans_status ?? '') === 'approved' ? 'bg-emerald-500' : (($tenant->midtrans_status ?? '') === 'rejected' ? 'bg-rose-500' : 'bg-amber-500') }}"></span>
                            {{ $tenant->midtrans_status ?? 'pending' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Mode Info Banner --}}
            <div x-show="paymentMode === 'platform'" class="rounded-xl border border-indigo-200 bg-[#EEF2FF] p-4 text-xs text-[#312E81]">
                <p class="font-bold flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Mode Platform Aktif
                </p>
                <p class="mt-1 leading-relaxed text-[#4338CA]">
                    Seluruh pembayaran pelanggan diproses langsung oleh sistem payment gateway BookQu. Dana otomatis masuk ke Saldo Platform bisnis Anda dan dapat dicairkan kapan saja melalui form <strong>Payout & Withdraw</strong> di bawah.
                </p>
            </div>

            <div x-show="paymentMode === 'owner'" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
                <p class="font-bold flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Mode Midtrans Pribadi
                </p>
                <p class="mt-1 leading-relaxed text-amber-800">
                    Pembayaran langsung masuk ke akun merchant Midtrans Anda sendiri. Pastikan Merchant ID, Client Key, dan Server Key di bawah ini telah diisi dengan benar.
                </p>
            </div>

            <div class="space-y-4 pt-2 border-t border-bq-border/60">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Environment</label>
                    <select name="midtrans_environment" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        <option value="sandbox" {{ ($tenant->midtrans_environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (Uji Coba)</option>
                        <option value="production" {{ ($tenant->midtrans_environment ?? '') === 'production' ? 'selected' : '' }}>Production (Live Transaksi Riil)</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-bq-text">Sandbox Merchant ID</label>
                        <input type="text" name="midtrans_sandbox_merchant_id" value="{{ $tenant->midtrans_sandbox_merchant_id }}" placeholder="Gxxxxxxxxx" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-bq-text">Sandbox Client Key</label>
                        <input type="text" name="midtrans_sandbox_client_key" value="{{ $tenant->midtrans_sandbox_client_key }}" placeholder="SB-Mid-client-xxxxxxxx" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Sandbox Server Key</label>
                    <input type="text" name="midtrans_sandbox_server_key" value="{{ $tenant->midtrans_sandbox_server_key ? '********' : '' }}" placeholder="SB-Mid-server-xxxxxxxx" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text">
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 pt-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-bq-text">Production Merchant ID</label>
                        <input type="text" name="midtrans_prod_merchant_id" value="{{ $tenant->midtrans_prod_merchant_id }}" placeholder="Mxxxxxxxxx" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-bq-text">Production Client Key</label>
                        <input type="text" name="midtrans_prod_client_key" value="{{ $tenant->midtrans_prod_client_key }}" placeholder="Mid-client-xxxxxxxx" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-bq-text">Production Server Key</label>
                    <input type="text" name="midtrans_prod_server_key" value="{{ $tenant->midtrans_prod_server_key ? '********' : '' }}" placeholder="Mid-server-xxxxxxxx" class="w-full rounded-xl border border-bq-border bg-bq-surface px-4 py-2.5 text-xs text-bq-text">
                </div>
            </div>

            <div class="flex justify-end border-t border-bq-border pt-5">
                <button type="submit" class="craft-btn inline-flex items-center gap-2 rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg" id="btn-save-payment">
                    Save Payment Settings
                </button>
            </div>
        </form>
    </div>

    {{-- ── 4. Payout & Withdraw ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-2xs" id="payout-settings" x-data="{ withdrawAmount: '' }">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-bold text-bq-text">Payout & Withdraw</h2>
            <p class="text-xs text-bq-text-muted">Kelola saldo platform dari pemesanan pelanggan dan ajukan penarikan dana.</p>
        </div>
        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-4 rounded-xl border border-bq-border bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-bq-text-muted">Saldo Platform Tersedia</p>
                    <p class="text-2xl font-black text-[#4F46E5] mt-0.5">Rp {{ number_format($tenant->saldo_platform ?? 0, 0, ',', '.') }}</p>
                </div>
                <form method="POST" action="{{ route('owner.payouts.request') }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-bq-text-muted">Rp</span>
                        <input
                            type="number"
                            name="jumlah"
                            x-model="withdrawAmount"
                            min="10000"
                            step="1000"
                            max="{{ (int)($tenant->saldo_platform ?? 0) }}"
                            placeholder="Min 10.000"
                            required
                            class="w-36 rounded-xl border border-bq-border bg-white pl-8 pr-3 py-2 text-xs font-bold text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                        >
                    </div>
                    @if(($tenant->saldo_platform ?? 0) >= 10000)
                        <button type="button" @click="withdrawAmount = {{ (int)($tenant->saldo_platform ?? 0) }}" class="rounded-xl border border-bq-border bg-white px-2.5 py-2 text-xs font-semibold text-bq-text hover:bg-slate-50 transition">
                            Tarik Semua
                        </button>
                    @endif
                    <button
                        type="submit"
                        {{ ($tenant->saldo_platform ?? 0) < 10000 ? 'disabled' : '' }}
                        class="craft-btn rounded-xl bg-bq-primary px-4 py-2 text-xs font-bold text-white shadow-sm shadow-bq-primary/25 hover:bg-bq-primary-hover disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Request Withdraw
                    </button>
                </form>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-bq-text">Riwayat Permintaan Withdraw Terakhir</p>
                <div class="mt-3 space-y-2">
                    @forelse ($payouts as $payout)
                        <div class="flex items-center justify-between rounded-xl border border-bq-border bg-white p-3.5 text-xs shadow-2xs">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-[#4F46E5] font-bold">
                                    Rp
                                </div>
                                <div>
                                    <p class="font-bold text-bq-text">Rp {{ number_format($payout->jumlah, 0, ',', '.') }}</p>
                                    <p class="text-[11px] text-bq-text-muted">{{ $payout->requested_at ? $payout->requested_at->translatedFormat('d M Y, H:i') : '-' }}</p>
                                </div>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase
                                {{ $payout->status === 'success' ? 'bg-emerald-50 text-emerald-700' : ($payout->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ $payout->status }}
                            </span>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-bq-border bg-slate-50/50 px-4 py-6 text-center text-xs text-bq-text-muted">
                            Belum ada riwayat permintaan withdraw.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── 5. Notification Preferences ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-2xs"
         x-data="{
             emailnotif: JSON.parse(localStorage.getItem('bq_notif_email') ?? 'true'),
             smsnotif: JSON.parse(localStorage.getItem('bq_notif_sms') ?? 'false'),
             bookingnotif: JSON.parse(localStorage.getItem('bq_notif_booking') ?? 'true'),
             paymentnotif: JSON.parse(localStorage.getItem('bq_notif_payment') ?? 'true'),
             saveNotice: false,
             toggle(key) {
                 this[key] = !this[key];
                 localStorage.setItem('bq_notif_' + key.replace('notif', ''), JSON.stringify(this[key]));
                 this.saveNotice = true;
                 setTimeout(() => this.saveNotice = false, 2000);
             }
         }"
         id="notification-settings">
        <div class="border-b border-bq-border px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-bq-text">Notification Preferences</h2>
                <p class="text-xs text-bq-text-muted">Atur notifikasi penting yang ingin Anda terima.</p>
            </div>
            <span x-show="saveNotice" x-transition class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">
                Tersimpan di browser
            </span>
        </div>
        <div class="divide-y divide-bq-border">
            @php
                $notifitems = [
                    ['var' => 'emailnotif', 'judul' => 'Email Notifications', 'deskripsi' => 'Terima update aktivitas booking dan reservasi via email'],
                    ['var' => 'smsnotif', 'judul' => 'SMS Notifications', 'deskripsi' => 'Terima SMS darurat dan konfirmasi pesanan penting'],
                    ['var' => 'bookingnotif', 'judul' => 'New Booking Alerts', 'deskripsi' => 'Dapatkan peringatan seketika saat pelanggan membuat booking baru'],
                    ['var' => 'paymentnotif', 'judul' => 'Payment Alerts', 'deskripsi' => 'Dapatkan notifikasi langsung saat pembayaran berhasil dikonfirmasi'],
                ];
            @endphp
            @foreach ($notifitems as $notif)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <p class="text-xs font-bold text-bq-text">{{ $notif['judul'] }}</p>
                        <p class="text-xs text-bq-text-muted">{{ $notif['deskripsi'] }}</p>
                    </div>
                    <button
                        type="button"
                        @click="toggle('{{ $notif['var'] }}')"
                        :class="{{ $notif['var'] }} ? 'bg-[#4F46E5]' : 'bg-slate-300'"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200"
                    >
                        <span :class="{{ $notif['var'] }} ? 'translate-x-5' : 'translate-x-0.5'" class="pointer-events-none mt-0.5 inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── 6. Danger Zone ── --}}
    <div class="rounded-2xl border border-rose-200 bg-rose-50/40 shadow-2xs" id="danger-zone">
        <div class="px-6 py-4">
            <h2 class="text-base font-bold text-rose-700">Danger Zone</h2>
            <p class="text-xs text-rose-600/80">Tindakan berisiko tinggi dan tidak dapat dikembalikan.</p>
        </div>
        <div class="border-t border-rose-200/80 px-6 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold text-bq-text">Delete Business Account</p>
                    <p class="text-xs text-bq-text-muted">Hapus akun Anda beserta seluruh data bisnis, layanan, jadwal, dan riwayat booking secara permanen.</p>
                </div>
                <button
                    type="button"
                    @click="deleteModalOpen = true; confirmInput = ''; $nextTick(() => $refs.confirmInputBox?.focus())"
                    class="craft-btn shrink-0 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-xs font-bold text-rose-600 shadow-xs transition-all hover:bg-rose-600 hover:text-white hover:border-rose-600"
                    id="btn-delete-account"
                >
                    Delete Account
                </button>
            </div>
        </div>
    </div>

    {{-- ── Modal Konfirmasi Hapus Akun ── --}}
    <div
        x-show="deleteModalOpen"
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="deleteModalOpen = false"
    >
        {{-- Backdrop --}}
        <div
            x-show="deleteModalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-[#231a3d]/60 backdrop-blur-xs"
            @click="deleteModalOpen = false"
        ></div>

        {{-- Modal Dialog --}}
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div
                x-show="deleteModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-rose-100"
            >
                <div class="p-6">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 mb-4">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>

                    <h3 class="text-base font-bold text-center text-slate-900">
                        Hapus Akun & Bisnis Permanen?
                    </h3>
                    
                    <p class="mt-2 text-xs text-center text-slate-600 leading-relaxed">
                        Tindakan ini <strong>bersifat permanen dan tidak dapat dibatalkan</strong>. Semua data bisnis <strong>{{ $tenant->namabisnis }}</strong> (layanan, jadwal, voucher, pelanggan, dan riwayat booking) akan dihapus secara total.
                    </p>

                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 text-left">
                        <label class="block text-xs font-semibold text-rose-900 mb-1.5">
                            Ketikkan email akun Anda untuk konfirmasi:
                        </label>
                        <div class="select-all font-mono text-xs font-bold text-rose-700 bg-white border border-rose-200 px-3 py-1.5 rounded-lg text-center mb-2.5">
                            {{ $tenant->user->email }}
                        </div>
                        <input
                            type="text"
                            x-ref="confirmInputBox"
                            x-model="confirmInput"
                            placeholder="Ketik email akun Anda di sini..."
                            class="w-full rounded-xl border border-rose-300 bg-white px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                            autocomplete="off"
                        >
                    </div>

                    @error('confirm_account')
                        <p class="mt-2 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="deleteModalOpen = false"
                            class="w-full sm:w-auto rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>

                        <form method="POST" action="{{ route('owner.settings.account.delete') }}" class="w-full sm:w-auto">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="confirm_account" :value="confirmInput">
                            <button
                                type="submit"
                                :disabled="confirmInput.trim().toLowerCase() !== targetAccount && confirmInput.trim().toLowerCase() !== targetBusiness"
                                :class="(confirmInput.trim().toLowerCase() === targetAccount || confirmInput.trim().toLowerCase() === targetBusiness)
                                    ? 'bg-rose-600 hover:bg-rose-700 text-white cursor-pointer shadow-md shadow-rose-600/25'
                                    : 'bg-rose-200 text-rose-400 cursor-not-allowed'"
                                class="craft-btn w-full sm:w-auto rounded-xl px-4 py-2.5 text-xs font-bold transition-all"
                            >
                                Hapus Akun Permanen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
