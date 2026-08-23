{{-- Configure Availability Modal Component --}}
@props(['blockedDates' => collect(), 'tenant' => null])

<div
    x-data="{ buka: false, sedangkirim: false }"
    @open-configure-availability.window="buka = true"
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
        <div class="w-full max-w-2xl rounded-2xl border border-bq-border bg-bq-surface shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b border-bq-border px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-bq-text">Configure Availability</h2>
                    <p class="text-sm text-bq-text-muted">Blok tanggal tertentu dan atur harga akhir pekan.</p>
                </div>
                <button @click="buka = false" class="rounded-lg p-1.5 text-bq-text-subtle transition-colors hover:bg-bq-background hover:text-bq-text" id="btn-close-configure-availability">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="/owner/schedule/availability" @submit="sedangkirim = true" id="form-configure-availability">
                @csrf
                <div class="space-y-5 px-6 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="input-block-date" class="mb-1.5 block text-sm font-medium text-bq-text">Block Date</label>
                            <input
                                type="date"
                                name="tanggal_block"
                                id="input-block-date"
                                class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                            >
                        </div>
                        <div>
                            <label for="input-block-reason" class="mb-1.5 block text-sm font-medium text-bq-text">Reason</label>
                            <input
                                type="text"
                                name="alasan"
                                id="input-block-reason"
                                placeholder="Maintenance, holiday"
                                class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                            >
                        </div>
                    </div>

                    <div class="rounded-lg border border-bq-border bg-bq-background p-4">
                        <p class="text-sm font-semibold text-bq-text">Weekend Pricing</p>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="input-weekend-type" class="mb-1.5 block text-xs font-medium text-bq-text">Type</label>
                                <select
                                    name="weekend_price_type"
                                    id="input-weekend-type"
                                    class="w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                >
                                    <option value="none" {{ ($tenant?->weekend_price_type ?? 'none') === 'none' ? 'selected' : '' }}>No adjustment</option>
                                    <option value="multiplier" {{ ($tenant?->weekend_price_type ?? '') === 'multiplier' ? 'selected' : '' }}>Multiplier</option>
                                    <option value="fixed" {{ ($tenant?->weekend_price_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed price</option>
                                </select>
                            </div>
                            <div>
                                <label for="input-weekend-value" class="mb-1.5 block text-xs font-medium text-bq-text">Value</label>
                                <input
                                    type="number"
                                    name="weekend_price_value"
                                    id="input-weekend-value"
                                    min="0"
                                    step="0.01"
                                    value="{{ $tenant?->weekend_price_value }}"
                                    placeholder="1.2 or 200000"
                                    class="w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                >
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-bq-text-muted">Multiplier example: 1.2 = +20% dari harga dasar.</p>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-bq-text">Blocked Dates</p>
                        <div class="mt-2 space-y-2">
                            @forelse ($blockedDates as $blocked)
                                <div class="flex items-center justify-between rounded-lg border border-bq-border bg-bq-background px-3 py-2 text-xs">
                                    <div>
                                        <p class="font-semibold text-bq-text">{{ $blocked->tanggal->format('d M Y') }}</p>
                                        <p class="text-bq-text-muted">{{ $blocked->alasan ?? 'No reason' }}</p>
                                    </div>
                                    <form id="form-remove-blocked-{{ $blocked->id }}" method="POST" action="/owner/schedule/blocked-dates/{{ $blocked->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="rounded-md px-2 py-1 text-rose-600 hover:bg-rose-50" 
                                            @click="$dispatch('open-confirm', { title: 'Buka Tanggal?', message: 'Apakah Anda yakin ingin menghapus blokir untuk tanggal ini?', formId: 'form-remove-blocked-{{ $blocked->id }}' })">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-bq-border bg-bq-background px-3 py-4 text-center text-xs text-bq-text-muted">
                                    Belum ada tanggal diblokir.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-bq-border px-6 py-4">
                    <button type="button" @click="buka = false" class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-text transition-all hover:bg-bq-background" id="btn-cancel-configure-availability">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="sedangkirim"
                        :class="sedangkirim ? 'opacity-60 cursor-not-allowed' : 'hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5'"
                        class="inline-flex items-center gap-2 rounded-lg bg-bq-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all"
                        id="btn-submit-configure-availability"
                    >
                        <svg x-show="sedangkirim" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="sedangkirim ? 'Saving...' : 'Save Settings'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
