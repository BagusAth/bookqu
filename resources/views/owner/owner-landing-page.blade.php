@extends('layouts.owner-layout')
@section('title', 'Landing Page [PRO]')

@section('content')
@php
    $subscription = $tenant->subscription ?? \App\Models\Subscription::where('idtenant', $tenant->id)->with('plan')->latest()->first();
    $isPro = ($subscription?->plan?->namapaket === 'pro') || ($subscription?->status === 'trial');
@endphp

<div class="mx-auto max-w-7xl space-y-6" x-data="{
    selectedTemplate: 'modern',
    publishStatus: 'published',
    notification: '',
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
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

    @if (!$isPro)
        {{-- ── NON-PRO LOCKED STATE ── --}}
        <div class="rounded-3xl border border-bq-border bg-bq-surface p-8 sm:p-12 text-center shadow-sm max-w-2xl mx-auto space-y-5">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-violet-100 text-violet-600">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700 uppercase tracking-wider">
                    PRO Feature
                </span>
                <h2 class="text-2xl font-black text-bq-text mt-3">Build Your Own Business Landing Page</h2>
                <p class="mt-2 text-sm text-bq-text-muted leading-relaxed max-w-md mx-auto">
                    Kembangkan profil booking bisnis Anda menjadi website landing page mandiri dengan domain custom, galeri visual, testimoni, dan SEO optimal.
                </p>
            </div>
            <div class="pt-2">
                <a href="{{ route('owner.subscription') }}" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-violet-600/30 hover:bg-violet-700 transition">
                    Upgrade to Pro &rarr;
                </a>
            </div>
        </div>
    @else
        {{-- ── PRO USER LANDING PAGE SHELL ── --}}
        {{-- Topbar Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-bq-text" id="page-title">Your Landing Page</h1>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Published Live
                    </span>
                    <span class="rounded-full bg-violet-100 text-violet-700 px-2 py-0.5 text-[10px] font-bold uppercase">Pro Active</span>
                </div>
                <p class="mt-1 text-xs text-bq-text-muted">Template Aktif: <strong class="text-bq-text">Modern Business (Tenant Edition)</strong></p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="/{{ $tenant->slug ?? 'demo' }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-bq-border bg-bq-surface px-3.5 py-2 text-xs font-semibold text-bq-text hover:bg-slate-50 transition">
                    <svg class="h-4 w-4 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview
                </a>
                <button type="button" @click="showToast('Landing page terbaru berhasil dipublikasikan!');" class="inline-flex items-center gap-1.5 rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Publish
                </button>
            </div>
        </div>

        {{-- CMS Step Progression Breadcrumb --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-4 shadow-xs">
            <div class="flex items-center justify-between overflow-x-auto text-xs font-semibold text-bq-text-muted gap-4">
                <span class="text-indigo-600 flex items-center gap-1.5 whitespace-nowrap font-bold">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[10px]">1</span>
                    Select Template
                </span>
                <span class="text-slate-400">&rarr;</span>
                <span class="text-indigo-600 flex items-center gap-1.5 whitespace-nowrap font-bold">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[10px]">2</span>
                    Customize Sections
                </span>
                <span class="text-slate-400">&rarr;</span>
                <span class="text-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-600 text-[10px]">3</span>
                    Edit Content
                </span>
                <span class="text-slate-400">&rarr;</span>
                <span class="text-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-600 text-[10px]">4</span>
                    Preview &amp; Publish
                </span>
            </div>
        </div>

        {{-- ── 1. Select Template Shell ── --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-4">
            <div>
                <h3 class="font-bold text-base text-bq-text">1. Pilih Template Desain</h3>
                <p class="text-xs text-bq-text-muted">Setiap tenant dapat memilih template yang sesuai dengan karakter bisnis.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                {{-- Template 1 --}}
                <div class="rounded-xl border p-4 cursor-pointer transition flex flex-col justify-between"
                     :class="selectedTemplate === 'modern' ? 'border-bq-primary bg-indigo-50/40 ring-2 ring-bq-primary/20' : 'border-bq-border hover:bg-slate-50'"
                     @click="selectedTemplate = 'modern'">
                    <div>
                        <div class="h-28 rounded-lg bg-gradient-to-br from-indigo-900 to-slate-900 p-3 text-white flex flex-col justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-300">Default Pro</span>
                            <p class="font-black text-xs">Modern Business</p>
                        </div>
                        <h4 class="font-bold text-xs text-bq-text mt-3">Modern Business</h4>
                        <p class="text-[11px] text-bq-text-muted mt-1">Struktur profesional dengan hero banner besar, katalog layanan, dan review slider.</p>
                    </div>
                    <span class="mt-3 text-[11px] font-semibold text-indigo-600 block" x-text="selectedTemplate === 'modern' ? '✓ Template Terpilih' : 'Pilih Template'"></span>
                </div>

                {{-- Template 2 --}}
                <div class="rounded-xl border p-4 cursor-pointer transition flex flex-col justify-between"
                     :class="selectedTemplate === 'studio' ? 'border-bq-primary bg-indigo-50/40 ring-2 ring-bq-primary/20' : 'border-bq-border hover:bg-slate-50'"
                     @click="selectedTemplate = 'studio'">
                    <div>
                        <div class="h-28 rounded-lg bg-gradient-to-br from-purple-900 to-rose-950 p-3 text-white flex flex-col justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-300">Creative</span>
                            <p class="font-black text-xs">Creative Studio</p>
                        </div>
                        <h4 class="font-bold text-xs text-bq-text mt-3">Creative Studio</h4>
                        <p class="text-[11px] text-bq-text-muted mt-1">Cocok untuk salon, barber, dan fotografer dengan galeri portofolio interaktif.</p>
                    </div>
                    <span class="mt-3 text-[11px] font-semibold text-indigo-600 block" x-text="selectedTemplate === 'studio' ? '✓ Template Terpilih' : 'Pilih Template'"></span>
                </div>

                {{-- Template 3 --}}
                <div class="rounded-xl border p-4 cursor-pointer transition flex flex-col justify-between"
                     :class="selectedTemplate === 'minimal' ? 'border-bq-primary bg-indigo-50/40 ring-2 ring-bq-primary/20' : 'border-bq-border hover:bg-slate-50'"
                     @click="selectedTemplate = 'minimal'">
                    <div>
                        <div class="h-28 rounded-lg bg-gradient-to-br from-emerald-900 to-teal-950 p-3 text-white flex flex-col justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300">Wellness</span>
                            <p class="font-black text-xs">Minimalist Clinic</p>
                        </div>
                        <h4 class="font-bold text-xs text-bq-text mt-3">Minimalist Clinic</h4>
                        <p class="text-[11px] text-bq-text-muted mt-1">Nuansa bersih, menenangkan, dan fokus pada kejelasan jadwal dokter atau terapis.</p>
                    </div>
                    <span class="mt-3 text-[11px] font-semibold text-indigo-600 block" x-text="selectedTemplate === 'minimal' ? '✓ Template Terpilih' : 'Pilih Template'"></span>
                </div>
            </div>
        </div>

        {{-- ── 2. Customize Sections Checklist ── --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-4">
            <div>
                <h3 class="font-bold text-base text-bq-text">2. Bagian Halaman (Sections)</h3>
                <p class="text-xs text-bq-text-muted">Centang dan sesuaikan urutan section yang ingin Anda tampilkan pada landing page.</p>
            </div>

            <div class="divide-y divide-bq-border rounded-xl border border-bq-border overflow-hidden text-xs">
                <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" checked class="rounded text-bq-primary focus:ring-bq-primary">
                        <div>
                            <span class="font-bold text-bq-text">Hero Section &amp; Tagline</span>
                            <span class="text-bq-text-muted block text-[11px]">Judul utama, deskripsi singkat, tombol Reservasi Sekarang</span>
                        </div>
                    </div>
                    <button type="button" @click="showToast('Editor Hero Section siap dikembangkan di Phase 2.')" class="text-indigo-600 font-semibold hover:underline">Edit Content &rarr;</button>
                </div>

                <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" checked class="rounded text-bq-primary focus:ring-bq-primary">
                        <div>
                            <span class="font-bold text-bq-text">Katalog Layanan Unggulan</span>
                            <span class="text-bq-text-muted block text-[11px]">Otomatis tersinkronisasi dari menu Services &amp; Programs Anda</span>
                        </div>
                    </div>
                    <button type="button" @click="showToast('Editor Layanan Section siap dikembangkan di Phase 2.')" class="text-indigo-600 font-semibold hover:underline">Edit Content &rarr;</button>
                </div>

                <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" checked class="rounded text-bq-primary focus:ring-bq-primary">
                        <div>
                            <span class="font-bold text-bq-text">Ulasan &amp; Testimoni Bintang</span>
                            <span class="text-bq-text-muted block text-[11px]">Testimoni terverifikasi dari customer Anda</span>
                        </div>
                    </div>
                    <button type="button" @click="showToast('Editor Testimoni siap dikembangkan di Phase 2.')" class="text-indigo-600 font-semibold hover:underline">Edit Content &rarr;</button>
                </div>

                <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" checked class="rounded text-bq-primary focus:ring-bq-primary">
                        <div>
                            <span class="font-bold text-bq-text">Peta Lokasi &amp; Kontak WhatsApp</span>
                            <span class="text-bq-text-muted block text-[11px]">Alamat lengkap bisnis dan tautan chat langsung</span>
                        </div>
                    </div>
                    <button type="button" @click="showToast('Editor Kontak siap dikembangkan di Phase 2.')" class="text-indigo-600 font-semibold hover:underline">Edit Content &rarr;</button>
                </div>
            </div>
        </div>

        {{-- ── 3. Custom Domain & Technical Settings Form ── --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-4">
            <div>
                <h3 class="font-bold text-base text-bq-text">3. Pengaturan Domain &amp; Brand Landing Page</h3>
                <p class="text-xs text-bq-text-muted">Sambungkan domain Anda sendiri dan sesuaikan warna aksen.</p>
            </div>

            <form action="{{ route('owner.landing-page.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                @if(session('pesan'))
                    <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-200">
                        <p class="text-xs font-semibold text-emerald-800">{{ session('pesan') }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Custom Domain (Opsional)</label>
                        <p class="text-[11px] text-bq-text-muted mb-1">Arahkan CNAME domain Anda ke server BookQu</p>
                        <input type="text" name="custom_domain" id="custom_domain" value="{{ old('custom_domain', $tenant->custom_domain) }}"
                            placeholder="Contoh: booking.bisnisanda.com"
                            class="w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Theme Accent Color</label>
                        <p class="text-[11px] text-bq-text-muted mb-1">Warna aksen tombol CTA landing page</p>
                        <div class="flex items-center gap-2">
                            <input type="color" id="color_picker" value="{{ old('theme_color', $tenant->theme_color ?? '#4F46E5') }}" class="h-9 w-10 rounded-lg border border-bq-border cursor-pointer p-0.5">
                            <input type="text" name="theme_color" id="theme_color" value="{{ old('theme_color', $tenant->theme_color ?? '#4F46E5') }}"
                                class="w-full uppercase font-mono rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-bq-border">
                    <button type="submit" class="rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
                        Simpan Pengaturan Landing Page
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('color_picker');
        const colorInput = document.getElementById('theme_color');

        if (colorPicker && colorInput) {
            colorPicker.addEventListener('input', function(e) {
                colorInput.value = e.target.value.toUpperCase();
            });

            colorInput.addEventListener('input', function(e) {
                let val = e.target.value;
                if (val && !val.startsWith('#')) {
                    val = '#' + val;
                }
                if (/^#[0-9A-F]{6}$/i.test(val)) {
                    colorPicker.value = val;
                }
            });
        }
    });
</script>
@endsection
@endsection
