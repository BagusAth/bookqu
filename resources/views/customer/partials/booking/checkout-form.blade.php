<div class="min-w-0 space-y-4">
    <x-ui.panel>
        <div class="grid gap-4">
            <x-ui.field label="Nama lengkap" for="namapelanggan" name="namapelanggan" required>
                <input id="namapelanggan" class="bq-input w-full" name="namapelanggan" type="text" x-model.trim="name"
                    value="{{ old('namapelanggan') }}" autocomplete="name" minlength="2" maxlength="150" required>
            </x-ui.field>
            <x-ui.field label="Email" for="email" name="email" required>
                <input id="email" class="bq-input w-full" name="email" type="email" x-model.trim="email"
                    value="{{ old('email') }}" autocomplete="email" maxlength="100" required>
            </x-ui.field>
            <x-ui.field label="Nomor WhatsApp" for="nomorhp" name="nomorhp" required>
                <input id="nomorhp" class="bq-input w-full" name="nomorhp" type="tel" x-model.trim="phone"
                    @input="$el.setCustomValidity('')" value="{{ old('nomorhp') }}" autocomplete="tel" minlength="10" maxlength="20" required>
            </x-ui.field>
            <x-ui.field label="Catatan (opsional)" for="catatan" name="catatan">
                <textarea id="catatan" class="bq-input w-full" name="catatan" rows="3" maxlength="500">{{ old('catatan') }}</textarea>
            </x-ui.field>
            <div class="space-y-1 border-t border-bq-border pt-3">
                <x-ui.checkbox id="remember-customer-data" x-model="remember"
                    :label="'Simpan nama, email dan nomor di perangkat ini untuk '.$tenant->namabisnis" />
                <button type="button" class="bq-btn bq-btn-ghost min-h-11" x-show="hasSavedContact" x-cloak @click="forgetContact()">
                    Hapus data tersimpan
                </button>
            </div>
        </div>
    </x-ui.panel>

    @if ($service->kapasitas > 1 || $addOnOptions !== [] || $showPromo)
        <x-ui.panel class="space-y-4">
            <div class="space-y-4">
                @if ($service->kapasitas > 1)
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label for="checkout-participants" class="text-bq-label">Jumlah peserta</label>
                        <x-customer.stepper id="checkout-participants" model="participants" label="jumlah peserta"
                            name="peserta" :min="1" :max="$service->kapasitas" :value="(int) old('peserta', 1)" />
                    </div>
                @endif
                @if ($addOnOptions !== [])
                    <fieldset>
                        <legend class="mb-2 text-bq-section">Tambahan</legend>
                        @foreach ($addOnOptions as $item)
                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-bq-border py-2">
                                <div>
                                    <label for="checkout-addon-{{ $item['id'] }}" class="text-bq-label">{{ $item['name'] }}</label>
                                    <p class="bq-num text-bq-small text-bq-text-muted">{{ $item['price_label'] }} per item</p>
                                </div>
                                <x-customer.stepper :id="'checkout-addon-'.$item['id']" :model="'quantities['.$item['id'].']'"
                                    :label="$item['name']" :value="$oldAddOnQuantities[$item['id']] ?? 0" />
                            </div>
                        @endforeach
                        <template x-for="(item, index) in selectedAddOns" :key="item.id">
                            <div hidden>
                                <input type="hidden" :name="'selected_addons[' + index + '][id]'" :value="item.id">
                                <input type="hidden" :name="'selected_addons[' + index + '][quantity]'" :value="item.quantity">
                            </div>
                        </template>
                    </fieldset>
                @endif
                @if ($showPromo)
                    <x-ui.field label="Kode promo (opsional)" for="checkout-promo" name="voucher_code">
                        <input id="checkout-promo" name="voucher_code" maxlength="50" value="{{ $oldPromoCode }}"
                            x-model.trim="voucherCode" class="bq-input w-full">
                    </x-ui.field>
                @endif
            </div>
        </x-ui.panel>
    @endif

    <div class="space-y-2" x-cloak>
        <p role="status" class="text-bq-body text-bq-text-muted" x-text="feedback"></p>
        <button type="button" class="bq-btn bq-btn-secondary min-h-11" x-show="quoteError" @click="queueQuote()">
            Coba lagi
        </button>
    </div>

    <x-customer.booking-policy :policy="$bookingPolicy" />
</div>
