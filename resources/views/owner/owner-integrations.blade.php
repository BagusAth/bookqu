@extends('layouts.owner-layout')

@section('title', 'Integrations')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    configModalOpen: false,
    selectedIntegration: null,
    notification: '',
    integrations: [
        {
            id: 'midtrans',
            name: 'Midtrans Payment Gateway',
            category: 'Payments',
            description: 'Memproses pembayaran otomatis menggunakan QRIS, Virtual Account Bank (BCA, Mandiri, BNI, BRI), dan E-Wallet.',
            status: 'connected',
            iconColor: 'bg-blue-50 text-blue-600',
            settings: { clientKey: 'SB-Mid-client-882199', mode: 'Sandbox' }
        },
        {
            id: 'email',
            name: 'Email SMTP & Notifications',
            category: 'Communication',
            description: 'Kirim email invoice, tiket reservasi otomatis, dan konfirmasi reschedule kepada customer.',
            status: 'connected',
            iconColor: 'bg-purple-50 text-purple-600',
            settings: { senderEmail: 'notification@bookqu.com', provider: 'System Mailer' }
        },
        {
            id: 'whatsapp',
            name: 'WhatsApp Automated Reminders',
            category: 'Messaging',
            description: 'Kirim notifikasi pengingat H-1 atau H-2 jam sebelum jadwal sesi reservasi customer via WhatsApp.',
            status: 'disconnected',
            iconColor: 'bg-emerald-50 text-emerald-600',
            settings: { gatewayUrl: '', sessionStatus: 'Not Paired' }
        },
        {
            id: 'gcal',
            name: 'Google Calendar Sync',
            category: 'Calendar',
            description: 'Sinkronisasi dua arah setiap booking yang masuk langsung ke kalender Google pribadi atau kalender tim.',
            status: 'disconnected',
            iconColor: 'bg-amber-50 text-amber-600',
            settings: { calendarId: 'primary' }
        },
        {
            id: 'analytics',
            name: 'Google Analytics & Meta Pixel',
            category: 'Tracking',
            description: 'Lacak kunjungan halaman booking, konversi reservasi, dan optimalkan kampanye iklan digital Anda.',
            status: 'disconnected',
            iconColor: 'bg-indigo-50 text-indigo-600',
            settings: { measurementId: 'G-XXXXXXXX' }
        }
    ],
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
    },
    toggleConnect(item) {
        if (item.status === 'connected') {
            item.status = 'disconnected';
            this.showToast(item.name + ' diputuskan (disconnected).');
        } else {
            item.status = 'connected';
            this.showToast(item.name + ' berhasil dihubungkan!');
        }
    },
    testConnection(item) {
        this.showToast('Menguji koneksi ke ' + item.name + '... Status: OK (200 Response).');
    },
    openConfig(item) {
        this.selectedIntegration = { ...item };
        this.configModalOpen = true;
    },
    saveConfig() {
        const idx = this.integrations.findIndex(i => i.id === this.selectedIntegration.id);
        if (idx !== -1) {
            this.integrations[idx] = { ...this.selectedIntegration };
        }
        this.configModalOpen = false;
        this.showToast('Konfigurasi ' + this.selectedIntegration.name + ' berhasil disimpan.');
    }
}">

    {{-- Toast Notification --}}
    <div x-show="notification"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 rounded-xl bg-slate-900 text-white px-4 py-3 shadow-xl text-xs font-medium flex items-center gap-2"
         style="display: none;">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
        <span x-text="notification"></span>
    </div>

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Integrations & Third-Party Services',
        'subjudul' => 'Hubungkan BookQu dengan Payment Gateway, WhatsApp, Google Calendar, Email, dan Analytics.',
    ])

    {{-- ── Integration Cards Grid ── --}}
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <template x-for="item in integrations" :key="item.id">
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs flex flex-col justify-between hover:border-bq-border-strong hover:shadow-md transition">
                <div>
                    {{-- Top Status Pill --}}
                    <div class="flex items-center justify-between">
                        <span class="rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider"
                              :class="item.status === 'connected' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-500'"
                              x-text="item.status === 'connected' ? 'Connected' : 'Disconnected'"></span>
                        <span class="text-xs text-bq-text-muted font-medium" x-text="item.category"></span>
                    </div>

                    {{-- Icon & Title --}}
                    <div class="mt-4 flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl font-bold" :class="item.iconColor">
                            <template x-if="item.id === 'midtrans'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </template>
                            <template x-if="item.id === 'email'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </template>
                            <template x-if="item.id === 'whatsapp'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </template>
                            <template x-if="item.id === 'gcal'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </template>
                            <template x-if="item.id === 'analytics'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </template>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-bq-text" x-text="item.name"></h4>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="mt-3 text-xs text-bq-text-muted leading-relaxed" x-text="item.description"></p>
                </div>

                {{-- Action Controls --}}
                <div class="mt-6 pt-4 border-t border-bq-border space-y-2">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="toggleConnect(item)"
                            class="flex-1 rounded-xl py-2 text-xs font-semibold transition text-center"
                            :class="item.status === 'connected' ? 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-bq-primary text-white hover:bg-bq-primary-hover'">
                            <span x-text="item.status === 'connected' ? 'Disconnect' : 'Connect'"></span>
                        </button>
                        <button type="button" @click="openConfig(item)" class="rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs font-semibold text-bq-text hover:bg-slate-50 transition" title="Settings">
                            Config
                        </button>
                    </div>
                    <button type="button" @click="testConnection(item)" class="w-full text-center text-[11px] text-bq-text-muted hover:text-indigo-600 font-medium transition py-1">
                        Test Connection &rarr;
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Configuration Modal --}}
    <div x-show="configModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="configModalOpen = false">
            <template x-if="selectedIntegration">
                <div>
                    <h3 class="text-base font-bold text-bq-text" x-text="'Konfigurasi ' + selectedIntegration.name"></h3>
                    <p class="text-xs text-bq-text-muted mt-1">Sesuaikan kredensial dan endpoint koneksi pihak ketiga.</p>
                    
                    <form @submit.prevent="saveConfig()" class="mt-4 space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-bq-text">Status Koneksi</label>
                            <select x-model="selectedIntegration.status" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text">
                                <option value="connected">Connected (Aktif)</option>
                                <option value="disconnected">Disconnected (Non-Aktif)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-bq-text">Endpoint / API Key Identitas</label>
                            <input type="text" value="https://api.bookqu.com/v1/webhook" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs font-mono text-bq-text-muted bg-slate-50" readonly>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                            <button type="button" @click="configModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                            <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Konfigurasi</button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
