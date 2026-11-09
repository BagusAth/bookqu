{{--
    The one customer panel (BQ-129, owner-portal.md §8.5, rule 3). Rendered once by the owner
    layout and opened with $dispatch('open-customer-panel', { url: '<json url>', trigger: $el }).
    The JSON endpoint (BQ-137) answers, for a customer of the current business:
    {
      name, phone, whatsapp_url, email, note,
      stats: [{ label, value }],                       // revenue values omitted for Kasir
      bookings: [{ code, date, time, status: { label, tone }, panel_url }]
    }
    A booking row opens the booking panel. Values are rendered as text only.
--}}
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
                <p class="text-sm text-bq-text-muted">
                    <span x-text="data.phone"></span>
                    <a x-show="data.whatsapp_url" x-bind:href="data.whatsapp_url" target="_blank" rel="noopener" class="ml-2 text-bq-primary hover:text-bq-primary-hover">WhatsApp</a>
                </p>
                <p x-show="data.email" class="text-sm text-bq-text-muted" x-text="data.email"></p>
            </section>

            <dl x-show="data.stats && data.stats.length" class="grid grid-cols-2 gap-3">
                <template x-for="(stat, index) in data.stats" x-bind:key="index">
                    <div class="rounded-lg border border-bq-border px-3 py-2">
                        <dt class="text-xs text-bq-text-muted" x-text="stat.label"></dt>
                        <dd class="text-sm font-semibold tabular-nums text-bq-text" x-text="stat.value"></dd>
                    </div>
                </template>
            </dl>

            <section x-show="data.note" aria-labelledby="customer-panel-note">
                <h3 id="customer-panel-note" class="mb-1 text-sm font-semibold text-bq-text">Catatan</h3>
                <p class="whitespace-pre-line text-sm text-bq-text-muted" x-text="data.note"></p>
            </section>

            <section aria-labelledby="customer-panel-bookings">
                <h3 id="customer-panel-bookings" class="mb-2 text-sm font-semibold text-bq-text">Booking</h3>
                <p x-show="!data.bookings || data.bookings.length === 0" class="text-sm text-bq-text-muted">Belum ada booking.</p>
                <ul class="divide-y divide-bq-border">
                    <template x-for="booking in (data.bookings || [])" x-bind:key="booking.code">
                        <li>
                            <button type="button" x-on:click="$dispatch('open-booking-panel', { url: booking.panel_url })" class="flex w-full items-center justify-between gap-3 py-2 text-left hover:bg-bq-background">
                                <span class="text-sm">
                                    <span class="block text-bq-text" x-text="booking.code"></span>
                                    <span class="block tabular-nums text-bq-text-muted"><span x-text="booking.date"></span>, <span x-text="booking.time"></span></span>
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" x-bind:class="{ 'bg-bq-info-bg text-bq-info': booking.status.tone === 'info', 'bg-bq-success-bg text-bq-success': booking.status.tone === 'success', 'bg-bq-warning-bg text-bq-warning': booking.status.tone === 'warning', 'bg-bq-danger-bg text-bq-danger': booking.status.tone === 'danger', 'bg-bq-background text-bq-text-muted': !['info','success','warning','danger'].includes(booking.status.tone) }" x-text="booking.status.label"></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </template>
</x-owner.drawer>
