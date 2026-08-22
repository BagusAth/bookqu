@extends('layouts.owner-layout')
@section('title', 'Landing Page Builder')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-bq-text" id="page-title">Landing Page</h1>
            <p class="mt-1 text-sm text-bq-text-muted">Buat dan kelola landing page untuk bisnis Anda</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2 text-xs font-semibold text-bq-text-muted cursor-not-allowed" id="btn-landing-builder" disabled>
                Akses Builder (Coming Soon)
            </button>
            <span class="inline-flex items-center rounded-full bg-violet-500/15 px-3 py-1 text-xs font-bold uppercase tracking-wide text-violet-400">
                <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                Pro Feature
            </span>
        </div>
    </div>

    <div class="rounded-xl border border-bq-border bg-bq-surface p-6 shadow-sm">
        <form action="{{ route('owner.landing-page.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            @if(session('pesan'))
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('pesan') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Custom Domain --}}
                <div class="space-y-2">
                    <label for="custom_domain" class="block text-sm font-medium text-bq-text">Custom Domain</label>
                    <p class="text-xs text-bq-text-subtle mb-2">Contoh: booking.klinikku.com (Pastikan Anda sudah mengarahkan DNS ke server ini)</p>
                    <input type="text" name="custom_domain" id="custom_domain" value="{{ old('custom_domain', $tenant->custom_domain) }}" 
                        class="block w-full rounded-lg border-bq-border/50 bg-bq-background py-2.5 text-sm text-bq-text shadow-sm focus:border-bq-primary focus:ring-bq-primary" 
                        placeholder="Contoh: booking.klinikku.com" />
                    @error('custom_domain')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Theme Color --}}
                <div class="space-y-2">
                    <label for="theme_color" class="block text-sm font-medium text-bq-text">Theme Color (Hex)</label>
                    <p class="text-xs text-bq-text-subtle mb-2">Warna utama untuk tombol dan aksen di halaman pemesanan Anda.</p>
                    <div class="flex items-center gap-3">
                        <input type="color" id="color_picker" class="h-10 w-10 cursor-pointer rounded border-0 p-0" 
                               value="{{ old('theme_color', $tenant->theme_color ?? '#4F46E5') }}" />
                        <input type="text" name="theme_color" id="theme_color" value="{{ old('theme_color', $tenant->theme_color ?? '#4F46E5') }}" 
                            class="block w-full rounded-lg border-bq-border/50 bg-bq-background py-2.5 text-sm text-bq-text uppercase shadow-sm focus:border-bq-primary focus:ring-bq-primary" 
                            placeholder="#4F46E5" />
                    </div>
                    @error('theme_color')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Banner --}}
            <div class="space-y-2 pt-4 border-t border-bq-border/50">
                <label for="banner" class="block text-sm font-medium text-bq-text">Banner Halaman Pemesanan</label>
                <p class="text-xs text-bq-text-subtle mb-2">Gambar banner (JPG, PNG) yang akan muncul di atas halaman pemesanan Anda. Maksimal 2MB.</p>
                
                @if($tenant->banner_path)
                    <div class="mb-4 overflow-hidden rounded-lg border border-bq-border/50">
                        <img src="{{ Storage::url($tenant->banner_path) }}" alt="Banner saat ini" class="w-full h-48 object-cover">
                    </div>
                @endif
                
                <input type="file" name="banner" id="banner" accept="image/png, image/jpeg, image/jpg"
                    class="block w-full text-sm text-bq-text-subtle file:mr-4 file:rounded-full file:border-0 file:bg-bq-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-bq-primary hover:file:bg-bq-primary/20" />
                @error('banner')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end pt-6 border-t border-bq-border/50">
                <button type="submit" class="rounded-lg bg-bq-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-bq-primary-hover focus:outline-none focus:ring-2 focus:ring-bq-primary focus:ring-offset-2">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('color_picker');
        const colorInput = document.getElementById('theme_color');

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
    });
</script>
@endsection
