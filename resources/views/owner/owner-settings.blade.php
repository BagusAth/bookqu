@extends('layouts.owner-layout')

@section('title', 'Settings')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

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
        <div class="space-y-5 p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Business Name</label>
                    <input type="text" value="{{ $tenant->namabisnis }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-namabisnis">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Business Type</label>
                    <input type="text" value="{{ $tenant->jenisbisnis }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-jenisbisnis">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Slug (URL)</label>
                <div class="flex items-center rounded-lg border border-bq-border bg-bq-background">
                    <span class="px-3 text-sm text-bq-text-muted">bookqu.com/</span>
                    <input type="text" value="{{ $tenant->slug }}" class="w-full border-0 bg-transparent px-1 py-2.5 text-sm text-bq-text focus:outline-none" id="input-slug">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Address</label>
                <textarea rows="2" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-alamat">{{ $tenant->alamat }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Phone Number</label>
                    <input type="text" value="{{ $tenant->nomorhp }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-nomorhp">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Email</label>
                    <input type="email" value="{{ $tenant->user->email ?? '' }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-email">
                </div>
            </div>
            <div class="flex justify-end border-t border-bq-border pt-5">
                <button class="rounded-lg bg-bq-primary px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5" id="btn-save-profile">Save Changes</button>
            </div>
        </div>
    </div>

    {{-- Account Settings --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface" id="account-settings">
        <div class="border-b border-bq-border px-6 py-4">
            <h2 class="text-base font-semibold text-bq-text">Account Settings</h2>
            <p class="text-sm text-bq-text-muted">Manage your personal account details.</p>
        </div>
        <div class="space-y-5 p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Full Name</label>
                    <input type="text" value="{{ $tenant->user->namalengkap ?? '' }}" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-namalengkap">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-bq-text">Role</label>
                    <input type="text" value="{{ $tenant->user->role ?? '' }}" disabled class="w-full rounded-lg border border-bq-border bg-bq-background px-4 py-2.5 text-sm text-bq-text-muted cursor-not-allowed" id="input-role">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-bq-text">Change Password</label>
                <input type="password" placeholder="Enter new password" class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20" id="input-password">
            </div>
            <div class="flex justify-end border-t border-bq-border pt-5">
                <button class="rounded-lg bg-bq-primary px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5" id="btn-save-account">Update Account</button>
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
