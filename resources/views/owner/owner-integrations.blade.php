@extends('layouts.owner-layout')

@section('title', 'Integrations')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Integrations & Webhooks',
        'subjudul' => 'Hubungkan BookQu dengan WhatsApp Gateway, Midtrans, Google Calendar, Email, dan Webhook pihak ketiga.',
    ])

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="rounded-lg bg-emerald-50 text-emerald-600 px-2 py-1 text-xs font-bold">Connected</span>
                    <span class="text-xs text-bq-text-muted">Core Engine</span>
                </div>
                <h4 class="mt-3 text-base font-bold text-bq-text">Midtrans Payment Gateway</h4>
                <p class="mt-1 text-xs text-bq-text-muted">Memproses QRIS, Virtual Account, dan E-Wallet secara otomatis.</p>
            </div>
            <button class="mt-4 w-full rounded-xl border border-bq-border py-2 text-xs font-semibold text-bq-text hover:bg-bq-background transition">
                Konfigurasi
            </button>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="rounded-lg bg-indigo-50 text-[#4F46E5] px-2 py-1 text-xs font-bold">Ready</span>
                    <span class="text-xs text-bq-text-muted">Notifikasi</span>
                </div>
                <h4 class="mt-3 text-base font-bold text-bq-text">WhatsApp Notifications</h4>
                <p class="mt-1 text-xs text-bq-text-muted">Kirim pengingat otomatis ke WhatsApp customer sebelum jadwal sesi.</p>
            </div>
            <button class="mt-4 w-full rounded-xl bg-[#4F46E5] py-2 text-xs font-semibold text-white hover:bg-[#4338CA] transition">
                Hubungkan WhatsApp
            </button>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="rounded-lg bg-slate-100 text-slate-600 px-2 py-1 text-xs font-bold">Coming Soon</span>
                    <span class="text-xs text-bq-text-muted">Kalender</span>
                </div>
                <h4 class="mt-3 text-base font-bold text-bq-text">Google Calendar Sync</h4>
                <p class="mt-1 text-xs text-bq-text-muted">Sinkronisasikan booking customer langsung ke Google Calendar tim.</p>
            </div>
            <button class="mt-4 w-full rounded-xl border border-bq-border py-2 text-xs font-semibold text-slate-400 cursor-not-allowed" disabled>
                Segera Hadir
            </button>
        </div>
    </div>
</div>
@endsection
