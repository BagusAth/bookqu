{{-- Edit Program Modal Component --}}
<div
    x-data="{
        buka: false,
        sedangkirim: false,
        preview: null,
        program: { id: null, namalayanan: '', idcategory: '', harga: '', durasi: '', deskripsi: '', is_active: 1, image_url: null, staff_ids: [], resource_ids: [], additional_item_ids: [] },
        handleFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => this.preview = ev.target.result;
            reader.readAsDataURL(file);
        },
        removeImage() {
            this.preview = null;
            this.$refs.fileInput.value = '';
        }
    }"
    @open-edit-program.window="
        program = {
            ...$event.detail,
            staff_ids: $event.detail.staff_ids || [],
            resource_ids: $event.detail.resource_ids || [],
            additional_item_ids: $event.detail.additional_item_ids || [],
        };
        preview = program.image_url || null;
        buka = true;
    "
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
            <form method="POST" :action="`/owner/programs/${program.id}`" enctype="multipart/form-data" @submit="sedangkirim = true" id="form-edit-program">
                @csrf
                @method('PUT')
                {{-- Flag to remove existing image --}}
                <input type="hidden" name="remove_image" :value="(!preview && !$refs.fileInput?.files?.length) ? '1' : '0'">

                <div class="space-y-4 px-6 py-5 max-h-[72vh] overflow-y-auto">

                    {{-- Cover Image Upload --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-bq-text">Cover Image <span class="text-bq-text-subtle text-xs font-normal">(optional, max 2MB)</span></label>
                        <div
                            class="relative flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-bq-border bg-bq-background transition-all hover:border-bq-primary/50 hover:bg-indigo-50/30 cursor-pointer overflow-hidden"
                            :class="preview ? 'p-0 border-solid border-bq-primary/40' : 'p-6'"
                            @click="$refs.fileInput.click()"
                        >
                            {{-- Preview --}}
                            <template x-if="preview">
                                <div class="relative w-full h-36">
                                    <img :src="preview" class="h-full w-full object-cover rounded-xl" alt="Cover preview">
                                    <button
                                        type="button"
                                        @click.stop="removeImage()"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70"
                                        title="Remove image"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            {{-- Placeholder --}}
                            <template x-if="!preview">
                                <div class="flex flex-col items-center gap-2 text-center pointer-events-none select-none">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-bq-primary">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-bq-text">Click to upload cover image</p>
                                        <p class="text-xs text-bq-text-subtle mt-0.5">JPG, PNG, WEBP up to 2MB</p>
                                    </div>
                                </div>
                            </template>

                            <input
                                type="file"
                                name="cover_image"
                                accept="image/jpeg,image/png,image/webp"
                                x-ref="fileInput"
                                class="hidden"
                                id="input-edit-cover-image"
                                @change="handleFile($event)"
                            >
                        </div>
                    </div>

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

                    {{-- Category --}}
                    <div>
                        <label for="input-edit-idcategory" class="mb-1.5 block text-sm font-medium text-bq-text">Category <span class="text-bq-text-subtle text-xs font-normal">(optional)</span></label>
                        <select
                            name="idcategory"
                            id="input-edit-idcategory"
                            x-model="program.idcategory"
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                        >
                            <option value="">-- Tanpa Kategori --</option>
                            @if(isset($kategoriList))
                                @foreach($kategoriList as $kat)
                                    <option value="{{ $kat->id }}">{{ $kat->name }}</option>
                                @endforeach
                            @endif
                        </select>
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
                            rows="2"
                            x-model="program.deskripsi"
                            class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20 resize-none"
                        ></textarea>
                    </div>

                    {{-- Staff Assignment --}}
                    @if(isset($staffList) && $staffList->isNotEmpty())
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-bq-text">Assign Staff <span class="text-bq-text-subtle text-xs font-normal">(optional)</span></label>
                            <div class="grid grid-cols-2 gap-2 max-h-28 overflow-y-auto p-2 border border-bq-border rounded-lg bg-bq-background/50">
                                @foreach($staffList as $stf)
                                    <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer hover:text-bq-primary">
                                        <input type="checkbox" name="staff_ids[]" value="{{ $stf->id }}"
                                               :checked="program.staff_ids && program.staff_ids.includes({{ $stf->id }})"
                                               @change="
                                                   if ($event.target.checked) {
                                                       if (!program.staff_ids.includes({{ $stf->id }})) program.staff_ids.push({{ $stf->id }});
                                                   } else {
                                                       program.staff_ids = program.staff_ids.filter(id => id !== {{ $stf->id }});
                                                   }
                                               "
                                               class="rounded border-bq-border text-bq-primary focus:ring-bq-primary/20">
                                        <span class="truncate">{{ $stf->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Resource Assignment --}}
                    @if(isset($resourceList) && $resourceList->isNotEmpty())
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-bq-text">Assign Resource / Room <span class="text-bq-text-subtle text-xs font-normal">(optional)</span></label>
                            <div class="grid grid-cols-2 gap-2 max-h-28 overflow-y-auto p-2 border border-bq-border rounded-lg bg-bq-background/50">
                                @foreach($resourceList as $res)
                                    <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer hover:text-bq-primary">
                                        <input type="checkbox" name="resource_ids[]" value="{{ $res->id }}"
                                               :checked="program.resource_ids && program.resource_ids.includes({{ $res->id }})"
                                               @change="
                                                   if ($event.target.checked) {
                                                       if (!program.resource_ids.includes({{ $res->id }})) program.resource_ids.push({{ $res->id }});
                                                   } else {
                                                       program.resource_ids = program.resource_ids.filter(id => id !== {{ $res->id }});
                                                   }
                                               "
                                               class="rounded border-bq-border text-bq-primary focus:ring-bq-primary/20">
                                        <span class="truncate">{{ $res->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Additional Items Assignment --}}
                    @if(isset($additionalItemList) && $additionalItemList->isNotEmpty())
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-bq-text">Available Add-ons <span class="text-bq-text-subtle text-xs font-normal">(optional)</span></label>
                            <div class="grid grid-cols-2 gap-2 max-h-28 overflow-y-auto p-2 border border-bq-border rounded-lg bg-bq-background/50">
                                @foreach($additionalItemList as $ai)
                                    <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer hover:text-bq-primary">
                                        <input type="checkbox" name="additional_item_ids[]" value="{{ $ai->id }}"
                                               :checked="program.additional_item_ids && program.additional_item_ids.includes({{ $ai->id }})"
                                               @change="
                                                   if ($event.target.checked) {
                                                       if (!program.additional_item_ids.includes({{ $ai->id }})) program.additional_item_ids.push({{ $ai->id }});
                                                   } else {
                                                       program.additional_item_ids = program.additional_item_ids.filter(id => id !== {{ $ai->id }});
                                                   }
                                               "
                                               class="rounded border-bq-border text-bq-primary focus:ring-bq-primary/20">
                                        <span class="truncate">{{ $ai->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
