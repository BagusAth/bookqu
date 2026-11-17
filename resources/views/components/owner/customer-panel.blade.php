{{--
    The one customer panel (BQ-129, owner-portal.md §8.5, rule 3). Rendered once by the owner
    layout and opened with $dispatch('open-customer-panel', { url: '<json url>', trigger: $el }).
    The JSON endpoint `owner.customers.panel` (BQ-137, Actions/Customer/GetCustomerPanel) answers,
    for a customer of the current business:
    {
      identifier, name, phone, whatsapp_url, email, email_url, note, note_url,
      stats: [{ label, value }],                       // spending omitted for Kasir (AC-PLG-03)
      bookings: [{ code, service, date, time, status: { label, tone }, panel_url, href }],
      payments: [{ order_id, date, amount, status: { label, tone } }],   // empty for Kasir
      walkin: { namapelanggan, nomorhp, email }        // AC-PLG-04 prefill
    }
    A booking row opens the booking panel (or the filtered Booking list until that panel exists).
    Values are rendered as text only.
--}}
@props([
    'canWalkIn' => false,
])

@php
    $tones = "{ 'bg-bq-info-bg text-bq-info': item.status.tone === 'info', 'bg-bq-success-bg text-bq-success': item.status.tone === 'success', 'bg-bq-warning-bg text-bq-warning': item.status.tone === 'warning', 'bg-bq-danger-bg text-bq-danger': item.status.tone === 'danger', 'bg-bq-background text-bq-text-muted': !['info','success','warning','danger'].includes(item.status.tone) }";
    $linkButton = 'inline-flex items-center gap-1.5 rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-surface-2';
@endphp

<x-owner.drawer
    id="owner-customer-panel"
    title="Detail pelanggan"
    behaviour="ownerPanel('owner-customer-panel')"
    x-on:open-customer-panel.window="load($event)"
    x-on:customer-note-dirty="noteDirty = $event.detail"
    x-on:beforeunload.window="if (open && noteDirty) { $event.preventDefault(); $event.returnValue = true; }"
>
    <x-slot:heading>
        <span x-text="data ? data.name : 'Detail pelanggan'">Detail pelanggan</span>
    </x-slot:heading>

    <div x-show="loading" class="py-10 text-center text-sm text-bq-text-muted" role="status">Memuat data pelanggan…</div>
    <div x-show="failed" x-cloak class="rounded-lg bg-bq-danger-bg px-4 py-3 text-sm text-bq-danger" role="alert">Data pelanggan tidak dapat dimuat. Coba lagi.</div>
    <form id="discard-customer-note" @submit.prevent="discardNote()" hidden></form>

    <template x-if="data">
        <div class="space-y-5">
            <section aria-labelledby="customer-panel-contact">
                <h3 id="customer-panel-contact" class="mb-2 text-sm font-semibold text-bq-text">Kontak</h3>
                <p x-show="data.phone" class="text-sm bq-num text-bq-text" x-text="data.phone"></p>
                <p x-show="data.email" class="break-all text-sm text-bq-text-muted" x-text="data.email"></p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a x-show="data.whatsapp_url" x-bind:href="data.whatsapp_url" target="_blank" rel="noopener" class="{{ $linkButton }}">WhatsApp</a>
                    <a x-show="data.email_url" x-bind:href="data.email_url" class="{{ $linkButton }}">Email</a>
                    @if ($canWalkIn)
                        <button
                            type="button"
                            class="bq-btn bq-btn-primary bq-btn-sm"
                            x-on:click="
                                hide(() => {
                                    $dispatch('walkin-prefill', data.walkin);
                                    $dispatch('open-dialog', { id: 'owner-walkin-dialog' });
                                });
                            "
                        >Catat booking</button>
                    @endif
                </div>
            </section>

            <dl x-show="data.stats && data.stats.length" class="grid grid-cols-2 gap-2">
                <template x-for="(stat, index) in data.stats" x-bind:key="index">
                    <div class="rounded-lg border border-bq-border px-3 py-2">
                        <dt class="text-bq-small text-bq-text-muted" x-text="stat.label"></dt>
                        <dd class="text-sm font-semibold bq-num text-bq-text" x-text="stat.value"></dd>
                    </div>
                </template>
            </dl>

            <section aria-labelledby="customer-panel-note" x-data="ownerCustomerNote(data)"
                x-effect="$dispatch('customer-note-dirty', text !== (data.note || ''))">
                <h3 id="customer-panel-note" class="mb-1 text-sm font-semibold text-bq-text">Catatan internal</h3>
                <p class="mb-2 text-bq-small text-bq-text-muted">Hanya terlihat oleh tim Anda.</p>
                <form x-on:submit.prevent="save()" class="space-y-2">
                    <label for="customer-panel-note-text" class="sr-only">Catatan internal</label>
                    <textarea id="customer-panel-note-text" x-model="text" rows="3" maxlength="2000" placeholder="Misalnya: suka sesi pagi, alergi parfum." class="bq-input w-full"></textarea>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs bq-num text-bq-text-muted" x-text="text.length + '/2000'"></span>
                        <button type="submit" x-bind:disabled="saving" class="bq-btn bq-btn-primary bq-btn-sm disabled:opacity-60" x-text="saving ? 'Menyimpan…' : 'Simpan catatan'">Simpan catatan</button>
                    </div>
                </form>
            </section>

            <section aria-labelledby="customer-panel-bookings">
                <h3 id="customer-panel-bookings" class="mb-2 text-sm font-semibold text-bq-text">Riwayat booking</h3>
                <p x-show="!data.bookings || data.bookings.length === 0" class="text-sm text-bq-text-muted">Belum ada booking.</p>
                <ul class="divide-y divide-bq-border">
                    <template x-for="item in (data.bookings || [])" x-bind:key="item.code">
                        <li>
                            <a
                                x-bind:href="item.href"
                                x-on:click="if (item.panel_url) { $event.preventDefault(); $dispatch('open-booking-panel', { url: item.panel_url }); }"
                                class="flex w-full items-center justify-between gap-3 py-2 text-left hover:bg-bq-surface-2"
                            >
                                <span class="min-w-0 text-sm">
                                    <span class="block text-bq-text" x-text="item.code"></span>
                                    <span class="block truncate text-bq-text-muted" x-text="item.service" x-bind:title="item.service"></span>
                                    <span class="block bq-num text-bq-text-muted"><span x-text="item.date"></span>, <span x-text="item.time"></span></span>
                                </span>
                                <span class="bq-badge" x-bind:class="{{ $tones }}" x-text="item.status.label"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </section>

            <section x-show="data.payments && data.payments.length" aria-labelledby="customer-panel-payments">
                <h3 id="customer-panel-payments" class="mb-2 text-sm font-semibold text-bq-text">Riwayat pembayaran</h3>
                <ul class="divide-y divide-bq-border">
                    <template x-for="item in (data.payments || [])" x-bind:key="item.order_id">
                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span class="min-w-0">
                                <span class="block truncate text-bq-text" x-text="item.order_id" x-bind:title="item.order_id"></span>
                                <span class="block bq-num text-bq-text-muted" x-text="item.date"></span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block font-medium bq-num text-bq-text" x-text="item.amount"></span>
                                <span class="bq-badge" x-bind:class="{{ $tones }}" x-text="item.status.label"></span>
                            </span>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </template>
</x-owner.drawer>
