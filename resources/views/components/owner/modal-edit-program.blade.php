{{-- Edit Program Modal Component --}}
<div
    x-data="{ buka: false, sedangkirim: false, program: { id: null, namalayanan: '', harga: '', durasi: '', deskripsi: '', is_active: 1 } }"
    @open-edit-program.window="program = $event.detail; buka = true"
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
        <div class="w-full max-w-lg rounded-2xl border border-bq-border bg-bq-surface shadow-2xl" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-bq-border px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-bq-text">Edit Program</h2>
                    <p class="text-sm text-bq-text-muted">Update program details and status.</p>
                </div>
                <button @click="buka = false" class="rounded-lg p-1.5 text-bq-text-subtle transition-colors hover:bg-bq-background hover:text-bq-text" id="btn-close-edit-program">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form method="POST" :action="`/owner/programs/${program.id}`" @submit="sedangkirim = true" id="form-edit-program">
                @csrf
                @method('PUT')
                <div class="space-y-4 px-6 py-5">
                    {{-- Program Name --}}
                    <div>
                        <label for="input-edit-namalayanan" class="mb-1.5 block text-sm font-medium text-bq-text">Program Name <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            name="namalayanan"
                            id="input-edit-namalayanan"
                            x-model="program.namalayanan"
                            required
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                        >
                    </div>

                    {{-- Price & Duration --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="input-edit-harga" class="mb-1.5 block text-sm font-medium text-bq-text">Price (Rp) <span class="text-rose-500">*</span></label>
                            <input
                                type="number"
                                name="harga"
                                id="input-edit-harga"
                                x-model="program.harga"
                                required
                                min="0"
                                step="1000"
                                class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                            >
                        </div>
                        <div>
                            <label for="input-edit-durasi" class="mb-1.5 block text-sm font-medium text-bq-text">Duration (min) <span class="text-rose-500">*</span></label>
                            <input
                                type="number"
                                name="durasi"
                                id="input-edit-durasi"
                                x-model="program.durasi"
                                required
                                min="5"
                                max="480"
                                class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                            >
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="input-edit-is-active" class="mb-1.5 block text-sm font-medium text-bq-text">Status <span class="text-rose-500">*</span></label>
                        <select
                            name="is_active"
                            id="input-edit-is-active"
                            x-model="program.is_active"
                            required
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                        >
                            <option value="1">Active</option>
                            <option value="0">Non-Active</option>
                        </select>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="input-edit-deskripsi" class="mb-1.5 block text-sm font-medium text-bq-text">Description</label>
                        <textarea
                            name="deskripsi"
                            id="input-edit-deskripsi"
                            rows="3"
                            x-model="program.deskripsi"
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20 resize-none"
                        ></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-bq-border px-6 py-4">
                    <button type="button" @click="buka = false" class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-text transition-all hover:bg-bq-background" id="btn-cancel-edit-program">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="sedangkirim"
                        :class="sedangkirim ? 'opacity-60 cursor-not-allowed' : 'hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5'"
                        class="inline-flex items-center gap-2 rounded-lg bg-bq-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all"
                        id="btn-submit-edit-program"
                    >
                        <svg x-show="sedangkirim" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="sedangkirim ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
