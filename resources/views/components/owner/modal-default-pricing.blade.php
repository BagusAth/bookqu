{{-- Default Pricing Modal Component --}}
@props(['daftarlayanan' => collect()])

<div
    x-data="{ buka: false, sedangkirim: false }"
    @open-default-pricing.window="buka = true"
    x-cloak
>
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

    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    >
        <div class="w-full max-w-lg rounded-2xl border border-bq-border bg-bq-surface shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b border-bq-border px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-bq-text">Set Default Pricing</h2>
                    <p class="text-sm text-bq-text-muted">Atur harga dasar per layanan.</p>
                </div>
                <button @click="buka = false" class="rounded-lg p-1.5 text-bq-text-subtle transition-colors hover:bg-bq-background hover:text-bq-text" id="btn-close-default-pricing">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="/owner/schedule/default-pricing" @submit="sedangkirim = true" id="form-default-pricing">
                @csrf
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="input-default-idlayanan" class="mb-1.5 block text-sm font-medium text-bq-text">Program <span class="text-rose-500">*</span></label>
                        <select
                            name="idlayanan"
                            id="input-default-idlayanan"
                            required
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                        >
                            <option value="">Select a program</option>
                            @foreach ($daftarlayanan as $layanan)
                                <option value="{{ $layanan->id }}">{{ $layanan->namalayanan }} — Rp {{ number_format($layanan->harga, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="input-default-harga" class="mb-1.5 block text-sm font-medium text-bq-text">Harga Default (Rp) <span class="text-rose-500">*</span></label>
                        <input
                            type="number"
                            name="harga"
                            id="input-default-harga"
                            min="0"
                            step="1000"
                            required
                            placeholder="150000"
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-bq-border px-6 py-4">
                    <button type="button" @click="buka = false" class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-text transition-all hover:bg-bq-background" id="btn-cancel-default-pricing">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="sedangkirim"
                        :class="sedangkirim ? 'opacity-60 cursor-not-allowed' : 'hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5'"
                        class="inline-flex items-center gap-2 rounded-lg bg-bq-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all"
                        id="btn-submit-default-pricing"
                    >
                        <svg x-show="sedangkirim" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="sedangkirim ? 'Saving...' : 'Save Pricing'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
