@php
    $profileErrors = old('_form') === 'complete-profile' ? $errors : new \Illuminate\Support\ViewErrorBag;
@endphp
<div
    x-data="{
        buka: @js($profileErrors->any() || ($showProfilePrompt ?? true)),
        sedangkirim: false, 
        namabisnis: @js(old('namabisnis', auth()->user()?->tenant?->namabisnis ?? ''))
    }"
    @open-complete-profile.window="buka = true"
    x-cloak
>
    {{-- Overlay --}}
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm"
        @click="buka = false"
    ></div>

    {{-- Modal --}}
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
        <div class="w-full max-w-lg bq-panel shadow-2xl flex flex-col max-h-[calc(100vh-2rem)] my-auto overflow-hidden" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-bq-border px-5 py-3.5 sm:px-6 sm:py-4 shrink-0 bg-bq-surface">
                <div>
                    <h2 class="text-base sm:text-lg font-semibold text-bq-text">Lengkapi Profil Bisnis</h2>
                    <p class="text-xs sm:text-sm text-bq-text-muted">Isi data bisnis Anda agar akun siap digunakan.</p>
                </div>
                <button @click="buka = false" class="rounded-lg p-1.5 text-bq-text-subtle transition-colors hover:bg-bq-surface-2 hover:text-bq-text cursor-pointer" id="btn-close-complete-profile">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('owner.profile.complete') }}" @submit="sedangkirim = true" id="form-complete-profile" class="flex flex-col min-h-0 flex-1">
                @csrf
                <input type="hidden" name="_form" value="complete-profile">
                <div class="space-y-4 px-5 py-4 sm:px-6 sm:py-5 overflow-y-auto flex-1">
                    @if ($profileErrors->any())
                        <div class="rounded-xl border border-bq-danger/50 bg-bq-danger-bg px-4 py-3 text-xs text-bq-danger">
                            <p class="font-semibold text-rose-800">Mohon perbaiki isian form:</p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-4">
                                @foreach ($profileErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label for="input-complete-namabisnis" class="mb-1.5 block text-sm font-medium text-bq-text">Nama Bisnis <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            name="namabisnis"
                            id="input-complete-namabisnis"
                            x-model="namabisnis"
                            value="{{ old('namabisnis', auth()->user()?->tenant?->namabisnis ?? '') }}"
                            required
                            placeholder="Contoh: Yoga Studio" 
                            class="bq-input w-full" @if ($profileErrors->has('namabisnis')) aria-invalid="true" @endif
                        >
                        @error('namabisnis')
                            <p class="mt-1 text-xs text-bq-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="input-complete-jenisbisnis" class="mb-1.5 block text-sm font-medium text-bq-text">Jenis Bisnis <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            name="jenisbisnis"
                            id="input-complete-jenisbisnis"
                            value="{{ old('jenisbisnis', auth()->user()?->tenant?->jenisbisnis ?? '') }}"
                            required
                            placeholder="Contoh: Studio Yoga" 
                            class="bq-input w-full" @if ($profileErrors->has('jenisbisnis')) aria-invalid="true" @endif
                        >
                        @error('jenisbisnis')
                            <p class="mt-1 text-xs text-bq-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="input-complete-nomorhp" class="mb-1.5 block text-sm font-medium text-bq-text">Nomor Telepon <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            name="nomorhp"
                            id="input-complete-nomorhp"
                            value="{{ old('nomorhp', auth()->user()?->tenant?->nomorhp ?? auth()->user()?->nomorhp ?? '') }}"
                            required
                            placeholder="08xxxxxxxxxx" 
                            class="bq-input w-full" @if ($profileErrors->has('nomorhp')) aria-invalid="true" @endif
                        >
                        @error('nomorhp')
                            <p class="mt-1 text-xs text-bq-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="input-complete-alamat" class="mb-1.5 block text-sm font-medium text-bq-text">Alamat <span class="text-rose-500">*</span></label>
                        <textarea
                            name="alamat"
                            id="input-complete-alamat"
                            rows="2"
                            placeholder="Alamat bisnis" 
                            required
                            class="bq-input w-full resize-none" @if ($profileErrors->has('alamat')) aria-invalid="true" @endif
                        >{{ old('alamat', auth()->user()?->tenant?->alamat ?? '') }}</textarea>
                        @error('alamat')
                            <p class="mt-1 text-xs text-bq-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-lg border border-bq-border bg-bq-background px-4 py-3">
                        <p class="text-xs text-bq-text-muted">URL bisnis akan dibuat otomatis berdasarkan nama bisnis.</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2.5 sm:gap-3 border-t border-bq-border px-5 py-3 sm:px-6 sm:py-4 shrink-0 bg-bq-surface">
                    <button type="button" @click="buka = false" class="bq-btn bq-btn-secondary bq-btn-sm cursor-pointer" id="btn-cancel-complete-profile">
                        Nanti Saja
                    </button>
                    <button
                        type="submit"
                        :disabled="sedangkirim"
                        :class="sedangkirim ? 'opacity-60 cursor-not-allowed' : 'hover:bg-bq-primary-hover hover:shadow-bq hover:-translate-y-0.5 active:scale-95'"
                        class="bq-btn bq-btn-primary bq-btn-sm cursor-pointer"
                        id="btn-submit-complete-profile"
                    >
                        <svg x-show="sedangkirim" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="sedangkirim ? 'Menyimpan...' : 'Simpan Profil'">Simpan Profil</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
