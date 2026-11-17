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
                  fields: { name: value }, reason: { name, label }|null }]      // only actions the server allows
    }
    Values are rendered as text only.
--}}
@php
    $panelBadge = "{ 'bg-bq-info-bg text-bq-info': %s === 'info', 'bg-bq-success-bg text-bq-success': %s === 'success', 'bg-bq-warning-bg text-bq-warning': %s === 'warning', 'bg-bq-danger-bg text-bq-danger': %s === 'danger', 'bg-bq-surface-2 text-bq-text-muted': !['info','success','warning','danger'].includes(%s) }";
    $badge = fn (string $expression) => sprintf($panelBadge, $expression, $expression, $expression, $expression, $expression);
    $bookingForm = $errors->any() && is_string(old('_form')) ? old('_form') : '';
    $reopenBooking = preg_match('/^booking-reason-([1-9][0-9]*)-/', $bookingForm, $bookingFormMatch)
        ? route('owner.bookings.panel', (int) $bookingFormMatch[1])
        : null;
@endphp

<x-owner.drawer
    id="owner-booking-panel"
    title="Detail booking"
    behaviour="ownerPanel('owner-booking-panel')"
    x-on:open-booking-panel.window="load($event)"
    :data-reopen-url="$reopenBooking"
    x-init="if ($el.dataset.reopenUrl) $nextTick(() => $dispatch('open-booking-panel', { url: $el.dataset.reopenUrl }))"
>
    <x-slot:heading>
        <span x-text="data ? data.code : 'Detail booking'">Detail booking</span>
    </x-slot:heading>

    <div x-show="loading" class="space-y-6 py-2" role="status"><span class="sr-only">Memuat detail booking…</span><x-ui.skeleton :lines="3" /><x-ui.skeleton :lines="4" /><x-ui.skeleton :lines="2" /></div>
    <div x-show="failed" x-cloak class="rounded-lg bg-bq-danger-bg px-4 py-3 text-sm text-bq-danger" role="alert">Detail booking tidak dapat dimuat. Coba lagi.</div>

    <template x-if="data">
        <div class="divide-y divide-bq-border [&>section]:py-5 [&>section:first-child]:pt-0">
            <section aria-labelledby="booking-panel-session">
                <div class="overflow-hidden rounded-xl border border-bq-primary-border bg-bq-primary-light">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-dashed border-bq-primary-border px-4 py-3">
                        <template x-if="data.payment.deadline">
                            <span class="bq-badge bg-bq-warning-bg text-bq-warning" x-text="data.status.label"></span>
                        </template>
                        <template x-if="!data.payment.deadline">
                            <span class="inline-flex items-center gap-2 text-bq-label">
                                <span aria-hidden="true" class="size-2 rounded-full"
                                    :class="{ 'bg-bq-success': data.status.tone === 'success', 'bg-bq-danger': data.status.tone === 'danger', 'bg-bq-primary': data.status.tone === 'info', 'bg-bq-text-muted': !['success', 'danger', 'info'].includes(data.status.tone) }"></span>
                                <span x-text="data.status.label"></span>
                            </span>
                        </template>
                        <span class="text-bq-label text-bq-primary" x-text="data.source"></span>
                    </div>
                    <div class="px-4 py-3">
                        <h3 id="booking-panel-session" class="sr-only">Sesi</h3>
                        <p class="text-base font-bold text-bq-ink" x-text="data.service"></p>
                        <ul class="mt-1 space-y-0.5">
                            <template x-for="(session, index) in data.sessions" x-bind:key="index">
                                <li class="bq-num grid gap-0.5">
                                    <span class="text-bq-small text-bq-text-muted" x-text="session.date"></span>
                                    <span class="font-bq-display text-bq-figure-small font-semibold text-bq-ink" x-text="session.time"></span>
                                </li>
                            </template>
                        </ul>
                        <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-bq-text-muted">
                            <span x-show="data.participants">Peserta: <span class="font-semibold text-bq-ink" x-text="data.participants"></span></span>
                            <span x-show="data.unit">Unit: <span class="font-semibold text-bq-ink" x-text="data.unit"></span></span>
                        </p>
                    </div>
                </div>
            </section>

            <section aria-labelledby="booking-panel-customer">
                <h3 id="booking-panel-customer" class="mb-2 text-bq-label text-bq-text-muted">Pelanggan</h3>
                <p class="text-base font-semibold text-bq-ink" x-text="data.customer.name"></p>
                <p class="text-sm text-bq-text-muted">
                    <span x-text="data.customer.phone"></span>
                    <a x-show="data.customer.whatsapp_url" x-bind:href="data.customer.whatsapp_url" target="_blank" rel="noopener" class="bq-link ml-2">WhatsApp</a>
                </p>
                <p x-show="data.customer.email" class="text-sm text-bq-text-muted" x-text="data.customer.email"></p>
                <button type="button" x-show="data.customer.panel_url" x-on:click="$dispatch('open-customer-panel', { url: data.customer.panel_url })" class="bq-link mt-1 text-sm">Lihat pelanggan</button>
            </section>

            <section aria-labelledby="booking-panel-payment">
                <h3 id="booking-panel-payment" class="mb-2 text-bq-label text-bq-text-muted">Pembayaran</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="bq-badge" x-bind:class="{{ $badge('data.payment.status.tone') }}" x-text="data.payment.status.label"></span>
                    <span class="text-sm text-bq-text-muted" x-text="data.payment.method"></span>
                </div>
                <dl class="mt-2 space-y-1 text-sm">
                    <template x-for="(line, index) in data.payment.breakdown" x-bind:key="index">
                        <div class="flex justify-between gap-4"><dt class="text-bq-text-muted" x-text="line.label"></dt><dd class="bq-num text-bq-text" x-text="line.amount"></dd></div>
                    </template>
                    <div class="flex justify-between gap-4 border-t border-bq-border pt-2 text-base font-bold text-bq-ink"><dt>Dibayar</dt><dd class="bq-num" x-text="data.payment.amount"></dd></div>
                    <div x-show="data.payment.paid_at" class="flex justify-between gap-4"><dt class="text-bq-text-muted">Waktu bayar</dt><dd x-text="data.payment.paid_at"></dd></div>
                    <div x-show="data.payment.deadline" class="flex justify-between gap-4"><dt class="text-bq-text-muted">Bayar sebelum</dt><dd x-text="data.payment.deadline"></dd></div>
                </dl>
                <template x-if="data.payment.refund">
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-bq-text-muted">Refund</span>
                        <span class="bq-badge" x-bind:class="{{ $badge('data.payment.refund.status.tone') }}" x-text="data.payment.refund.status.label"></span>
                        <span class="bq-num" x-text="data.payment.refund.amount"></span>
                    </div>
                </template>
                <p x-show="data.refund_reason" class="mt-1 text-sm text-bq-text-muted">Alasan pengajuan: <span x-text="data.refund_reason"></span></p>
                <p x-show="data.walkin_method" class="mt-1 text-sm text-bq-text-muted">Cara bayar: <span x-text="data.walkin_method"></span></p>
            </section>

            <section x-show="data.note" aria-labelledby="booking-panel-note">
                <h3 id="booking-panel-note" class="mb-1 text-bq-label text-bq-text-muted">Catatan pelanggan</h3>
                <p class="whitespace-pre-line text-sm text-bq-text-muted" x-text="data.note"></p>
            </section>

            <section aria-labelledby="booking-panel-history">
                <h3 id="booking-panel-history" class="mb-3 text-bq-label text-bq-text-muted">Riwayat</h3>
                <ol class="space-y-3 border-l-2 border-bq-border pl-4">
                    <template x-for="(entry, index) in data.history" x-bind:key="index">
                        <li class="relative text-sm">
                            <span class="absolute top-1.5 -left-[21px] h-2 w-2 rounded-full bg-bq-border-strong" aria-hidden="true"></span>
                            <p class="text-bq-ink" x-text="entry.text"></p>
                            <p class="text-bq-small text-bq-text-muted"><span x-text="entry.at"></span> · <span x-text="entry.actor"></span></p>
                        </li>
                    </template>
                </ol>
            </section>
        </div>
    </template>

    <x-slot:footer>
        <p x-show="data?.action_hint" class="w-full text-sm text-bq-text-muted" x-text="data?.action_hint"></p>
        <template x-if="data && data.actions && data.actions.length">
            <div class="flex w-full flex-col gap-2">
                <template x-for="(action, index) in data.actions" x-bind:key="index">
                    <div x-data="{ showReason: action.reason && action.form_key === @js($bookingForm) }">
                        <template x-if="action.reschedule">
                            <button type="button" class="bq-btn bq-btn-secondary w-full"
                                @click="hide(); $dispatch('open-owner-reschedule', { booking: action.reschedule })" x-text="action.label"></button>
                        </template>
                        <template x-if="action.url">
                            <form method="POST" x-bind:action="action.url" x-bind:id="'booking-panel-action-' + index" class="space-y-2">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" x-bind:value="action.method || 'POST'">
                                <template x-for="(value, name) in (action.fields || {})" :key="name">
                                    <input type="hidden" :name="name" :value="value">
                                </template>
                                <template x-if="action.reason">
                                    <div x-show="showReason">
                                        <input type="hidden" name="_form" :value="action.form_key">
                                        <label class="bq-label mb-1 block" x-bind:for="'booking-panel-reason-' + index" x-text="action.reason.label"></label>
                                        <textarea x-bind:id="'booking-panel-reason-' + index" x-bind:name="action.reason.name" :value="action.form_key === @js($bookingForm) ? @js(old('reason')) : ''" :disabled="!showReason" required maxlength="500" rows="2" class="bq-input w-full"></textarea>
                                        @if ($reopenBooking)
                                            <x-owner.field-error name="reason" x-cloak x-show="action.form_key === @js($bookingForm)" />
                                            <x-owner.field-error name="error" x-cloak x-show="action.form_key === @js($bookingForm)" />
                                        @endif
                                    </div>
                                </template>
                                <button
                                    type="button"
                                    x-on:click="
                                        const form = $el.form;
                                        if (action.reason && !showReason) {
                                            showReason = true;
                                            $nextTick(() => form.querySelector('textarea').focus());
                                            return;
                                        }
                                        if (!form.reportValidity()) return;
                                        if (action.confirm) {
                                            $dispatch('open-confirm', { title: action.confirm.title, message: action.confirm.message, formId: form.id, confirmText: action.label, tone: action.tone === 'danger' ? 'danger' : 'primary' });
                                        } else {
                                            form.requestSubmit();
                                        }
                                    "
                                    class="bq-btn w-full"
                                    x-bind:class="action.tone === 'danger' ? 'bq-btn-danger' : (data.actions.findIndex(item => item.url && item.tone !== 'danger') === index ? 'bq-btn-primary' : 'bq-btn-secondary')"
                                    x-text="action.label"
                                ></button>
                            </form>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </x-slot:footer>
</x-owner.drawer>
