<x-owner.dialog id="calendar-slot" title="Kelola slot">
    <p x-show="!slot && !slotErrors.slot" class="text-bq-text-muted">Slot ini berada di luar filter yang sedang ditampilkan.</p>
    <p x-show="slotErrors.slot" x-text="slotErrors.slot?.[0]" class="text-bq-danger" role="alert"></p>
    <template x-for="selected in slot ? [slot] : []" :key="selected.id">
        <div class="space-y-4">
            <p class="text-sm font-semibold" x-text="selected.walkInLabel"></p>
            <template x-if="selected.walkIn">
                <section x-on:submit.prevent="saveWalkIn($event.target)" aria-label="Catat booking">
                    <h3 class="mb-2 text-sm font-semibold">Catat booking</h3>
                    <p x-show="feedback" x-text="feedback" class="mb-2 text-sm text-bq-danger" role="alert"></p>
                    <x-owner.walkin-form fixed-slot-model="selected" id-prefix="calendar-walkin" />
                </section>
            </template>
            <template x-if="selected.edit">
                <div class="space-y-3 border-t border-bq-border pt-3">
                    <form method="POST" :action="selected.updateUrl" @submit.prevent="saveSlot($el, $event.submitter)">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_form" :value="'calendar-state-' + selected.id">
                        @if (is_string(old('_form')) && str_starts_with(old('_form'), 'calendar-state-'))
                            <x-owner.field-error name="operation" x-cloak x-show="@js(old('_form')) === 'calendar-state-' + selected.id" />
                            <x-owner.field-error name="slot" x-cloak x-show="@js(old('_form')) === 'calendar-state-' + selected.id" />
                        @endif
                        <button name="operation" :disabled="busy" :value="selected.closed ? 'open' : 'close'" class="bq-btn bq-btn-secondary"
                            x-text="selected.closed ? 'Buka lagi' : 'Tutup slot ini'"></button>
                        <p x-show="slotErrors.operation" x-text="slotErrors.operation?.[0]" class="text-bq-danger" role="alert"></p>
                    </form>
                    <form method="POST" :action="selected.updateUrl" @submit.prevent="saveSlot($el, $event.submitter)" class="flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_form" :value="'calendar-price-' + selected.id">
                        <input type="hidden" name="operation" value="price">
                        <label class="grid gap-1 text-sm">Harga khusus (Rp)
                            <input name="harga_override" type="number" min="0" max="9999999999" step="1" :value="@js(old('_form')) === 'calendar-price-' + selected.id ? @js(old('harga_override')) : selected.override"
                                placeholder="Ikuti aturan harga" class="bq-input w-36">
                            <span class="text-xs text-bq-text-muted">Kosongkan untuk mengikuti aturan harga.</span>
                            <span x-show="slotErrors.harga_override" x-text="slotErrors.harga_override?.[0]" class="text-bq-danger" role="alert"></span>
                            @if (is_string(old('_form')) && str_starts_with(old('_form'), 'calendar-price-'))
                                <x-owner.field-error name="harga_override" x-cloak x-show="@js(old('_form')) === 'calendar-price-' + selected.id" />
                            @endif
                        </label>
                        <button :disabled="busy" class="bq-btn bq-btn-secondary">Ubah harga slot ini</button>
                    </form>
                </div>
            </template>
        </div>
    </template>
</x-owner.dialog>
