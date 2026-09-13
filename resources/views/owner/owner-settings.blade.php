@extends('layouts.owner-layout')

@section('title', 'Settings')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    @if (session('sukses'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <p class="font-semibold">{{ session('sukses') }}</p>
        </div>
    @endif
    @if (session('pesan'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold">{{ session('pesan') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-semibold">Periksa kembali input Anda.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('components.owner.page-header', [
        'judul' => 'Settings',
        'subjudul' => 'Manage your business profile and preferences.',
    ])

    {{-- Business Profile --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="business-profile">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-semibold text-bq-text">Business Profile</h2>
            <p class="text-sm text-bq-text-muted">Update your business information visible to customers.</p>
        </div>
        <form class="space-y-5 p-6" method="POST" action="/owner/settings/profile" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Business Name</label>
                    <input type="text" name="namabisnis" value="{{ $tenant->namabisnis }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-namabisnis">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Business Type</label>
                    <input type="text" name="jenisbisnis" value="{{ $tenant->jenisbisnis }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-jenisbisnis">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Slug (URL)</label>
                <div class="flex items-center rounded-lg border border-bq-border bg-bq-background">
                    <span class="px-3 text-sm text-bq-text-muted">bookqu.com/</span>
                    <input type="text" value="{{ $tenant->slug }}" class="w-full border-0 bg-transparent px-1 py-2.5 text-sm text-bq-text focus:outline-none" id="input-slug" disabled>
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Address</label>
                <textarea rows="2" name="alamat" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-alamat">{{ $tenant->alamat }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Description</label>
                <textarea rows="3" name="deskripsi" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-deskripsi">{{ $tenant->deskripsi }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Phone Number</label>
                    <input type="text" name="nomorhp" value="{{ $tenant->nomorhp }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-nomorhp">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Logo</label>
                    <input type="file" name="logo" accept="image/*" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text" id="input-logo">
                    @if ($tenant->logo_path)
                        <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo" class="mt-2 h-12 w-12 rounded-lg object-cover">
                    @endif
                </div>
            </div>
            <div class="flex justify-end border-t border-bq-border pt-5">
                <button type="submit" class="rounded-lg bg-bq-primary px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5" id="btn-save-profile">Save Changes</button>
            </div>
        </form>
    </div>

    {{-- Account Settings --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="account-settings">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-semibold text-bq-text">Account Settings</h2>
            <p class="text-sm text-bq-text-muted">Manage your personal account details.</p>
        </div>
        <form class="space-y-5 p-6" method="POST" action="/owner/settings/account">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Full Name</label>
                    <input type="text" name="namalengkap" value="{{ $tenant->user->namalengkap ?? '' }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-namalengkap">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Email</label>
                    <input type="email" name="email" value="{{ $tenant->user->email ?? '' }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-email">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Phone Number</label>
                    <input type="text" name="nomorhp" value="{{ $tenant->user->nomorhp ?? '' }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-account-nomorhp">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Role</label>
                    <input type="text" value="{{ $tenant->user->role ?? '' }}" disabled class="w-full rounded-lg border border-bq-border bg-bq-background px-4 py-2.5 text-sm text-bq-text-muted cursor-not-allowed" id="input-role">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Change Password</label>
                    <input type="password" name="password" placeholder="Enter new password" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-password">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Confirm Password</label>
                    <input type="password" name="password_confirmation" placeholder="Confirm new password" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-password-confirmation">
                </div>
            </div>
            <div class="flex justify-end border-t border-bq-border pt-5">
                <button type="submit" class="rounded-lg bg-bq-primary px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5" id="btn-save-account">Update Account</button>
            </div>
        </form>
    </div>

    {{-- Payment Settings --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="payment-settings">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-semibold text-bq-text">Payment Settings</h2>
            <p class="text-sm text-bq-text-muted">Pilih mode pembayaran dan isi kredensial Midtrans jika diperlukan.</p>
        </div>
        <form class="space-y-5 p-6" method="POST" action="/owner/settings/payment">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Payment Mode</label>
                    <select name="payment_mode" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
                        <option value="platform" {{ ($tenant->payment_mode ?? 'platform') === 'platform' ? 'selected' : '' }}>Platform (Payout end of day)</option>
                        <option value="owner" {{ ($tenant->payment_mode ?? '') === 'owner' ? 'selected' : '' }}>Owner Midtrans</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Midtrans Status</label>
                    <input type="text" value="{{ $tenant->midtrans_status ?? 'pending' }}" disabled class="w-full rounded-lg border border-bq-border bg-bq-background px-4 py-2.5 text-sm text-bq-text-muted">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Environment</label>
                <select name="midtrans_environment" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
                    <option value="sandbox" {{ ($tenant->midtrans_environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                    <option value="production" {{ ($tenant->midtrans_environment ?? '') === 'production' ? 'selected' : '' }}>Production</option>
                </select>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Sandbox Merchant ID</label>
                    <input type="text" name="midtrans_sandbox_merchant_id" value="{{ $tenant->midtrans_sandbox_merchant_id }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Sandbox Client Key</label>
                    <input type="text" name="midtrans_sandbox_client_key" value="{{ $tenant->midtrans_sandbox_client_key }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Sandbox Server Key</label>
                <input type="text" name="midtrans_sandbox_server_key" value="{{ $tenant->midtrans_sandbox_server_key ? '********' : '' }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Production Merchant ID</label>
                    <input type="text" name="midtrans_prod_merchant_id" value="{{ $tenant->midtrans_prod_merchant_id }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Production Client Key</label>
                    <input type="text" name="midtrans_prod_client_key" value="{{ $tenant->midtrans_prod_client_key }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Production Server Key</label>
                <input type="text" name="midtrans_prod_server_key" value="{{ $tenant->midtrans_prod_server_key ? '********' : '' }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text">
            </div>
            <div class="flex justify-end border-t border-bq-border pt-5">
                <button type="submit" class="rounded-lg bg-bq-primary px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5" id="btn-save-payment">Save Payment Settings</button>
            </div>
        </form>
    </div>

    {{-- Payout --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="payout-settings">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-semibold text-bq-text">Payout & Withdraw</h2>
            <p class="text-sm text-bq-text-muted">Saldo platform dan riwayat withdraw.</p>
        </div>
        <div class="space-y-5 p-6">
            <div class="flex items-center justify-between rounded-lg border border-bq-border bg-bq-background px-4 py-3">
                <div>
                    <p class="text-xs text-bq-text-muted">Saldo Platform</p>
                    <p class="text-lg font-bold text-bq-text">Rp {{ number_format($tenant->saldo_platform ?? 0, 0, ',', '.') }}</p>
                </div>
                <form method="POST" action="/owner/payouts" class="flex items-center gap-2">
                    @csrf
                    <input type="number" name="jumlah" min="10000" step="1000" placeholder="10000" class="w-32 rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm">
                    <button type="submit" class="rounded-lg bg-bq-primary px-4 py-2 text-xs font-semibold text-white">Request Withdraw</button>
                </form>
            </div>

            <div>
                <p class="text-sm font-semibold text-bq-text">Recent Requests</p>
                <div class="mt-3 space-y-2">
                    @forelse ($payouts as $payout)
                        <div class="flex items-center justify-between rounded-lg border border-bq-border bg-bq-background px-3 py-2 text-xs">
                            <div>
                                <p class="font-semibold text-bq-text">Rp {{ number_format($payout->jumlah, 0, ',', '.') }}</p>
                                <p class="text-bq-text-muted">{{ $payout->requested_at->format('d M Y H:i') }}</p>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase
                                {{ $payout->status === 'success' ? 'bg-emerald-50 text-emerald-700' : ($payout->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ $payout->status }}
                            </span>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-bq-border bg-bq-background px-3 py-4 text-center text-xs text-bq-text-muted">
                            Belum ada permintaan withdraw.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Notification Preferences --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" x-data="{ emailnotif: true, smsnotif: false, bookingnotif: true, paymentnotif: true }" id="notification-settings">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-semibold text-bq-text">Notification Preferences</h2>
            <p class="text-sm text-bq-text-muted">Choose how you want to be notified.</p>
        </div>
        <div class="divide-y divide-bq-border">
            @php
                $notifitems = [
                    ['var' => 'emailnotif', 'judul' => 'Email Notifications', 'deskripsi' => 'Receive booking updates via email'],
                    ['var' => 'smsnotif', 'judul' => 'SMS Notifications', 'deskripsi' => 'Get SMS alerts for important events'],
                    ['var' => 'bookingnotif', 'judul' => 'New Booking Alerts', 'deskripsi' => 'Notify when a customer makes a booking'],
                    ['var' => 'paymentnotif', 'judul' => 'Payment Alerts', 'deskripsi' => 'Notify when a payment is received'],
                ];
            @endphp
            @foreach ($notifitems as $notif)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <p class="text-sm font-medium text-bq-text">{{ $notif['judul'] }}</p>
                        <p class="text-xs text-bq-text-muted">{{ $notif['deskripsi'] }}</p>
                    </div>
                    <button
                        @click="{{ $notif['var'] }} = !{{ $notif['var'] }}"
                        :class="{{ $notif['var'] }} ? 'bg-bq-primary' : 'bg-bq-border-strong'"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200"
                    >
                        <span :class="{{ $notif['var'] }} ? 'translate-x-5' : 'translate-x-0.5'" class="pointer-events-none mt-0.5 inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Danger Zone --}}
    <div class="rounded-xl border border-rose-200 bg-rose-50/50" id="danger-zone">
        <div class="px-6 py-4">
            <h2 class="text-base font-semibold text-rose-700">Danger Zone</h2>
            <p class="text-sm text-rose-600/70">Irreversible actions for your account.</p>
        </div>
        <div class="border-t border-rose-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-bq-text">Delete Business Account</p>
                    <p class="text-xs text-bq-text-muted">Permanently delete your business and all data.</p>
                </div>
                <button class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-600 transition-all hover:bg-rose-600 hover:text-white hover:border-rose-600" id="btn-delete-account">Delete Account</button>
            </div>
        </div>
    </div>

</div>
@endsection
