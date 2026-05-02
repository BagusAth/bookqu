{{-- Add Bulk Slots Modal Component --}}
@props(['daftarlayanan' => collect()])

<div
    x-data="{
        buka: false,
        sedangkirim: false,
        jenisslot: 'harian',
        jumlahslotditambah: 0,
    }"
    @open-add-bulk-slots.window="buka = true"
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
        class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    >
        <div class="w-full max-w-xl rounded-2xl border border-bq-border bg-bq-surface shadow-2xl" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-bq-border px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-bq-text">Add Bulk Slots</h2>
                    <p class="text-sm text-bq-text-muted">Create multiple schedule slots at once.</p>
                </div>
                <button @click="buka = false" class="rounded-lg p-1.5 text-bq-text-subtle transition-colors hover:bg-bq-background hover:text-bq-text" id="btn-close-bulk-slots">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form method="POST" action="/owner/schedule/bulk-slots" @submit="sedangkirim = true" id="form-add-bulk-slots">
                @csrf
                <div class="space-y-4 px-6 py-5">
                    {{-- Slot Type Toggle --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-bq-text">Slot Type</label>
                        <div class="flex rounded-lg border border-bq-border bg-bq-background p-0.5">
                            <button type="button" @click="jenisslot = 'harian'"
                                :class="jenisslot === 'harian' ? 'bg-bq-primary text-white shadow-sm' : 'text-bq-text-muted hover:text-bq-text'"
                                class="flex-1 rounded-md px-3.5 py-2 text-sm font-medium transition-all duration-200">
                                Daily
                            </button>
                            <button type="button" @click="jenisslot = 'rentang'"
                                :class="jenisslot === 'rentang' ? 'bg-bq-primary text-white shadow-sm' : 'text-bq-text-muted hover:text-bq-text'"
                                class="flex-1 rounded-md px-3.5 py-2 text-sm font-medium transition-all duration-200">
                                Date Range
                            </button>
                        </div>
                        <input type="hidden" name="jenisslot" :value="jenisslot">
                    </div>

                    {{-- Service --}}
                    <div>
                        <label for="input-idlayanan" class="mb-1.5 block text-sm font-medium text-bq-text">Program <span class="text-rose-500">*</span></label>
                        <select
                            name="idlayanan"
                            id="input-idlayanan"
                            required
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                        >
                            <option value="">Select a program</option>
                            @foreach ($daftarlayanan as $layanan)
                                <option value="{{ $layanan->id }}">{{ $layanan->namalayanan }} — Rp {{ number_format($layanan->harga, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date(s) --}}
                    <div>
                        <template x-if="jenisslot === 'harian'">
                            <div>
                                <label for="input-tanggal" class="mb-1.5 block text-sm font-medium text-bq-text">Date <span class="text-rose-500">*</span></label>
                                <input type="date" name="tanggal" id="input-tanggal" required
                                    class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                            </div>
                        </template>
                        <template x-if="jenisslot === 'rentang'">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="input-tanggalmulai" class="mb-1.5 block text-sm font-medium text-bq-text">Start Date <span class="text-rose-500">*</span></label>
                                    <input type="date" name="tanggalmulai" id="input-tanggalmulai"
                                        class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                                </div>
                                <div>
                                    <label for="input-tanggalselesai" class="mb-1.5 block text-sm font-medium text-bq-text">End Date <span class="text-rose-500">*</span></label>
                                    <input type="date" name="tanggalselesai" id="input-tanggalselesai"
                                        class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Time Range --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="input-jammulai" class="mb-1.5 block text-sm font-medium text-bq-text">Start Time <span class="text-rose-500">*</span></label>
                            <input type="time" name="jammulai" id="input-jammulai" required value="08:00"
                                class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        </div>
                        <div>
                            <label for="input-jamselesai" class="mb-1.5 block text-sm font-medium text-bq-text">End Time <span class="text-rose-500">*</span></label>
                            <input type="time" name="jamselesai" id="input-jamselesai" required value="17:00"
                                class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        </div>
                    </div>

                    {{-- Slot Interval --}}
                    <div>
                        <label for="input-intervalslot" class="mb-1.5 block text-sm font-medium text-bq-text">Slot Duration (minutes) <span class="text-rose-500">*</span></label>
                        <select name="intervalslot" id="input-intervalslot" required
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60" selected>60 minutes (1 hour)</option>
                            <option value="90">90 minutes</option>
                            <option value="120">120 minutes (2 hours)</option>
                        </select>
                    </div>

                    {{-- Preview --}}
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                        <p class="text-xs font-medium text-blue-800">
                            <svg class="mr-1 inline h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            Slots will be generated automatically from start to end time using the selected interval.
                        </p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-bq-border px-6 py-4">
                    <button type="button" @click="buka = false" class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-text transition-all hover:bg-bq-background" id="btn-cancel-slots">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="sedangkirim"
                        :class="sedangkirim ? 'opacity-60 cursor-not-allowed' : 'hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5'"
                        class="inline-flex items-center gap-2 rounded-lg bg-bq-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all"
                        id="btn-submit-slots"
                    >
                        <svg x-show="sedangkirim" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="sedangkirim ? 'Creating Slots...' : 'Create Slots'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
