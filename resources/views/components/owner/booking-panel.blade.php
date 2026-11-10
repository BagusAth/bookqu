{{--
    The one booking panel (BQ-129, owner-portal.md §8.4, rule 3, AC-PNL-06). Rendered once by the
    owner layout. Every entry point opens it with
        $dispatch('open-booking-panel', { url: '<json url>', trigger: $el })
    The JSON endpoint (BQ-135) answers, for an authorized booking:
    {
      code, source, service,
      status: { label, tone },                       // App\Support\StatusBadge
      sessions: [{ date, time }],                     // every slot (BookingTimeRange labels)
      participants: int|null, unit: string|null,
      customer: { name, phone, whatsapp_url, email, panel_url },
      payment: { status: { label, tone }, method, amount, breakdown: [{ label, amount }],
                 paid_at, deadline, refund: { status: { label, tone }, amount }|null },
      note, walkin_method,
      history: [{ at, text, actor }],                 // actor = "Nama (Peran)", §5.3
      actions: [{ label, url, method, tone, confirm: { title, message }|null,
                  reason: { name, label }|null }]      // only actions the server allows
    }
    Values are rendered as text only.
--}}
@php
    $panelBadge = "{ 'bg-bq-info-bg text-bq-info': %s === 'info', 'bg-bq-success-bg text-bq-success': %s === 'success', 'bg-bq-warning-bg text-bq-warning': %s === 'warning', 'bg-bq-danger-bg text-bq-danger': %s === 'danger', 'bg-bq-background text-bq-text-muted': !['info','success','warning','danger'].includes(%s) }";
    $badge = fn (string $expression) => sprintf($panelBadge, $expression, $expression, $expression, $expression, $expression);
@endphp

<x-owner.drawer
    id="owner-booking-panel"
    title="Detail booking"
    behaviour="ownerPanel('owner-booking-panel')"
    x-on:open-booking-panel.window="load($event)"
>
    <x-slot:heading>
        <span x-text="data ? data.code : 'Detail booking'">Detail booking</span>
    </x-slot:heading>

    <div x-show="loading" class="py-10 text-center text-sm text-bq-text-muted" role="status">Memuat detail booking…</div>
    <div x-show="failed" x-cloak class="rounded-lg bg-bq-danger-bg px-4 py-3 text-sm text-bq-danger" role="alert">Detail booking tidak dapat dimuat. Coba lagi.</div>

    <template x-if="data">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" x-bind:class="{{ $badge('data.status.tone') }}" x-text="data.status.label"></span>
                <span class="text-sm text-bq-text-muted" x-text="data.source"></span>
            </div>

            <section aria-labelledby="booking-panel-session">
                <h3 id="booking-panel-session" class="mb-2 text-sm font-semibold text-bq-text">Sesi</h3>
                <p class="text-sm text-bq-text" x-text="data.service"></p>
                <ul class="mt-1 space-y-0.5">
                    <template x-for="(session, index) in data.sessions" x-bind:key="index">
                        <li class="text-sm tabular-nums text-bq-text-muted"><span x-text="session.date"></span>, <span x-text="session.time"></span> WIB</li>
                    </template>
                </ul>
                <p x-show="data.participants" class="mt-1 text-sm text-bq-text-muted">Peserta: <span x-text="data.participants"></span></p>
                <p x-show="data.unit" class="mt-1 text-sm text-bq-text-muted">Unit: <span x-text="data.unit"></span></p>
            </section>

            <section aria-labelledby="booking-panel-customer">
                <h3 id="booking-panel-customer" class="mb-2 text-sm font-semibold text-bq-text">Pelanggan</h3>
                <p class="text-sm text-bq-text" x-text="data.customer.name"></p>
                <p class="text-sm text-bq-text-muted">
                    <span x-text="data.customer.phone"></span>
                    <a x-show="data.customer.whatsapp_url" x-bind:href="data.customer.whatsapp_url" target="_blank" rel="noopener" class="ml-2 text-bq-primary hover:text-bq-primary-hover">WhatsApp</a>
                </p>
                <p x-show="data.customer.email" class="text-sm text-bq-text-muted" x-text="data.customer.email"></p>
                <button type="button" x-show="data.customer.panel_url" x-on:click="$dispatch('open-customer-panel', { url: data.customer.panel_url })" class="mt-1 text-sm text-bq-primary hover:text-bq-primary-hover">Lihat pelanggan</button>
            </section>

            <section aria-labelledby="booking-panel-payment">
                <h3 id="booking-panel-payment" class="mb-2 text-sm font-semibold text-bq-text">Pembayaran</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" x-bind:class="{{ $badge('data.payment.status.tone') }}" x-text="data.payment.status.label"></span>
                    <span class="text-sm text-bq-text-muted" x-text="data.payment.method"></span>
                </div>
                <dl class="mt-2 space-y-1 text-sm">
                    <template x-for="(line, index) in data.payment.breakdown" x-bind:key="index">
                        <div class="flex justify-between gap-4"><dt class="text-bq-text-muted" x-text="line.label"></dt><dd class="tabular-nums text-bq-text" x-text="line.amount"></dd></div>
                    </template>
                    <div class="flex justify-between gap-4 font-semibold"><dt>Dibayar</dt><dd class="tabular-nums" x-text="data.payment.amount"></dd></div>
                    <div x-show="data.payment.paid_at" class="flex justify-between gap-4"><dt class="text-bq-text-muted">Waktu bayar</dt><dd x-text="data.payment.paid_at"></dd></div>
                    <div x-show="data.payment.deadline" class="flex justify-between gap-4"><dt class="text-bq-text-muted">Batas bayar</dt><dd x-text="data.payment.deadline"></dd></div>
                </dl>
                <template x-if="data.payment.refund">
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-bq-text-muted">Refund</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" x-bind:class="{{ $badge('data.payment.refund.status.tone') }}" x-text="data.payment.refund.status.label"></span>
                        <span class="tabular-nums" x-text="data.payment.refund.amount"></span>
                    </div>
                </template>
                <p x-show="data.walkin_method" class="mt-1 text-sm text-bq-text-muted">Metode walk-in: <span x-text="data.walkin_method"></span></p>
            </section>

            <section x-show="data.note" aria-labelledby="booking-panel-note">
                <h3 id="booking-panel-note" class="mb-1 text-sm font-semibold text-bq-text">Catatan pelanggan</h3>
                <p class="whitespace-pre-line text-sm text-bq-text-muted" x-text="data.note"></p>
            </section>

            <section aria-labelledby="booking-panel-history">
                <h3 id="booking-panel-history" class="mb-2 text-sm font-semibold text-bq-text">Riwayat</h3>
                <ol class="space-y-2">
                    <template x-for="(entry, index) in data.history" x-bind:key="index">
                        <li class="text-sm">
                            <p class="text-bq-text" x-text="entry.text"></p>
                            <p class="text-xs text-bq-text-muted"><span x-text="entry.at"></span> · <span x-text="entry.actor"></span></p>
                        </li>
                    </template>
                </ol>
            </section>
        </div>
    </template>

    <x-slot:footer>
        <template x-if="data && data.actions && data.actions.length">
            <div class="flex w-full flex-col gap-3">
                <template x-for="(action, index) in data.actions" x-bind:key="index">
                    <form method="POST" x-bind:action="action.url" x-bind:id="'booking-panel-action-' + index" class="space-y-2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" x-bind:value="action.method || 'POST'">
                        <template x-if="action.reason">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-bq-text" x-bind:for="'booking-panel-reason-' + index" x-text="action.reason.label"></label>
                                <textarea x-bind:id="'booking-panel-reason-' + index" x-bind:name="action.reason.name" required maxlength="500" rows="2" class="w-full rounded-lg border border-bq-border px-3 py-2 text-sm"></textarea>
                            </div>
                        </template>
                        <button
                            type="button"
                            x-on:click="
                                const form = $el.form;
                                if (!form.reportValidity()) return;
                                if (action.confirm) {
                                    $dispatch('open-confirm', { title: action.confirm.title, message: action.confirm.message, formId: form.id, confirmText: action.label, tone: action.tone === 'danger' ? 'danger' : 'primary' });
                                } else {
                                    form.requestSubmit();
                                }
                            "
                            class="w-full rounded-lg px-4 py-2 text-sm font-medium"
                            x-bind:class="action.tone === 'danger' ? 'bg-bq-danger text-white hover:opacity-90' : 'border border-bq-border bg-bq-surface text-bq-text hover:bg-bq-background'"
                            x-text="action.label"
                        ></button>
                    </form>
                </template>
            </div>
        </template>
    </x-slot:footer>
</x-owner.drawer>
