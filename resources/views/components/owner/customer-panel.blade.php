{{--
    The one customer panel (BQ-129, owner-portal.md §8.5, rule 3). Rendered once by the owner
    layout and opened with $dispatch('open-customer-panel', { url: '<json url>', trigger: $el }).
    The JSON endpoint `owner.customers.panel` (BQ-137, Actions/Customer/GetCustomerPanel) answers,
    for a customer of the current business:
    {
      identifier, name, phone, whatsapp_url, email, email_url, note, note_url,
      stats: [{ label, value, tone? }],                // spending (tone money) omitted for Kasir (AC-PLG-03)
      bookings: [{ code, service, date, time, status: { label, tone }, panel_url, href }],
      payments: [{ order_id, date, amount, status: { label, tone } }],   // empty for Kasir
      walkin: { namapelanggan, nomorhp, email }        // AC-PLG-04 prefill
    }
    A booking row opens the booking panel (or the filtered Booking list until that panel exists).
    Values are rendered as text only. Visual style follows the former customer drawer.
--}}
@props([
    'canWalkIn' => false,
])

@php
    $tones = "{ 'bg-emerald-50 text-emerald-700 ring-emerald-600/20': item.status.tone === 'success', 'bg-indigo-50 text-indigo-700 ring-indigo-600/20': item.status.tone === 'info', 'bg-amber-50 text-amber-700 ring-amber-600/20': item.status.tone === 'warning', 'bg-rose-50 text-rose-700 ring-rose-600/20': item.status.tone === 'danger', 'bg-gray-50 text-gray-700 ring-gray-600/20': !['info','success','warning','danger'].includes(item.status.tone) }";
    $linkButton = 'craft-btn inline-flex items-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-3 py-2 text-xs font-bold text-[#6e6584] shadow-2xs transition hover:bg-[#f3effe] hover:text-[#382186]';
    $sectionHeading = 'text-xs font-bold uppercase tracking-wider text-bq-text-muted';
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

    <div x-show="loading" class="space-y-3" role="status">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 animate-pulse rounded-2xl bg-slate-200"></div>
            <div class="space-y-2">
                <div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div>
                <div class="h-3 w-24 animate-pulse rounded bg-slate-200"></div>
            </div>
        </div>
        <div class="h-20 animate-pulse rounded-xl bg-slate-100"></div>
        <div class="h-32 animate-pulse rounded-xl bg-slate-100"></div>
        <span class="sr-only">Memuat data pelanggan…</span>
    </div>
    <div x-show="failed" x-cloak class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700" role="alert">Data pelanggan tidak dapat dimuat. Coba lagi.</div>

    <template x-if="data">
        <div class="space-y-4">
            {{-- Header: avatar, contact and actions --}}
            <section aria-labelledby="customer-panel-contact" class="rounded-xl border border-bq-border bg-slate-50/70 p-4">
                <h3 id="customer-panel-contact" class="sr-only">Kontak</h3>
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-base font-bold text-white" aria-hidden="true">
                        <span x-text="data.name ? data.name.charAt(0).toUpperCase() : 'P'"></span>
                    </div>
                    <div class="min-w-0">
                        <p class="break-words text-base font-bold text-bq-text" x-text="data.name"></p>
                        <p x-show="data.phone" class="font-mono text-xs text-bq-text-muted" x-text="data.phone"></p>
                        <p x-show="data.email" class="break-all text-xs text-bq-text-muted" x-text="data.email"></p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a x-show="data.whatsapp_url" x-bind:href="data.whatsapp_url" target="_blank" rel="noopener" class="craft-btn inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">WhatsApp</a>
                    <a x-show="data.email_url" x-bind:href="data.email_url" class="{{ $linkButton }}">Email</a>
                    @if ($canWalkIn)
                        <button
                            type="button"
                            class="craft-btn inline-flex items-center gap-1.5 rounded-xl bg-bq-primary px-3 py-2 text-xs font-semibold text-white shadow-md shadow-bq-primary/25 transition hover:bg-bq-primary-hover"
                            x-on:click="
                                $dispatch('walkin-prefill', data.walkin);
                                hide();
                                $dispatch('open-dialog', { id: 'owner-walkin-dialog' });
                            "
                        >Buat booking</button>
                    @endif
                </div>
            </section>

            {{-- Figures --}}
            <dl x-show="data.stats && data.stats.length" class="grid grid-cols-2 gap-3">
                <template x-for="(stat, index) in data.stats" x-bind:key="index">
                    <div class="rounded-xl border border-bq-border bg-slate-50/50 p-3.5">
                        <dt class="text-[11px] text-bq-text-muted" x-text="stat.label"></dt>
                        <dd class="mt-0.5 text-base font-bold tabular-nums text-bq-text" x-bind:class="stat.tone === 'money' ? 'text-emerald-700' : ''" x-text="stat.value"></dd>
                    </div>
                </template>
            </dl>

            {{-- Internal note (BQ-100 finding 3: one save at a time) --}}
            <section aria-labelledby="customer-panel-note" x-data="ownerCustomerNote(data)" class="space-y-2 rounded-xl border border-bq-border p-4">
                <h3 id="customer-panel-note" class="{{ $sectionHeading }}">Catatan internal</h3>
                <p class="text-[11px] text-bq-text-muted">Hanya terlihat oleh tim Anda.</p>
                <form x-on:submit.prevent="save()" class="space-y-2">
                    <label for="customer-panel-note-text" class="sr-only">Catatan internal</label>
                    <textarea id="customer-panel-note-text" x-model="text" rows="3" maxlength="2000" placeholder="Misalnya: suka sesi pagi, alergi parfum." class="w-full rounded-xl border border-bq-border bg-bq-surface px-3.5 py-2.5 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] tabular-nums text-bq-text-muted" x-text="text.length + '/2000'"></span>
                        <button type="submit" x-bind:disabled="saving" class="craft-btn inline-flex items-center rounded-xl bg-bq-primary px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-bq-primary/25 transition hover:bg-bq-primary-hover disabled:opacity-60" x-text="saving ? 'Menyimpan…' : 'Simpan catatan'">Simpan catatan</button>
                    </div>
                </form>
            </section>

            {{-- Booking history --}}
            <section aria-labelledby="customer-panel-bookings" class="rounded-xl border border-bq-border p-4">
                <h3 id="customer-panel-bookings" class="{{ $sectionHeading }} mb-2">Riwayat booking</h3>
                <p x-show="!data.bookings || data.bookings.length === 0" class="text-xs text-bq-text-muted">Belum ada booking.</p>
                <ul class="divide-y divide-bq-border">
                    <template x-for="item in (data.bookings || [])" x-bind:key="item.code">
                        <li>
                            <a
                                x-bind:href="item.href"
                                x-on:click="if (item.panel_url) { $event.preventDefault(); $dispatch('open-booking-panel', { url: item.panel_url }); }"
                                class="-mx-2 flex items-center justify-between gap-3 rounded-lg px-2 py-2.5 transition hover:bg-bq-background/60"
                            >
                                <span class="min-w-0 text-xs">
                                    <span class="block font-mono font-semibold text-bq-text" x-text="item.code"></span>
                                    <span class="block truncate text-bq-text-muted" x-text="item.service"></span>
                                    <span class="block tabular-nums text-bq-text-subtle"><span x-text="item.date"></span>, <span x-text="item.time"></span> WIB</span>
                                </span>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset" x-bind:class="{{ $tones }}" x-text="item.status.label"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </section>

            {{-- Payment history (empty for roles without revenue) --}}
            <section x-show="data.payments && data.payments.length" aria-labelledby="customer-panel-payments" class="rounded-xl border border-bq-border p-4">
                <h3 id="customer-panel-payments" class="{{ $sectionHeading }} mb-2">Riwayat pembayaran</h3>
                <ul class="divide-y divide-bq-border">
                    <template x-for="item in (data.payments || [])" x-bind:key="item.order_id">
                        <li class="flex items-center justify-between gap-3 py-2.5 text-xs">
                            <span class="min-w-0">
                                <span class="block truncate font-mono text-bq-text" x-text="item.order_id"></span>
                                <span class="block tabular-nums text-bq-text-subtle" x-text="item.date"></span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block font-semibold tabular-nums text-emerald-700" x-text="item.amount"></span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset" x-bind:class="{{ $tones }}" x-text="item.status.label"></span>
                            </span>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </template>
</x-owner.drawer>
