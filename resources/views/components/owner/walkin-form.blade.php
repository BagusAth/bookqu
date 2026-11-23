@props(['fixedSlot' => null, 'slots' => [], 'slotsUrl' => null, 'searchUrl' => null, 'idPrefix' => 'walkin'])

@php
    $formValue = fn ($key, $fallback = null) => old('_form') === $idPrefix ? old($key, $fallback) : $fallback;
    $methods = ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'manual' => 'Lainnya'];
    $config = [
        'searchUrl' => $searchUrl, 'today' => today()->toDateString(),
        'date' => $formValue('walkin_date'), 'slot' => $formValue('idschedule'),
        'addOns' => $formValue('selected_addons', []),
    ];
@endphp

<form method="POST" action="{{ route('owner.bookings.walkin') }}" x-data="ownerWalkInForm(@js($slotsUrl), @js($config))"
    x-on:open-dialog.window="start($event)" x-on:walkin-prefill.window="prefill($event.detail)"
    x-on:submit="save($event)" {{ $attributes->class('space-y-4') }}>
    @csrf
    <input type="hidden" name="_form" value="{{ $idPrefix }}">

    <div x-show="conflict" x-cloak>
        <x-ui.error-block>
            <x-slot:title><span x-text="conflict?.start ? 'Jam ' + conflict.start.replace(':', '.') + ' baru saja terisi booking lain' : 'Slot waktu yang dipilih tidak tersedia'"></span></x-slot:title>
            <p class="mt-1">Booking ini belum tersimpan dan isian Anda tidak hilang.</p>
            <p x-show="conflict?.alternatives.length" class="mt-1">Pilih jam lain yang masih kosong:</p>
            <p x-show="!conflict?.alternatives.length" class="mt-1">Pilih tanggal lain untuk melihat jam yang masih kosong.</p>
            <x-slot:action>
                <template x-for="alternative in conflict?.alternatives || []" :key="alternative.id">
                    <x-ui.button size="sm" x-on:click="pick(alternative.id)" x-text="alternative.start.replace(':', '.')" />
                </template>
            </x-slot:action>
        </x-ui.error-block>
    </div>

    <div x-show="errors.quota" x-cloak>
        <x-ui.error-block title="Kuota booking bulan ini habis" description="Booking yang ada tetap bisa diurus." />
    </div>
    <p x-show="errors.error" x-text="errors.error?.[0]" class="text-bq-danger" role="alert"></p>
    <x-owner.field-error name="error" :form="$idPrefix" />

    @if ($slotsUrl)
        <div>
            <label for="{{ $idPrefix }}-service" class="bq-label">Layanan</label>
            <x-ui.select id="{{ $idPrefix }}-service" x-model="service" x-on:change="quantities = {}; pick('')" x-bind:disabled="saving" class="mt-1 w-full">
                <option value="">Pilih layanan</option>
                <template x-for="item in services" :key="item.id">
                    <option :value="item.id" x-text="item.name"></option>
                </template>
            </x-ui.select>
        </div>
        <div>
            <label for="{{ $idPrefix }}-date" class="bq-label">Tanggal</label>
            <x-ui.input type="date" id="{{ $idPrefix }}-date" name="walkin_date" x-model="date"
                x-on:change="pick(''); load()" min="{{ $config['today'] }}" x-bind:disabled="saving" required class="mt-1 w-full" />
        </div>
        <fieldset class="space-y-2">
            <legend class="bq-label">Jam mulai</legend>
            <p x-show="loading" role="status" class="text-bq-small text-bq-text-muted">Memuat slot…</p>
            <p x-show="failed" role="alert" class="text-bq-danger">
                Slot tidak dapat dimuat. <button type="button" x-on:click="load()" class="bq-link">Coba lagi</button>
            </p>
            <p x-show="!loading && !failed && !serviceSlots.length" class="text-bq-small text-bq-text-muted">Tidak ada slot pada tanggal ini.</p>
            <div class="flex flex-wrap gap-2" x-show="!loading">
                <template x-for="slot in serviceSlots" :key="slot.id">
                    <label class="relative">
                        <input type="radio" name="idschedule" :value="slot.id" :checked="String(slot.id) === selectedSlot"
                            :disabled="!slot.available || saving" required x-on:change="pick(slot.id)" class="peer sr-only">
                        <span class="bq-num block rounded-bq-control border border-bq-border px-3 py-2 font-bq-display text-bq-clock font-semibold peer-checked:border-bq-primary peer-checked:bg-bq-primary-light peer-disabled:bg-bq-background peer-disabled:text-bq-text-muted peer-disabled:line-through peer-focus-visible:outline-2 peer-focus-visible:outline-bq-primary"
                            x-text="slot.start.replace(':', '.')"></span>
                    </label>
                </template>
            </div>
            <p class="text-bq-small text-bq-text-muted">Jam yang dicoret sudah terisi atau sudah lewat.</p>
            <p x-show="errors.idschedule" x-text="errors.idschedule?.[0]" class="text-bq-danger" role="alert"></p>
        </fieldset>
        <fieldset x-show="addOns.length" x-cloak class="space-y-2">
            <legend class="bq-label">Tambahan</legend>
            <template x-for="item in addOns" :key="item.id">
                <label class="flex items-center justify-between gap-3">
                    <span x-text="item.name"></span>
                    <input type="number" min="0" max="9999" placeholder="0" x-model.number="quantities[item.id]"
                        :aria-label="'Jumlah ' + item.name" class="bq-input max-w-24">
                </label>
            </template>
            <template x-for="(item, index) in selectedAddOns" :key="item.id">
                <div>
                    <input type="hidden" :name="'selected_addons[' + index + '][id]'" :value="item.id">
                    <input type="hidden" :name="'selected_addons[' + index + '][quantity]'" :value="quantities[item.id]">
                </div>
            </template>
            <template x-for="[field, messages] in Object.entries(errors).filter(([field]) => field.startsWith('selected_addons'))" :key="field">
                <p x-text="messages[0]" class="text-bq-danger" role="alert"></p>
            </template>
        </fieldset>
    @elseif ($fixedSlot)
        <input type="hidden" name="idschedule" value="{{ $fixedSlot['id'] }}">
        <p class="rounded-bq-control bg-bq-background px-3 py-2">{{ $fixedSlot['label'] }}</p>
    @else
        <div>
            <label for="{{ $idPrefix }}-slot" class="bq-label">Slot</label>
            <x-ui.select id="{{ $idPrefix }}-slot" name="idschedule" required class="mt-1 w-full">
                <option value="">Pilih slot</option>
                @foreach ($slots as $option)
                    <option value="{{ $option['id'] }}" @selected((string) $formValue('idschedule') === (string) $option['id'])>{{ $option['label'] }}</option>
                @endforeach
            </x-ui.select>
        </div>
    @endif
    <x-owner.field-error name="idschedule" :form="$idPrefix" :id="$idPrefix.'-slot-error'" />

    <div @if ($slotsUrl) x-show="capacity > 1" x-cloak @endif>
        <label for="{{ $idPrefix }}-participants" class="bq-label">Peserta</label>
        <x-ui.input id="{{ $idPrefix }}-participants" type="number" name="peserta" min="1"
            x-bind:max="picked?.seats_left || 9999" x-bind:disabled="{{ $slotsUrl ? 'capacity <= 1' : 'false' }}" required :value="$formValue('peserta', 1)" class="mt-1 w-full" />
        <p x-show="picked" class="mt-1 text-bq-small text-bq-text-muted" x-text="'Sisa ' + picked?.seats_left + ' dari ' + capacity + ' kursi di jam ini.'"></p>
        <p x-show="errors.peserta" x-text="errors.peserta?.[0]" class="text-bq-danger" role="alert"></p>
        <x-owner.field-error name="peserta" :form="$idPrefix" />
    </div>

    <div x-on:click.outside="customers = []" x-on:keydown.escape="customers = []">
        <label for="{{ $idPrefix }}-name" class="bq-label">Nama pelanggan</label>
        <x-ui.input id="{{ $idPrefix }}-name" x-ref="name" name="namapelanggan" required maxlength="150"
            :value="$formValue('namapelanggan')" autocomplete="off" placeholder="Nama atau nomor HP"
            x-on:input.debounce.250ms="suggest($el)" class="mt-1 w-full" />
        @if ($searchUrl)
            <p class="mt-1 text-bq-small text-bq-text-muted">Ketik nama atau nomor HP untuk mencari pelanggan lama.</p>
            <ul x-show="customers.length" x-cloak aria-label="Pelanggan lama" class="mt-2 rounded-bq-panel border border-bq-border p-1">
                <template x-for="customer in customers" :key="customer.panel_url">
                    <li><button type="button" x-on:click="prefill(customer)" class="w-full rounded-bq-control px-3 py-2 text-left hover:bg-bq-background">
                        <span class="block font-semibold" x-text="customer.name"></span>
                        <span class="block text-bq-small text-bq-text-muted" x-text="customer.phone"></span>
                    </button></li>
                </template>
            </ul>
        @endif
        <p x-show="errors.namapelanggan" x-text="errors.namapelanggan?.[0]" class="text-bq-danger" role="alert"></p>
        <x-owner.field-error name="namapelanggan" :form="$idPrefix" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $idPrefix }}-phone" class="bq-label">Nomor WhatsApp</label>
            <x-ui.input id="{{ $idPrefix }}-phone" x-ref="phone" type="tel" name="nomorhp" required maxlength="30" :value="$formValue('nomorhp')" class="mt-1 w-full" />
            <p x-show="errors.nomorhp" x-text="errors.nomorhp?.[0]" class="text-bq-danger" role="alert"></p>
            <x-owner.field-error name="nomorhp" :form="$idPrefix" />
        </div>
        <div>
            <label for="{{ $idPrefix }}-email" class="bq-label">Email (opsional)</label>
            <x-ui.input id="{{ $idPrefix }}-email" x-ref="email" type="email" name="email" maxlength="100" :value="$formValue('email')" class="mt-1 w-full" />
            <p x-show="errors.email" x-text="errors.email?.[0]" class="text-bq-danger" role="alert"></p>
            <x-owner.field-error name="email" :form="$idPrefix" />
        </div>
    </div>

    <fieldset>
        <legend class="mb-2 bq-label">Cara bayar</legend>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ($methods as $value => $label)
                <x-ui.radio-card name="metode" :value="$value" :label="$label" :checked="$formValue('metode', 'cash') === $value" />
            @endforeach
        </div>
        <p class="mt-1 text-bq-small text-bq-text-muted">Booking yang dicatat di sini langsung terkonfirmasi.</p>
        <x-owner.field-error name="metode" :form="$idPrefix" />
    </fieldset>

    <div>
        <label for="{{ $idPrefix }}-note" class="bq-label">Catatan (opsional)</label>
        <x-ui.textarea id="{{ $idPrefix }}-note" name="catatan" rows="2" maxlength="500" class="mt-1 w-full">{{ $formValue('catatan') }}</x-ui.textarea>
        <p x-show="errors.catatan" x-text="errors.catatan?.[0]" class="text-bq-danger" role="alert"></p>
        <x-owner.field-error name="catatan" :form="$idPrefix" />
    </div>

    <div class="flex justify-end">
        <x-ui.button type="submit" variant="primary" x-ref="submit" x-bind:disabled="{{ $slotsUrl ? 'saving || loading || !picked?.available' : 'saving' }}">Catat booking</x-ui.button>
    </div>
</form>
