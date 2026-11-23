{{-- Checkout form cards (design A §7.3). Inputs keep old() values after a validation error (BQ-164 PL-02). --}}
@php
    $fieldError = fn (string $name) => $errors->first($name);
@endphp

<div class="min-w-0 space-y-3.5">
    <section aria-labelledby="judul-data" class="sf-card p-[18px] md:p-6">
        <h2 id="judul-data" class="text-[17px] font-bold">Data pemesan</h2>
        <p class="mt-1 text-[13px] text-sf-muted">Bukti reservasi dan tautan kelola dikirim ke email ini.</p>
        <div class="mt-4 grid gap-3.5 md:grid-cols-2">
            @foreach ([
                ['id' => 'namapelanggan', 'label' => 'Nama lengkap', 'type' => 'text', 'model' => 'name', 'auto' => 'name', 'attrs' => 'minlength=2 maxlength=150'],
                ['id' => 'email', 'label' => 'Email', 'type' => 'email', 'model' => 'email', 'auto' => 'email', 'attrs' => 'maxlength=100'],
                ['id' => 'nomorhp', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'model' => 'phone', 'auto' => 'tel', 'attrs' => 'minlength=10 maxlength=20'],
            ] as $field)
                <div>
                    <label for="{{ $field['id'] }}" class="text-[13px] font-semibold">{{ $field['label'] }}</label>
                    <input id="{{ $field['id'] }}" name="{{ $field['id'] }}" type="{{ $field['type'] }}" x-model.trim="{{ $field['model'] }}"
                        value="{{ old($field['id']) }}" autocomplete="{{ $field['auto'] }}" {!! $field['attrs'] !!} required
                        @if ($field['id'] === 'nomorhp') @input="$el.setCustomValidity('')" @endif
                        @if ($fieldError($field['id'])) aria-invalid="true" aria-describedby="{{ $field['id'] }}-error" @endif
                        class="sf-field mt-1.5">
                    @if ($fieldError($field['id']))
                        <p id="{{ $field['id'] }}-error" class="sf-field-error">{{ $fieldError($field['id']) }}</p>
                    @endif
                </div>
            @endforeach
            <div>
                <label for="catatan" class="text-[13px] font-semibold">Catatan (opsional)</label>
                <textarea id="catatan" name="catatan" rows="2" maxlength="500" class="sf-area mt-1.5 min-h-12"
                    @if ($fieldError('catatan')) aria-invalid="true" aria-describedby="catatan-error" @endif>{{ old('catatan') }}</textarea>
                @if ($fieldError('catatan'))
                    <p id="catatan-error" class="sf-field-error">{{ $fieldError('catatan') }}</p>
                @endif
            </div>
        </div>
        <label class="mt-3.5 flex min-h-11 items-start gap-3 text-sm leading-5">
            <input id="remember-customer-data" type="checkbox" x-model="remember" class="mt-0.5 size-[22px] shrink-0 accent-(--sf-primary)">
            <span>Simpan nama, email dan nomor di perangkat ini untuk {{ $tenant->namabisnis }}</span>
        </label>
        <button type="button" class="sf-btn-secondary sf-btn-sm mt-1" x-show="hasSavedContact" x-cloak @click="forgetContact()">Hapus data tersimpan</button>
    </section>

    @if ($service->kapasitas > 1)
        <section aria-labelledby="judul-peserta" class="sf-card flex flex-wrap items-center gap-3 p-[18px] md:p-6">
            <div class="grow">
                <h2 id="judul-peserta" class="text-[17px] font-bold"><label for="checkout-participants">Jumlah peserta</label></h2>
                <p class="mt-0.5 text-[13px] text-sf-muted">Paling banyak {{ $service->kapasitas }} orang per sesi</p>
            </div>
            <x-customer.stepper id="checkout-participants" model="participants" label="jumlah peserta"
                name="peserta" :min="1" :max="$service->kapasitas" :value="(int) old('peserta', 1)" />
        </section>
    @endif

    @if ($addOnOptions !== [])
        <fieldset class="sf-card p-[18px] md:p-6">
            <div class="flex items-baseline justify-between gap-3">
                <legend class="float-left text-[17px] font-bold">Tambahan</legend>
                <span class="text-[13px] text-sf-muted">Opsional</span>
            </div>
            <div class="clear-both mt-3 grid gap-2 md:grid-cols-2">
                @foreach ($addOnOptions as $item)
                    <div class="flex items-center gap-2.5 rounded-sf-card-small bg-sf-page p-3">
                        <div class="min-w-0 grow">
                            <label for="checkout-addon-{{ $item['id'] }}" class="block text-sm font-semibold leading-[19px]">{{ $item['name'] }}</label>
                            <p class="sf-num mt-0.5 text-[13px] text-sf-muted">{{ $item['price_label'] }} per item</p>
                        </div>
                        <x-customer.stepper :id="'checkout-addon-'.$item['id']" :model="'quantities['.$item['id'].']'"
                            :label="$item['name']" :value="$oldAddOnQuantities[$item['id']] ?? 0" />
                    </div>
                @endforeach
            </div>
            <div hidden>
                <template x-for="(item, index) in selectedAddOns" :key="item.id">
                    <div>
                        <input type="hidden" :name="'selected_addons[' + index + '][id]'" :value="item.id">
                        <input type="hidden" :name="'selected_addons[' + index + '][quantity]'" :value="item.quantity">
                    </div>
                </template>
            </div>
        </fieldset>
    @endif

    @if ($showPromo)
        <section aria-labelledby="judul-promo" class="sf-card p-[18px] md:p-6">
            <h2 id="judul-promo" class="text-[17px] font-bold"><label for="checkout-promo">Kode promo</label></h2>
            <p class="mt-1 text-[13px] text-sf-muted">Opsional. Total dihitung ulang setelah kode diisi.</p>
            <input id="checkout-promo" name="voucher_code" maxlength="50" value="{{ $oldPromoCode }}" x-model.trim="voucherCode"
                autocomplete="off" class="sf-field mt-3 font-semibold tracking-[1px] uppercase"
                @if ($fieldError('voucher_code')) aria-invalid="true" aria-describedby="voucher-error" @endif>
            @if ($fieldError('voucher_code'))
                <p id="voucher-error" class="sf-field-error">{{ $fieldError('voucher_code') }}</p>
            @endif
        </section>
    @endif

    <div x-show="feedback" x-cloak class="space-y-2">
        <p role="status" class="text-sm text-sf-muted" x-text="feedback"></p>
        <button type="button" class="sf-btn-secondary" x-show="quoteError" @click="queueQuote()">Coba lagi</button>
    </div>

    {{-- Tablet: price breakdown above the bottom bar. --}}
    <section aria-labelledby="judul-rincian-harga" class="sf-card hidden p-6 md:block lg:hidden">
        <h2 id="judul-rincian-harga" class="text-[17px] font-bold">Rincian harga</h2>
        <dl class="mt-3 space-y-2.5 text-sm">
            @include('customer.partials.booking.checkout-summary-desktop')
            <div class="flex justify-between gap-4 border-t border-sf-divider pt-3 text-base font-bold">
                <dt>Total</dt>
                <dd class="sf-num" x-text="totalLabel">{{ $checkoutSummary['total'] }}</dd>
            </div>
        </dl>
    </section>

    <ul class="space-y-1 text-[13px] leading-[19px] text-sf-muted lg:hidden">
        @foreach ($policyLines as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ul>
</div>
