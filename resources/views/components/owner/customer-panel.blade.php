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
    $linkButton = 'inline-flex items-center gap-1.5 rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-background';
@endphp

<x-owner.drawer
    id="owner-customer-panel"
    title="Detail pelanggan"
    behaviour="ownerPanel('owner-customer-panel')"
    x-on:open-customer-panel.window="load($event)"
>
    <x-slot:heading>
        <span x-text="data ? data.name : 'Detail pelanggan'">Detail pelanggan</span>
    </x-slot:heading>

    <div x-show="loading" class="py-10 text-center text-sm text-bq-text-muted" role="status">Memuat data pelanggan…</div>
    <div x-show="failed" x-cloak class="rounded-lg bg-bq-danger-bg px-4 py-3 text-sm text-bq-danger" role="alert">Data pelanggan tidak dapat dimuat. Coba lagi.</div>

    <template x-if="data">
        <div class="space-y-5">
            <section aria-labelledby="customer-panel-contact">
                <h3 id="customer-panel-contact" class="mb-2 text-sm font-semibold text-bq-text">Kontak</h3>
                <p x-show="data.phone" class="text-sm tabular-nums text-bq-text" x-text="data.phone"></p>
                <p x-show="data.email" class="break-all text-sm text-bq-text-muted" x-text="data.email"></p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a x-show="data.whatsapp_url" x-bind:href="data.whatsapp_url" target="_blank" rel="noopener" class="{{ $linkButton }}">WhatsApp</a>
                    <a x-show="data.email_url" x-bind:href="data.email_url" class="{{ $linkButton }}">Email</a>
                    @if ($canWalkIn)
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-bq-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-bq-primary-hover"
                            x-on:click="
                                $dispatch('walkin-prefill', data.walkin);
                                hide();
                                $dispatch('open-dialog', { id: 'owner-walkin-dialog' });
                            "
                        >Buat booking</button>
                    @endif
                </div>
            </section>

            <dl x-show="data.stats && data.stats.length" class="grid grid-cols-2 gap-3">
                <template x-for="(stat, index) in data.stats" x-bind:key="index">
                    <div class="rounded-lg border border-bq-border px-3 py-2">
                        <dt class="text-xs text-bq-text-muted" x-text="stat.label"></dt>
                        <dd class="text-sm font-semibold tabular-nums text-bq-text" x-text="stat.value"></dd>
                    </div>
                </template>
            </dl>

            <section aria-labelledby="customer-panel-note" x-data="ownerCustomerNote(data)">
                <h3 id="customer-panel-note" class="mb-1 text-sm font-semibold text-bq-text">Catatan internal</h3>
                <p class="mb-2 text-xs text-bq-text-muted">Hanya terlihat oleh tim Anda.</p>
                <form x-on:submit.prevent="save()" class="space-y-2">
                    <label for="customer-panel-note-text" class="sr-only">Catatan internal</label>
                    <textarea id="customer-panel-note-text" x-model="text" rows="3" maxlength="2000" placeholder="Misalnya: suka sesi pagi, alergi parfum." class="w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs tabular-nums text-bq-text-muted" x-text="text.length + '/2000'"></span>
                        <button type="submit" x-bind:disabled="saving" class="inline-flex items-center rounded-lg bg-bq-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-bq-primary-hover disabled:opacity-60" x-text="saving ? 'Menyimpan…' : 'Simpan catatan'">Simpan catatan</button>
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
                                class="flex w-full items-center justify-between gap-3 py-2 text-left hover:bg-bq-background"
                            >
                                <span class="min-w-0 text-sm">
                                    <span class="block text-bq-text" x-text="item.code"></span>
                                    <span class="block truncate text-bq-text-muted" x-text="item.service"></span>
                                    <span class="block tabular-nums text-bq-text-muted"><span x-text="item.date"></span>, <span x-text="item.time"></span></span>
                                </span>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium" x-bind:class="{{ $tones }}" x-text="item.status.label"></span>
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
                                <span class="block truncate text-bq-text" x-text="item.order_id"></span>
                                <span class="block tabular-nums text-bq-text-muted" x-text="item.date"></span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block font-medium tabular-nums text-bq-text" x-text="item.amount"></span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" x-bind:class="{{ $tones }}" x-text="item.status.label"></span>
                            </span>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </template>
</x-owner.drawer>
