/*
 * Owner shared building blocks (BQ-129, owner-portal.md §9).
 *
 * Loaded before Alpine starts. Provides:
 *  - ownerDialog(id): dialog and drawer behaviour (role="dialog" markup lives in Blade):
 *    focus moves into the dialog, Tab stays inside it, Escape closes it, and focus returns
 *    to the element that opened it. Open with
 *      $dispatch('open-dialog', { id: 'my-dialog' })
 *    and close with $dispatch('close-dialog', { id: 'my-dialog' }).
 *  - ownerPanel(kind): the single booking/customer panel. Any entry point opens it with
 *      $dispatch('open-booking-panel', { url: '<json url>' })
 *      $dispatch('open-customer-panel', { url: '<json url>' })
 *    The JSON contract is documented in the panel components; values are rendered with
 *    x-text only, never as HTML.
 *  - ownerToasts(): the toast region; any script can call
 *      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: '…' } }))
 */
(function () {
    const FOCUSABLE = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    function dialogBehaviour(id) {
        return {
            dialogId: id,
            open: false,
            returnTo: null,

            show(trigger) {
                this.returnTo = trigger instanceof HTMLElement ? trigger : document.activeElement;
                this.open = true;
                this.$nextTick(() => this.focusInside(0));
            },

            // The panel can still be hidden on the first tick (x-show on the panel itself), so retry shortly.
            // setTimeout rather than requestAnimationFrame: frames do not run while the tab is not painting.
            focusInside(attempt) {
                const panel = this.$refs.panel;
                if (!this.open || !panel) return;
                if (panel.getClientRects().length === 0) {
                    if (attempt < 60) setTimeout(() => this.focusInside(attempt + 1), 16);
                    return;
                }
                (this.focusables()[0] || panel).focus();
            },

            hide() {
                if (!this.open) return;
                this.open = false;
                this.$dispatch('dialog-closed', { id: this.dialogId });
                const target = this.returnTo;
                this.returnTo = null;
                if (target && typeof target.focus === 'function' && document.contains(target)) {
                    this.$nextTick(() => target.focus());
                }
            },

            focusables() {
                if (!this.$refs.panel) return [];
                return Array.from(this.$refs.panel.querySelectorAll(FOCUSABLE))
                    .filter((element) => element.getClientRects().length > 0);
            },

            trap(event) {
                if (event.key !== 'Tab') return;
                const items = this.focusables();
                if (items.length === 0) {
                    event.preventDefault();
                    this.$refs.panel?.focus();
                    return;
                }
                const first = items[0];
                const last = items[items.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            },

            onOpenEvent(event) {
                if (event.detail && event.detail.id === this.dialogId) this.show(event.detail.trigger);
            },

            onCloseEvent(event) {
                if (!event.detail || !event.detail.id || event.detail.id === this.dialogId) this.hide();
            },
        };
    }

    // Shared with components that extend the dialog behaviour in their own x-data (confirm dialog).
    window.ownerDialogBehaviour = dialogBehaviour;

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('ownerDialog', (id) => dialogBehaviour(id));

        window.Alpine.data('ownerMenu', () => ({
            open: false,

            items() {
                return Array.from(this.$refs.items.querySelectorAll('[role="menuitem"]:not([disabled]):not([aria-disabled="true"])'))
                    .filter((item) => item.getClientRects().length > 0);
            },

            show(last = false) {
                this.open = true;
                this.$nextTick(() => this.focusItem(last));
            },

            close(restoreFocus = false) {
                if (!this.open) return;
                this.open = false;
                if (restoreFocus) this.$refs.trigger.focus();
            },

            leave(event) {
                if (!this.$refs.items.contains(event.relatedTarget)) this.close();
            },

            focusItem(last = false) {
                this.items().at(last ? -1 : 0)?.focus();
            },

            move(direction) {
                const items = this.items();
                const index = items.indexOf(document.activeElement);
                if (items.length) items[(index + direction + items.length) % items.length].focus();
            },
        }));

        window.Alpine.data('ownerPanel', (id) => ({
            ...dialogBehaviour(id),
            loading: false,
            failed: false,
            data: null,
            requestNumber: 0,
            noteDirty: false,
            afterDiscard: null,

            hide(next = null) {
                if (this.noteDirty) {
                    this.afterDiscard = next;
                    this.$dispatch('open-confirm', {
                        title: 'Buang perubahan catatan?',
                        message: 'Perubahan catatan pelanggan belum disimpan.',
                        formId: 'discard-customer-note',
                        confirmText: 'Buang perubahan',
                    });
                    return;
                }
                dialogBehaviour(id).hide.call(this);
                next?.();
            },

            discardNote() {
                const next = this.afterDiscard;
                this.afterDiscard = null;
                this.noteDirty = false;
                this.hide(next);
            },

            async load(event) {
                const url = event.detail && event.detail.url;
                if (!url) return;
                if (this.noteDirty) {
                    this.hide(() => this.load(event));
                    return;
                }
                const requestNumber = ++this.requestNumber;
                this.data = null;
                this.failed = false;
                this.loading = true;
                this.show(event.detail.trigger);
                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (requestNumber !== this.requestNumber) return;
                    if (!response.ok) throw new Error('panel request failed');
                    this.data = await response.json();
                } catch (error) {
                    if (requestNumber === this.requestNumber) this.failed = true;
                } finally {
                    if (requestNumber === this.requestNumber) this.loading = false;
                }
            },
        }));

        const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const jsonHeaders = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

        // BQ-137 (BQ-100 finding 3): the customer note editor in the customer panel. One save at a
        // time; the result is reported with a toast and the panel keeps the saved text.
        window.Alpine.data('ownerPayoutSubmission', () => ({
            payoutErrors: {},
            payoutUnconfirmed: false,
            payoutSubmitting: false,
            payoutPayload: null,

            async submitPayout(form) {
                if (this.payoutSubmitting) return;
                this.payoutSubmitting = true;
                this.payoutErrors = {};
                this.payoutPayload ??= new FormData(form);
                let response;
                try {
                    response = await fetch(form.action, {
                        method: 'POST',
                        body: this.payoutPayload,
                        headers: jsonHeaders,
                        signal: AbortSignal.timeout(30000),
                    });
                    this.payoutUnconfirmed = response.status >= 500;
                    if (this.payoutUnconfirmed || response.status === 401 || response.status === 419) return;
                    this.payoutPayload = null;
                    if (response.status === 422) {
                        this.payoutErrors = (await response.json()).errors || {};
                        if (this.payoutErrors.jumlah) this.step = 1;
                        else if (this.payoutErrors.payout_account_id) this.step = 2;
                        return;
                    }
                    if (!response.ok) {
                        this.payoutErrors = { request_key: ['Pengajuan tidak dapat dikirim. Muat ulang halaman.'] };
                        return;
                    }
                    window.location.assign((await response.json()).redirect);
                } catch (error) {
                    this.payoutUnconfirmed = !response || error.name === 'TimeoutError' || error.name === 'AbortError';
                    if (!this.payoutUnconfirmed) {
                        this.payoutErrors = { request_key: ['Respons pengajuan tidak dapat dibaca. Muat ulang halaman.'] };
                    }
                } finally {
                    this.payoutSubmitting = false;
                    if (this.payoutUnconfirmed) {
                        this.step = 3;
                        this.$nextTick(() => this.$refs.payoutRetry?.focus());
                    }
                }
            },
        }));

        window.Alpine.data('ownerCustomerNote', (customer) => ({
            text: customer.note || '',
            saving: false,

            async save() {
                if (this.saving) return;
                this.saving = true;
                const submitted = this.text;
                try {
                    const response = await fetch(customer.note_url, {
                        method: 'POST',
                        headers: { ...jsonHeaders, 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                        body: JSON.stringify({ customer_identifier: customer.identifier, notes: submitted }),
                    });
                    if (response.status === 419 || response.status === 401) return;
                    if (!response.ok) throw new Error('note save failed');
                    customer.note = submitted;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Catatan disimpan.' } }));
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Catatan gagal disimpan. Isian Anda masih ada.', action: () => this.save() } }));
                } finally {
                    this.saving = false;
                }
            },
        }));

        window.Alpine.data('ownerDayRail', () => ({
            now: '',
            clockTimer: null,

            start(row) {
                return (String(row.start || row.time).match(/\d{2}[:.]\d{2}/) || ['99:99'])[0].replace('.', ':');
            },
            get railRows() {
                const empty = (this.live.empty_slots || []).map(slot => ({ ...slot, empty: true }));
                return [...this.live.today, ...empty].sort((a, b) => this.start(a).localeCompare(this.start(b)));
            },
            get nowIndex() {
                const index = this.railRows.findIndex(row => this.start(row) > this.now);
                return index === -1 ? this.railRows.length : index;
            },
            init() {
                const tick = () => {
                    this.now = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date());
                };
                tick();
                this.clockTimer = setInterval(tick, 30000);
            },
            destroy() {
                clearInterval(this.clockTimer);
            },
        }));

        // BQ-136 (AC-BRD-06): Beranda sections 2–4. Refreshed every 60 seconds only while the tab is
        // visible; the timer stops when it is hidden and the data refreshes once on return. The page
        // renders the data with x-text, never as HTML.
        window.Alpine.data('ownerBeranda', (initial, url) => ({
            live: initial,
            timer: null,
            requestNumber: 0,

            init() {
                this.onVisibility = () => {
                    if (document.visibilityState === 'visible') {
                        this.refresh();
                        this.start();
                    } else {
                        this.stop();
                    }
                };
                document.addEventListener('visibilitychange', this.onVisibility);
                if (document.visibilityState === 'visible') this.start();
            },

            destroy() {
                this.stop();
                document.removeEventListener('visibilitychange', this.onVisibility);
            },

            start() {
                if (this.timer === null) this.timer = setInterval(() => this.refresh(), 60000);
            },

            stop() {
                if (this.timer !== null) clearInterval(this.timer);
                this.timer = null;
            },

            async refresh() {
                const requestNumber = ++this.requestNumber;
                try {
                    const response = await fetch(url, { headers: jsonHeaders });
                    if (!response.ok || requestNumber !== this.requestNumber) return;
                    const body = await response.json();
                    if (body && body.data) this.live = body.data;
                } catch (error) {
                    // Keep the last data; the next visible tick retries.
                }
            },
        }));

        // BQ-136: the booking link card. Copy or share, then record the share for the new owner guide.
        window.Alpine.data('ownerBookingLink', (link, markUrl) => ({
            copied: false,
            canShare: typeof navigator.share === 'function',

            async copy() {
                try {
                    await navigator.clipboard.writeText(link.url);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Link booking disalin.' } }));
                    this.mark();
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Link tidak dapat disalin. Salin secara manual.' } }));
                }
            },

            async share() {
                try {
                    await navigator.share({ text: link.share_text, url: link.url });
                    this.mark();
                } catch (error) {
                    // The member closed the share sheet.
                }
            },

            mark() {
                fetch(markUrl, { method: 'POST', headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() } }).catch(() => {});
            },
        }));

        // BQ-142: copy a ready-made text (promotion materials) with a toast as feedback.
        window.Alpine.data('ownerCopyText', (text) => ({
            copied: false,

            async copy() {
                try {
                    await navigator.clipboard.writeText(text);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Teks disalin.' } }));
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Teks tidak dapat disalin. Salin secara manual.' } }));
                }
            },
        }));

        // BQ-128: global search. Results open the booking/customer panel when its endpoint exists,
        // otherwise the Booking or Pelanggan list filtered to the result. Stale responses are ignored.
        window.Alpine.data('ownerSearch', (url) => ({
            query: '',
            open: false,
            loading: false,
            failed: false,
            results: { bookings: [], customers: [] },
            requestNumber: 0,
            activeIndex: -1,

            get entries() {
                return [
                    ...this.results.bookings.map(result => ({ result, kind: 'booking' })),
                    ...this.results.customers.map(result => ({ result, kind: 'customer' })),
                ];
            },

            get activeId() {
                return this.open && this.activeIndex >= 0 ? 'owner-search-option-' + this.activeIndex : null;
            },

            move(step) {
                if (this.loading || this.entries.length === 0) return;
                this.open = true;
                const last = this.entries.length - 1;
                if (this.activeIndex < 0) this.activeIndex = step > 0 ? 0 : last;
                else this.activeIndex = Math.max(0, Math.min(last, this.activeIndex + step));
                this.$nextTick(() => document.getElementById(this.activeId)?.scrollIntoView({ block: 'nearest' }));
            },

            async search() {
                const term = this.query.trim();
                const requestNumber = ++this.requestNumber;
                this.activeIndex = -1;
                this.results = { bookings: [], customers: [] };
                if (term.length < 2) {
                    this.open = false;
                    this.loading = false;
                    return;
                }
                this.open = true;
                this.loading = true;
                this.failed = false;
                try {
                    const target = new URL(url, window.location.origin);
                    target.searchParams.set('q', term);
                    const response = await fetch(target, { headers: jsonHeaders });
                    if (!response.ok) throw new Error('search failed');
                    const data = await response.json();
                    if (requestNumber !== this.requestNumber || this.query.trim() !== term) return;
                    this.results = { bookings: data.bookings || [], customers: data.customers || [] };
                } catch (error) {
                    if (requestNumber === this.requestNumber) this.failed = true;
                } finally {
                    if (requestNumber === this.requestNumber) this.loading = false;
                }
            },

            choose(result, kind) {
                this.close();
                if (result.panel_url) {
                    this.$dispatch(kind === 'booking' ? 'open-booking-panel' : 'open-customer-panel', { url: result.panel_url });
                } else if (result.href) {
                    window.location.assign(result.href);
                }
            },

            openSelection() {
                if (!this.open || this.loading) return;
                const entry = this.entries[Math.max(this.activeIndex, 0)];
                if (entry) this.choose(entry.result, entry.kind);
            },

            close() {
                this.open = false;
                this.activeIndex = -1;
            },
        }));

        // BQ-128 (BQ-090 findings): one initialisation; the list loads when the dropdown opens;
        // the unread count is refreshed only while the tab is visible.
        window.Alpine.data('ownerBell', (config) => ({
            open: false,
            loading: false,
            loaded: false,
            items: [],
            unread: config.unread || 0,
            timer: null,

            init() {
                this.timer = setInterval(() => {
                    if (document.visibilityState === 'visible') this.refresh(true);
                }, 120000);
            },

            destroy() {
                clearInterval(this.timer);
            },

            toggle() {
                this.open = !this.open;
                if (this.open && !this.loaded) this.refresh(false);
            },

            async refresh(background) {
                if (!background) this.loading = true;
                try {
                    const target = new URL(config.listUrl, window.location.origin);
                    target.searchParams.set('limit', '8');
                    const response = await fetch(target, { headers: jsonHeaders });
                    if (!response.ok) return;
                    const data = await response.json();
                    if (!data.success) return;
                    this.unread = data.unread_count;
                    if (!background || this.loaded) {
                        this.items = data.data;
                        this.loaded = true;
                    }
                } catch (error) {
                    // The count stays as rendered; the full list is on the Notifikasi page.
                } finally {
                    this.loading = false;
                }
            },

            async openItem(item) {
                try {
                    const response = await fetch(config.readUrl.replace('__ID__', encodeURIComponent(item.id)), {
                        method: 'POST',
                        headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() },
                    });
                    if (response.ok) {
                        const result = await response.json();
                        window.location.assign(result.target);
                        return;
                    }
                } catch (error) {
                    // Keep the item accessible from the full notification centre.
                }
                window.location.assign(config.listUrl);
            },

            async markAllRead() {
                this.unread = 0;
                this.items.forEach((item) => { item.is_read = true; });
                try {
                    await fetch(config.readAllUrl, { method: 'POST', headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() } });
                } catch (error) {
                    // See openItem.
                }
            },
        }));

        window.Alpine.data('ownerWalkInForm', (slotsUrl, config) => ({
            date: config.date || config.today,
            service: '',
            selectedSlot: String(config.slot ?? ''),
            quantities: Object.fromEntries(Array.isArray(config.addOns) ? config.addOns.map(item => [item.id, item.quantity]) : []),
            services: [],
            slots: [],
            customers: [],
            errors: {},
            conflict: null,
            loading: false,
            failed: false,
            saving: false,
            requestNumber: 0,
            searchNumber: 0,

            get serviceSlots() {
                return this.slots.filter(slot => String(slot.service) === String(this.service));
            },
            get picked() {
                return this.serviceSlots.find(slot => String(slot.id) === String(this.selectedSlot));
            },
            get addOns() {
                return this.picked?.add_ons ?? [];
            },
            get selectedAddOns() {
                return this.addOns.filter(item => Number(this.quantities[item.id]) > 0);
            },
            get capacity() {
                return this.services.find(service => String(service.id) === String(this.service))?.capacity ?? 1;
            },

            start(event) {
                if (!slotsUrl || event.detail?.id !== 'owner-walkin-dialog') return;
                const slot = event.detail.slot;
                if (slot) {
                    this.date = slot.date;
                    this.service = String(slot.service_id);
                    this.pick(slot.id);
                }
                this.load();
            },
            pick(id) {
                this.selectedSlot = String(id);
                this.conflict = null;
            },
            async load() {
                const sequence = ++this.requestNumber;
                this.loading = Boolean(this.date);
                this.failed = false;
                if (!this.date) return;
                try {
                    const url = new URL(slotsUrl, window.location.origin);
                    url.searchParams.set('date', this.date);
                    url.searchParams.set('picker', '1');
                    const response = await fetch(url, { headers: jsonHeaders });
                    if (!response.ok) throw new Error();
                    const data = await response.json();
                    if (sequence !== this.requestNumber) return;
                    this.services = data.services;
                    this.slots = data.slots;
                    const preferred = this.slots.find(slot => String(slot.id) === this.selectedSlot);
                    this.service = String(preferred?.service ?? (this.service || this.services[0]?.id || ''));
                    if (!preferred?.available) this.selectedSlot = '';
                } catch {
                    if (sequence !== this.requestNumber) return;
                    this.slots = [];
                    this.failed = true;
                } finally {
                    if (sequence === this.requestNumber) this.loading = false;
                }
            },
            async suggest(input) {
                const query = input.value.trim();
                const sequence = ++this.searchNumber;
                if (!config.searchUrl || query.length < 2) {
                    this.customers = [];
                    return;
                }
                try {
                    const url = new URL(config.searchUrl, window.location.origin);
                    url.searchParams.set('q', query);
                    const response = await fetch(url, { headers: jsonHeaders });
                    const data = response.ok ? await response.json() : {};
                    if (sequence === this.searchNumber && input.value.trim() === query) this.customers = data.customers || [];
                } catch {
                    if (sequence === this.searchNumber) this.customers = [];
                }
            },
            prefill(customer) {
                ++this.searchNumber;
                this.customers = [];
                this.$refs.name.value = customer.name ?? customer.namapelanggan ?? '';
                this.$refs.phone.value = customer.phone ?? customer.nomorhp ?? '';
                this.$refs.email.value = customer.email ?? '';
            },
            async save(event) {
                if (!slotsUrl) return;
                event.preventDefault();
                if (this.saving) return;
                const form = event.currentTarget;
                const fields = new FormData(form);
                const attempted = this.picked;
                this.saving = true;
                this.errors = {};
                this.conflict = null;
                try {
                    const response = await fetch(form.action, { method: 'POST', body: fields, headers: jsonHeaders });
                    if (response.status === 419 || response.status === 401) return;
                    if (response.status === 409) {
                        const payload = await response.json();
                        this.selectedSlot = '';
                        this.conflict = { start: attempted?.start, alternatives: payload.alternatives };
                        await this.load();
                        return;
                    }
                    if (response.status === 422) {
                        this.errors = (await response.json()).errors || {};
                        return;
                    }
                    if (!response.ok) throw new Error();
                    window.location.reload();
                } catch {
                    this.$dispatch('toast', { type: 'error', message: 'Status penyimpanan belum dapat diperiksa. Isian Anda masih ada.', action: () => this.$refs.submit.focus() });
                } finally {
                    this.saving = false;
                }
            },
        }));

        window.Alpine.data('ownerToasts', (initial) => ({
            toasts: [],
            nextId: 0,

            init() {
                (initial || []).forEach((toast) => this.add(toast.type, toast.message));
            },

            add(type, message, action = null) {
                if (!message) return;
                const id = ++this.nextId;
                this.toasts.push({ id, type: type || 'success', message: String(message), action });
                setTimeout(() => this.remove(id), action ? 10000 : 5000);
            },

            remove(id) {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            },
        }));
    });
})();
