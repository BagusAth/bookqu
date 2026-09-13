@extends('layouts.owner-layout')

@section('title', 'Appearance & Branding')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    primaryColor: '{{ $tenant->theme_color ?? '#4F46E5' }}',
    buttonStyle: '{{ $tenant->button_style ?? 'rounded-xl' }}',
    fontFamily: '{{ $tenant->font_family ?? 'Plus Jakarta Sans' }}',
    cardStyle: '{{ $tenant->card_style ?? 'elevated' }}',
    layoutStyle: 'grid',
    businessName: '{{ addslashes($tenant->namabisnis ?? 'My Business') }}',
    businessDesc: '{{ addslashes($tenant->deskripsi ?? 'Reservasi online cepat dan mudah.') }}',
    presetColors: ['#4F46E5', '#2563EB', '#0D9488', '#059669', '#E11D48', '#7C3AED', '#111827']
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
        'judul' => 'Appearance & Booking Theme',
        'subjudul' => 'Kustomisasi identitas visual, warna brand, dan gaya tampilan halaman booking publik Anda.',
    ])

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        {{-- ── LEFT: Settings Controls (7 cols) ── --}}
        <form method="POST" action="{{ route('owner.settings.appearance.update') }}" class="space-y-6 lg:col-span-7" id="form-appearance-settings">
            @csrf
            <input type="hidden" name="theme_color" :value="primaryColor">
            <input type="hidden" name="button_style" :value="buttonStyle">
            <input type="hidden" name="font_family" :value="fontFamily">
            <input type="hidden" name="card_style" :value="cardStyle">
            <input type="hidden" name="deskripsi" :value="businessDesc">

            {{-- Branding & Colors --}}
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-5">
                <h3 class="text-base font-bold text-bq-text">Warna Primer &amp; Brand Palette</h3>
                <p class="text-xs text-bq-text-muted">Warna ini diterapkan pada tombol utama, badge aktif, dan highlight halaman booking publik.</p>

                <div class="flex items-center gap-3">
                    <input type="color" x-model="primaryColor" class="h-10 w-12 rounded-lg border border-bq-border cursor-pointer p-0.5">
                    <input type="text" x-model="primaryColor" class="w-28 uppercase font-mono text-xs rounded-xl border border-bq-border px-3 py-2 text-bq-text focus:outline-none">
                    
                    <div class="flex items-center gap-1.5 ml-2">
                        <template x-for="c in presetColors" :key="c">
                            <button type="button" @click="primaryColor = c"
                                class="h-7 w-7 rounded-full border-2 transition transform hover:scale-110"
                                :style="'background-color: ' + c"
                                :class="primaryColor.toLowerCase() === c.toLowerCase() ? 'border-slate-900 scale-110 shadow-sm' : 'border-white'"></button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Typography & Font --}}
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-bq-text">Tipografi (Font Family)</h3>
                <p class="text-xs text-bq-text-muted">Pilih jenis font yang selaras dengan persona dan bidang usaha Anda.</p>

                <div class="grid grid-cols-2 gap-3">
                    <template x-for="f in ['Plus Jakarta Sans', 'Inter', 'Outfit', 'Playfair Display']" :key="f">
                        <button type="button" @click="fontFamily = f"
                            class="p-3.5 rounded-xl border text-left transition"
                            :class="fontFamily === f ? 'border-bq-primary bg-indigo-50/50 ring-1 ring-bq-primary' : 'border-bq-border bg-bq-surface hover:bg-slate-50'">
                            <span class="font-bold text-xs text-bq-text" :style="'font-family: ' + f" x-text="f"></span>
                            <span class="block text-[11px] text-bq-text-muted mt-0.5" :style="'font-family: ' + f">BookQu Online Reservation</span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Button & Card Styles --}}
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-5">
                <h3 class="text-base font-bold text-bq-text">Gaya Tombol &amp; Card Layanan</h3>
                
                {{-- Button Radius --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-bq-text">Bentuk Sudut Tombol (Border Radius)</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" @click="buttonStyle = 'rounded-md'" class="py-2.5 px-3 rounded-md border text-xs font-semibold text-center transition" :class="buttonStyle === 'rounded-md' ? 'border-bq-primary bg-indigo-50 text-indigo-700' : 'border-bq-border hover:bg-slate-50'">
                            Square (6px)
                        </button>
                        <button type="button" @click="buttonStyle = 'rounded-xl'" class="py-2.5 px-3 rounded-xl border text-xs font-semibold text-center transition" :class="buttonStyle === 'rounded-xl' ? 'border-bq-primary bg-indigo-50 text-indigo-700' : 'border-bq-border hover:bg-slate-50'">
                            Rounded (12px)
                        </button>
                        <button type="button" @click="buttonStyle = 'rounded-full'" class="py-2.5 px-3 rounded-full border text-xs font-semibold text-center transition" :class="buttonStyle === 'rounded-full' ? 'border-bq-primary bg-indigo-50 text-indigo-700' : 'border-bq-border hover:bg-slate-50'">
                            Pill Shape
                        </button>
                    </div>
                </div>

                {{-- Card Layout --}}
                <div class="space-y-2 pt-3 border-t border-bq-border">
                    <label class="text-xs font-semibold text-bq-text">Style Card Layanan</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" @click="cardStyle = 'elevated'" class="p-3 rounded-xl border text-xs text-center transition" :class="cardStyle === 'elevated' ? 'border-bq-primary bg-indigo-50 text-indigo-700 font-bold' : 'border-bq-border hover:bg-slate-50'">
                            Elevated (Shadow)
                        </button>
                        <button type="button" @click="cardStyle = 'bordered'" class="p-3 rounded-xl border text-xs text-center transition" :class="cardStyle === 'bordered' ? 'border-bq-primary bg-indigo-50 text-indigo-700 font-bold' : 'border-bq-border hover:bg-slate-50'">
                            Outline Border
                        </button>
                        <button type="button" @click="cardStyle = 'flat'" class="p-3 rounded-xl border text-xs text-center transition" :class="cardStyle === 'flat' ? 'border-bq-primary bg-indigo-50 text-indigo-700 font-bold' : 'border-bq-border hover:bg-slate-50'">
                            Soft Background
                        </button>
                    </div>
                </div>
            </div>

            {{-- Business Description --}}
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-3">
                <h3 class="text-base font-bold text-bq-text">Deskripsi Publik Bisnis</h3>
                <p class="text-xs text-bq-text-muted">Teks singkat tentang bisnis Anda yang tampil di header reservasi.</p>
                <textarea x-model="businessDesc" rows="3" class="w-full rounded-xl border border-bq-border p-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
            </div>

            {{-- Save button --}}
            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-semibold text-white hover:bg-bq-primary-hover shadow-sm transition">
                    Simpan Tampilan
                </button>
            </div>

        </form>

        {{-- ── RIGHT: Live Preview (5 cols) ── --}}
        <div class="space-y-4 lg:col-span-5">
            <div class="sticky top-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-bq-text-muted flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Preview Tampilan Publik
                    </span>
                    <a href="/{{ $tenant->slug ?? 'demo' }}" target="_blank" class="text-xs text-indigo-600 hover:underline">
                        Buka Halaman Penuh &rarr;
                    </a>
                </div>

                {{-- Phone / Mockup Container --}}
                <div class="rounded-3xl border-4 border-slate-800 bg-white shadow-2xl p-4 overflow-hidden" :style="'font-family: ' + fontFamily">
                    
                    {{-- Mini Top Header --}}
                    <div class="h-28 rounded-2xl bg-gradient-to-r from-slate-900 to-indigo-950 p-4 text-white flex flex-col justify-end relative overflow-hidden">
                        <div class="absolute inset-0 bg-cover opacity-20" style="background-image: url('https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=500');"></div>
                        <div class="relative z-10">
                            <span class="text-[10px] uppercase font-bold tracking-widest text-indigo-300">Verified Business</span>
                            <h4 class="font-black text-sm tracking-tight" x-text="businessName"></h4>
                            <p class="text-[10px] text-slate-300 line-clamp-1 mt-0.5" x-text="businessDesc"></p>
                        </div>
                    </div>

                    {{-- Service Cards Mockup --}}
                    <div class="mt-4 space-y-3">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pilih Layanan</p>
                        
                        {{-- Sample Card 1 --}}
                        <div class="p-3.5 transition"
                             :class="{
                                 'rounded-2xl border border-slate-200 shadow-md bg-white': cardStyle === 'elevated',
                                 'rounded-2xl border-2 border-slate-300 bg-white': cardStyle === 'bordered',
                                 'rounded-2xl bg-slate-50 border border-slate-100': cardStyle === 'flat'
                             }">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h5 class="font-bold text-xs text-slate-900">Perawatan Wajah &amp; Glow</h5>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Durasi 60 Menit • Konsultasi Dokter</p>
                                </div>
                                <span class="font-bold text-xs" :style="'color: ' + primaryColor">Rp 250.000</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-[10px] text-slate-400">Tersedia Hari Ini</span>
                                <button type="button"
                                    class="text-[10px] font-bold text-white px-3 py-1.5 shadow-sm transition"
                                    :class="buttonStyle"
                                    :style="'background-color: ' + primaryColor">
                                    Pilih Jadwal
                                </button>
                            </div>
                        </div>

                        {{-- Sample Card 2 --}}
                        <div class="p-3.5 transition"
                             :class="{
                                 'rounded-2xl border border-slate-200 shadow-md bg-white': cardStyle === 'elevated',
                                 'rounded-2xl border-2 border-slate-300 bg-white': cardStyle === 'bordered',
                                 'rounded-2xl bg-slate-50 border border-slate-100': cardStyle === 'flat'
                             }">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h5 class="font-bold text-xs text-slate-900">Hair Spa &amp; Scalp Treatment</h5>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Durasi 45 Menit • Termasuk Wash &amp; Blow</p>
                                </div>
                                <span class="font-bold text-xs" :style="'color: ' + primaryColor">Rp 175.000</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-[10px] text-slate-400">Slot Terbatas</span>
                                <button type="button"
                                    class="text-[10px] font-bold text-white px-3 py-1.5 shadow-sm transition"
                                    :class="buttonStyle"
                                    :style="'background-color: ' + primaryColor">
                                    Pilih Jadwal
                                </button>
                            </div>
                        </div>

                    </div>

                    {{-- Bottom bar --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 text-center text-[10px] text-slate-400">
                        Powered by <span class="font-bold text-slate-600">BookQu</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
