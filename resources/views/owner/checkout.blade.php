@extends('layouts.owner-layout')

@section('title', 'Checkout — Review Pesanan')

@section('content')
<div class="mx-auto max-w-5xl space-y-6" x-data="checkoutPage()">

    {{-- Stepper Navigation --}}
    <nav aria-label="Tahapan Checkout" class="py-2">
        <div class="flex items-center justify-center gap-0">
            {{-- Step 1: Active --}}
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-primary text-sm font-bold text-white shadow-md shadow-bq-primary/30 ring-4 ring-bq-primary/15">
                    1
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-xs font-semibold uppercase tracking-wider text-bq-primary">Langkah 1</p>
                    <p class="text-sm font-bold text-bq-primary">Review Pesanan</p>
                </div>
            </div>

            <div class="mx-2 sm:mx-3 h-0.5 w-6 sm:w-16 rounded bg-bq-border"></div>

            {{-- Step 2: Pembayaran --}}
            <div class="flex items-center gap-2.5 opacity-60">
                <div class="flex h-9 w-9 items-center justify-center rounded-full border border-bq-border bg-bq-surface text-sm font-semibold text-bq-text-muted">
                    2
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-xs font-medium uppercase tracking-wider text-bq-text-muted">Langkah 2</p>
                    <p class="text-sm font-medium text-bq-text-muted">Pembayaran</p>
                </div>
            </div>

            <div class="mx-2 sm:mx-3 h-0.5 w-6 sm:w-16 rounded bg-bq-border"></div>

            {{-- Step 3: Invoice --}}
            <div class="flex items-center gap-2.5 opacity-60">
                <div class="flex h-9 w-9 items-center justify-center rounded-full border border-bq-border bg-bq-surface text-sm font-semibold text-bq-text-muted">
                    3
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-xs font-medium uppercase tracking-wider text-bq-text-muted">Langkah 3</p>
                    <p class="text-sm font-medium text-bq-text-muted">Invoice</p>
                </div>
            </div>
        </div>
    </nav>

    {{-- Pending Payment Alert if Available --}}
    @if (!empty($existingPayment))
        <div class="rounded-xl border border-amber-200 bg-amber-50/90 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
            <div class="flex items-start sm:items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900">Tagihan Sebelumnya Masih Aktif</h3>
                    <p class="text-xs text-amber-800/90 mt-0.5">
                        Anda memiliki transaksi pending (<span class="font-mono font-medium">{{ $existingPayment->order_id }}</span>) untuk paket ini. Anda dapat melanjutkan pembayaran tersebut atau membuat pesanan baru di bawah.
                    </p>
                </div>
            </div>
            <a href="{{ route('owner.checkout.payment', $existingPayment) }}" class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700 transition">
                <span>Lanjutkan Pembayaran Lama</span>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

        {{-- Left Column: Form & Context --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Page Title --}}
            <div>
                <h1 class="text-xl font-bold text-bq-text sm:text-2xl">Review Pesanan Langganan</h1>
                <p class="mt-1 text-sm text-bq-text-muted">
                    Pastikan rincian bisnis dan kontak penerima invoice sudah sesuai sebelum menuju halaman pembayaran.
                </p>
            </div>

            {{-- Card 1: Konteks Bisnis & Tenant --}}
            <div class="rounded-xl border border-bq-border bg-bq-surface p-5 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-bq-border pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-bq-text">Unit Bisnis Penerima Upgrade</h2>
                            <p class="text-xs text-bq-text-subtle">Langganan akan langsung ditautkan ke akun bisnis ini</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Mitra Terdaftar
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <div class="rounded-lg border border-bq-border/60 bg-bq-background p-3.5 space-y-1 min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wider text-bq-text-subtle">Nama Bisnis</p>
                        <p class="text-base font-bold text-bq-text truncate">{{ $tenant->namabisnis }}</p>
                        <div class="flex items-center gap-1.5 text-xs text-bq-text-muted">
                            <svg class="h-3.5 w-3.5 text-bq-text-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span>{{ $tenant->jenisbisnis ?: 'Layanan / Jasa' }}</span>
                        </div>
                    </div>

                    <div class="rounded-lg border border-bq-border/60 bg-bq-background p-3.5 space-y-1 min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wider text-bq-text-subtle">Portal Booking Publik</p>
                        <a href="{{ url('/' . $tenant->slug) }}" target="_blank" rel="noopener noreferrer" class="group flex items-center gap-1.5 text-sm font-semibold text-bq-primary hover:text-bq-primary-hover truncate" title="Buka Halaman Booking Publik">
                            <span class="truncate">{{ preg_replace('#^https?://#', '', url('/' . $tenant->slug)) }}</span>
                            <svg class="h-3.5 w-3.5 shrink-0 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <p class="text-xs text-bq-text-subtle">Tautan yang digunakan pelanggan untuk booking</p>
                    </div>
                </div>
            </div>

            {{-- Checkout Form --}}
            <form action="{{ route('owner.checkout.process') }}" method="POST" class="space-y-5" id="checkout-form" @submit="isSubmitting = true">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                {{-- Card 2: Data Kontak Pembayar --}}
                <div class="rounded-xl border border-bq-border bg-bq-surface p-5 space-y-4 shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-bq-border pb-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-bq-primary/10 text-bq-primary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-bq-text">Kontak Penerima Invoice Resmi</h2>
                            <p class="text-xs text-bq-text-subtle">Faktur resmi dan tanda terima pembayaran akan dikirimkan ke kontak ini</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="nama_pembayar" class="mb-1.5 block text-xs font-semibold text-bq-text uppercase tracking-wider">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nama_pembayar" id="nama_pembayar"
                                value="{{ old('nama_pembayar', $user->namalengkap ?? $tenant->namaowner ?? '') }}"
                                class="w-full rounded-lg border @error('nama_pembayar') border-red-500 ring-1 ring-red-500 @else border-bq-border @enderror bg-bq-background px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-colors focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                placeholder="Contoh: John Doe" required>
                            <p class="mt-1.5 text-xs text-bq-text-subtle">Nama penanggung jawab yang tercantum pada faktur langganan.</p>
                            @error('nama_pembayar')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email_pembayar" class="mb-1.5 block text-xs font-semibold text-bq-text uppercase tracking-wider">
                                Email Invoice <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email_pembayar" id="email_pembayar"
                                value="{{ old('email_pembayar', $user->email ?? '') }}"
                                class="w-full rounded-lg border @error('email_pembayar') border-red-500 ring-1 ring-red-500 @else border-bq-border @enderror bg-bq-background px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-colors focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                placeholder="nama@perusahaan.com" required>
                            <p class="mt-1.5 text-xs text-bq-text-subtle">Faktur resmi & tanda terima pembayaran akan dikirim ke alamat email ini.</p>
                            @error('email_pembayar')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="hp_pembayar" class="mb-1.5 block text-xs font-semibold text-bq-text uppercase tracking-wider">
                                Nomor WhatsApp <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="hp_pembayar" id="hp_pembayar"
                                value="{{ old('hp_pembayar', $user->nomorhp ?? $tenant->nomorhp ?? '') }}"
                                class="w-full rounded-lg border @error('hp_pembayar') border-red-500 ring-1 ring-red-500 @else border-bq-border @enderror bg-bq-background px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-colors focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                placeholder="081234567890" required>
                            <p class="mt-1.5 text-xs text-bq-text-subtle">Digunakan untuk konfirmasi aktivasi instan dan pengingat masa aktif langganan.</p>
                            @error('hp_pembayar')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Security Trust Note --}}
                <div class="flex items-center gap-2 px-1 text-xs text-bq-text-subtle">
                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Informasi penagihan Anda aman dan dilindungi enkripsi SSL 256-bit standar industri perbankan.</span>
                </div>

                {{-- Submit Button (Mobile) --}}
                <div class="lg:hidden pt-2">
                    <button type="submit" id="btn-checkout-submit-mobile"
                        class="w-full rounded-xl bg-bq-primary py-3.5 text-sm font-bold text-white shadow-lg shadow-bq-primary/30 transition-all hover:bg-bq-primary-hover hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting">
                        <span x-show="!isSubmitting" class="flex items-center justify-center gap-2">
                            <span>Lanjut ke Pembayaran →</span>
                        </span>
                        <span x-show="isSubmitting" class="flex items-center justify-center gap-2" style="display: none;">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Memproses Pesanan...</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right Column: Selected Plan & Payment Summary --}}
        <div class="lg:col-span-2 space-y-4">

            @php
                $tierName = strtolower($plan->namapaket);
                $tierBadge = match($tierName) {
                    'pro' => [
                        'badgeClass' => 'bg-purple-100 text-purple-800 border-purple-200',
                        'iconBg' => 'bg-purple-500/10 text-purple-600',
                        'cardBorder' => 'border-purple-200/90 shadow-purple-500/5',
                        'label' => 'PRO TIER',
                    ],
                    'medium' => [
                        'badgeClass' => 'bg-amber-100 text-amber-800 border-amber-300',
                        'iconBg' => 'bg-amber-500/10 text-amber-600',
                        'cardBorder' => 'border-amber-200/90 shadow-amber-500/5',
                        'label' => 'POPULER',
                    ],
                    default => [
                        'badgeClass' => 'bg-slate-100 text-indigo-700 border-slate-200',
                        'iconBg' => 'bg-slate-100 text-indigo-600',
                        'cardBorder' => 'border-slate-200/90 shadow-sm',
                        'label' => 'STARTER',
                    ],
                };
            @endphp

            {{-- Plan Summary Card --}}
            <div class="rounded-xl border {{ $tierBadge['cardBorder'] }} bg-bq-surface p-5 space-y-4 shadow-sm">
                
                {{-- Header with Dynamic Tier Badge --}}
                <div class="flex items-center justify-between border-b border-bq-border pb-3">
                    <div>
                        <h2 class="text-base font-bold text-bq-text">Ringkasan Pesanan</h2>
                        <p class="text-xs text-bq-text-muted">Review paket yang dipilih</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $tierBadge['badgeClass'] }}">
                        @if ($tierName === 'pro')
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
                        @elseif ($tierName === 'medium')
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @else
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                        {{ $tierBadge['label'] }}
                    </span>
                </div>

                {{-- Selected Plan Name & Price Tag --}}
                <div class="flex items-center gap-3 rounded-lg border border-bq-border/60 bg-bq-background p-3.5">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tierBadge['iconBg'] }} shrink-0">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-bq-text-subtle">Paket Terpilih</p>
                        <h3 class="text-base font-bold text-bq-text capitalize">Paket {{ ucfirst($plan->namapaket) }}</h3>
                        <p class="text-xs text-bq-text-muted">Rp {{ number_format($plan->hargabulanan, 0, ',', '.') }} / bulan</p>
                    </div>
                </div>

                {{-- Plan Features List --}}
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-bq-text-subtle">Fitur Utama Paket</p>
                    <ul class="space-y-2 text-xs text-bq-text">
                        <li class="flex items-center gap-2">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shrink-0">
                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>{{ $plan->maxlayanan > 0 ? $plan->maxlayanan . ' layanan aktif' : 'Unlimited layanan katalog' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shrink-0">
                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>{{ ($plan->isunlimited || $plan->maxbooking == 0) ? 'Unlimited booking/bulan' : 'Hingga ' . number_format($plan->maxbooking, 0, ',', '.') . ' booking / bulan' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shrink-0">
                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>{{ $tierName === 'pro' ? 'Unlimited staf & operator' : ($tierName === 'medium' ? 'Hingga 15 akun staf' : '1 admin + 2 staf') }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shrink-0">
                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span>{{ $tierName === 'pro' ? 'Multi-cabang & custom domain' : ($tierName === 'medium' ? 'Laporan analytics & fitur CRM' : 'Online booking & e-invoice') }}</span>
                        </li>
                    </ul>
                </div>

                {{-- Subscription Period --}}
                <div class="flex items-center gap-3 rounded-lg border border-bq-border/60 bg-bq-background p-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-bq-text-subtle">Masa Aktif Langganan</p>
                        <p class="text-xs font-bold text-bq-text">
                            1 Bulan ({{ now()->format('d M Y') }} — {{ now()->addMonth()->format('d M Y') }})
                        </p>
                        <p class="text-[11px] text-bq-text-subtle">Berlaku selama 1 bulan kalender penuh</p>
                    </div>
                </div>

                {{-- Transparent Price Breakdown --}}
                <div class="border-t border-bq-border pt-4 space-y-2.5">
                    <div class="flex justify-between text-xs text-bq-text-muted">
                        <span>Harga Paket (1 Bulan)</span>
                        <span class="font-medium text-bq-text">Rp {{ number_format($plan->hargabulanan, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-bq-text-muted">
                        <span>Biaya Layanan</span>
                        @if (($biayaPlatform ?? 0) > 0)
                            <span class="font-medium text-bq-text">Rp {{ number_format($biayaPlatform, 0, ',', '.') }}</span>
                        @else
                            <span class="font-semibold text-emerald-600">Rp 0 (Gratis)</span>
                        @endif
                    </div>
                    <div class="border-t border-dashed border-bq-border pt-3 flex items-baseline justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-bq-text">Total Pembayaran</span>
                            <p class="text-[11px] text-bq-text-subtle">Sudah termasuk pajak &amp; biaya sistem</p>
                        </div>
                        <span class="text-xl font-extrabold text-bq-primary">
                            Rp {{ number_format($plan->hargabulanan + ($biayaPlatform ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Trust Badges --}}
                <div class="rounded-lg bg-slate-50 border border-slate-200/80 p-3 space-y-2 text-xs">
                    <div class="flex items-start gap-2 text-slate-700">
                        <svg class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <span class="font-semibold text-slate-800">Aktivasi Instan Otomatis</span>
                            <p class="text-[11px] text-slate-500">Fitur paket langsung aktif saat pembayaran terverifikasi.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2 text-slate-700">
                        <svg class="h-4 w-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <div>
                            <span class="font-semibold text-slate-800">Transaksi Aman &amp; Terenkripsi 256-bit SSL</span>
                            <p class="text-[11px] text-slate-500">Dilindungi enkripsi perbankan &amp; payment gateway resmi BI.</p>
                        </div>
                    </div>
                </div>

                {{-- Primary CTA Submit Button (Desktop) --}}
                <div class="hidden lg:block pt-1">
                    <button type="submit" form="checkout-form" id="btn-checkout-submit"
                        class="w-full rounded-xl bg-bq-primary py-3.5 text-sm font-bold text-white shadow-lg shadow-bq-primary/30 transition-all hover:bg-bq-primary-hover hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting">
                        <span x-show="!isSubmitting" class="flex items-center justify-center gap-2">
                            <span>Lanjut ke Pembayaran →</span>
                        </span>
                        <span x-show="isSubmitting" class="flex items-center justify-center gap-2" style="display: none;">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Memproses Pesanan...</span>
                        </span>
                    </button>
                </div>
            </div>

            {{-- Back to Subscription Link --}}
            <a href="{{ route('owner.subscription') }}" class="inline-flex items-center gap-2 text-sm font-medium text-bq-text-muted transition-colors hover:text-bq-primary" id="btn-back-subscription">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <span>Kembali ke Subscription</span>
            </a>
        </div>
    </div>
</div>

<script>
function checkoutPage() {
    return {
        isSubmitting: false,
    };
}
</script>
@endsection
