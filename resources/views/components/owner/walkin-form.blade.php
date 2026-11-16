{{--
    Walk-in form (BQ-129, owner-portal.md §9, FR-BOOKING-010): the one server action
    `owner.bookings.walkin`. Pass `fixed-slot` (['id' => …, 'label' => …]) for a fixed slot, or
    `slots` (a list of them) for a choice, or `slots-url` to pick a date and load that day's free
    slots (`owner.bookings.walkin-slots`, BQ-128). Participants and add-ons appear only once their
    backend exists (BQ-151, BQ-150): no control without a backend (§3 rule 6).
--}}
@props([
    'fixedSlot' => null,
    'fixedSlotModel' => null,
    'slots' => [],
    'slotsUrl' => null,
    'idPrefix' => 'walkin',
])

@php
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $formValue = fn ($key, $fallback = null) => old('_form') === $idPrefix ? old($key, $fallback) : $fallback;
    $methods =['cash' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS', 'manual' => 'Lainnya (manual)'];
    $field = 'w-full bq-input';
    $labelClass = 'mb-1 block bq-label';
@endphp

{{-- BQ-137 (AC-PLG-04): "Buat booking" in the customer panel fills the contact fields with walkin-prefill. --}}
<form
    method="POST"
    action="{{ route('owner.bookings.walkin') }}"
    x-data
    x-on:walkin-prefill.window="
        $refs.name.value = $event.detail.namapelanggan || '';
        $refs.phone.value = $event.detail.nomorhp || '';
        $refs.email.value = $event.detail.email || '';
    "
    {{ $attributes->merge(['class' => 'space-y-4']) }}
>
    @csrf
    <input type="hidden" name="_form" value="{{ $idPrefix }}">

    <div>
        <label for="{{ $idPrefix }}-slot" class="{{ $labelClass }}">Slot</label>
        @if ($fixedSlotModel)
            <input type="hidden" name="idschedule" x-bind:value="{{ $fixedSlotModel }}.id">
            <p id="{{ $idPrefix }}-slot" class="rounded-lg bg-bq-background px-3 py-2 text-sm" x-text="{{ $fixedSlotModel }}.walkInLabel"></p>
            <fieldset class="mt-2 space-y-2" x-show="{{ $fixedSlotModel }}.addOns.length" x-data="{ quantities: {} }">
                <legend class="{{ $labelClass }}">Add-on</legend>
                <template x-for="item in {{ $fixedSlotModel }}.addOns" :key="item.id">
                    <label class="flex items-center justify-between gap-2 text-sm">
                        <span x-text="item.name"></span>
                        <input type="number" min="0" max="9999" placeholder="0" x-model.number="quantities[item.id]" :aria-label="'Jumlah ' + item.name" class="{{ $field }} max-w-24">
                    </label>
                </template>
                <template x-for="(item, index) in {{ $fixedSlotModel }}.addOns.filter(item => Number(quantities[item.id]) > 0)" :key="item.id">
                    <div>
                        <input type="hidden" :name="'selected_addons[' + index + '][id]'" :value="item.id">
                        <input type="hidden" :name="'selected_addons[' + index + '][quantity]'" :value="quantities[item.id]">
                    </div>
                </template>
            </fieldset>
        @elseif ($fixedSlot)
            <input type="hidden" name="idschedule" value="{{ $fixedSlot['id'] }}">
            <p id="{{ $idPrefix }}-slot" class="rounded-lg bg-bq-background px-3 py-2 text-sm text-bq-text">{{ $fixedSlot['label'] }}</p>
        @elseif ($slotsUrl)
            <div x-data="ownerWalkInSlots(@js($slotsUrl), @js(today()->toDateString()), @js($formValue('walkin_date')), @js($formValue('idschedule')), @js($formValue('selected_addons', [])))" x-on:open-dialog.window="loadWhenOpened($event)" class="grid grid-cols-1 gap-2 sm:grid-cols-[10rem_1fr]">
                <input type="date" name="walkin_date" x-model="date" x-on:change="load()" x-bind:min="today" aria-label="Tanggal" class="{{ $field }}">
                <select id="{{ $idPrefix }}-slot" name="idschedule" required x-model="selectedSlot" x-on:change="quantities = {}" x-bind:disabled="loading" class="{{ $field }}">
                    <option value="" x-text="loading ? 'Memuat slot…' : (slots.length ? 'Pilih slot' : 'Tidak ada slot kosong')">Pilih slot</option>
                    <template x-for="slot in slots" x-bind:key="slot.id">
                        <option x-bind:value="slot.id" x-text="slot.label"></option>
                    </template>
                </select>
                <fieldset x-show="addOns.length" x-cloak class="space-y-2 sm:col-span-2">
                    <legend class="{{ $labelClass }}">Add-on</legend>
                    <template x-for="item in addOns" :key="item.id">
                        <label class="flex items-center justify-between gap-3 text-sm">
                            <span x-text="item.name"></span>
                            <input type="number" min="0" max="9999" placeholder="0" x-model.number="quantities[item.id]"
                                :aria-label="'Jumlah ' + item.name" class="{{ $field }} max-w-24">
                        </label>
                    </template>
                    <template x-for="(item, index) in selectedAddOns" :key="item.id">
                        <div>
                            <input type="hidden" :name="'selected_addons[' + index + '][id]'" :value="item.id">
                            <input type="hidden" :name="'selected_addons[' + index + '][quantity]'" :value="quantities[item.id]">
                        </div>
                    </template>
                </fieldset>
                <x-owner.field-error name="selected_addons" :form="$idPrefix" class="sm:col-span-2" />
                <x-owner.field-error name="selected_addons.*" :form="$idPrefix" class="sm:col-span-2" />
                <p x-show="failed" x-cloak class="text-xs text-bq-danger sm:col-span-2" role="alert">Slot tidak dapat dimuat. Coba lagi.</p>
            </div>
        @else
            <select id="{{ $idPrefix }}-slot" name="idschedule" required class="{{ $field }}">
                <option value="">Pilih slot</option>
                @foreach ($slots as $option)
                    <option value="{{ $option['id'] }}" @selected((string) $formValue('idschedule') === (string) $option['id'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        @endif
        <x-owner.field-error name="idschedule" :form="$idPrefix" :id="$idPrefix.'-slot-error'" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $idPrefix }}-name" class="{{ $labelClass }}">Nama pelanggan</label>
            <input id="{{ $idPrefix }}-name" x-ref="name" type="text" name="namapelanggan" required maxlength="150" value="{{ $formValue('namapelanggan') }}" autocomplete="off" class="{{ $field }}">
            <x-owner.field-error name="namapelanggan" :form="$idPrefix" />
        </div>
        <div>
            <label for="{{ $idPrefix }}-phone" class="{{ $labelClass }}">Nomor HP</label>
            <input id="{{ $idPrefix }}-phone" x-ref="phone" type="tel" name="nomorhp" required maxlength="30" value="{{ $formValue('nomorhp') }}" autocomplete="off" class="{{ $field }}">
            <x-owner.field-error name="nomorhp" :form="$idPrefix" />
        </div>
    </div>

    <div>
        <label for="{{ $idPrefix }}-email" class="{{ $labelClass }}">Email <span class="font-normal text-bq-text-muted">(opsional)</span></label>
        <input id="{{ $idPrefix }}-email" x-ref="email" type="email" name="email" maxlength="100" value="{{ $formValue('email') }}" autocomplete="off" class="{{ $field }}">
        <x-owner.field-error name="email" :form="$idPrefix" />
    </div>

    <div>
        <label for="{{ $idPrefix }}-participants" class="{{ $labelClass }}">Jumlah peserta</label>
        <input id="{{ $idPrefix }}-participants" type="number" name="peserta" min="1" max="9999" @if ($fixedSlotModel) :max="{{ $fixedSlotModel }}.seatsLeft" @endif required value="{{ $formValue('peserta', 1) }}" class="{{ $field }}">
        <x-owner.field-error name="peserta" :form="$idPrefix" />
    </div>

    <fieldset>
        <legend class="{{ $labelClass }}">Metode pembayaran</legend>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ($methods as $value => $methodLabel)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bq-border px-3 py-2 text-sm text-bq-text has-[:checked]:border-bq-primary has-[:checked]:bg-bq-primary-light">
                    <input type="radio" name="metode" value="{{ $value }}" @checked($formValue('metode', 'cash') === $value) class="text-bq-primary focus:ring-bq-primary">
                    {{ $methodLabel }}
                </label>
            @endforeach
        </div>
        <x-owner.field-error name="metode" :form="$idPrefix" />
    </fieldset>

    <div>
        <label for="{{ $idPrefix }}-note" class="{{ $labelClass }}">Catatan <span class="font-normal text-bq-text-muted">(opsional)</span></label>
        <textarea id="{{ $idPrefix }}-note" name="catatan" rows="2" maxlength="500" class="{{ $field }}">{{ $formValue('catatan') }}</textarea>
        <x-owner.field-error name="catatan" :form="$idPrefix" />
    </div>

    <div class="flex justify-end">
        <button type="submit" @if ($fixedSlotModel) :disabled="busy" @endif class="bq-btn bq-btn-primary">Simpan walk-in</button>
    </div>
</form>
