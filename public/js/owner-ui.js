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

        window.Alpine.data('ownerPanel', (id) => ({
            ...dialogBehaviour(id),
            loading: false,
            failed: false,
            data: null,
            requestNumber: 0,

            async load(event) {
                const url = event.detail && event.detail.url;
                if (!url) return;
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

        // BQ-128: global search. Results open the booking/customer panel when its endpoint exists,
        // otherwise the Booking or Pelanggan list filtered to the result. Stale responses are ignored.
        window.Alpine.data('ownerSearch', (url) => ({
            query: '',
            open: false,
            loading: false,
            failed: false,
            results: { bookings: [], customers: [] },
            requestNumber: 0,

            async search() {
                const term = this.query.trim();
                const requestNumber = ++this.requestNumber;
                if (term.length < 2) {
                    this.open = false;
                    this.results = { bookings: [], customers: [] };
                    return;
                }
                this.open = true;
                this.loading = true;
                this.failed = false;
                try {
                    const target = new URL(url, window.location.origin);
                    target.searchParams.set('q', term);
                    const response = await fetch(target, { headers: jsonHeaders });
                    if (requestNumber !== this.requestNumber) return;
                    if (!response.ok) throw new Error('search failed');
                    const data = await response.json();
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

            openFirst() {
                const first = this.results.bookings[0] || this.results.customers[0];
                if (first) this.choose(first, this.results.bookings[0] ? 'booking' : 'customer');
            },

            close() {
                this.open = false;
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
                if (!item.is_read) {
                    item.is_read = true;
                    this.unread = Math.max(0, this.unread - 1);
                    try {
                        await fetch(config.readUrl.replace('__ID__', encodeURIComponent(item.id)), {
                            method: 'POST',
                            headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() },
                        });
                    } catch (error) {
                        // Reading the item still works; the server state catches up on the next refresh.
                    }
                }
                if (item.url) window.location.assign(item.url);
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

        // BQ-128: "+ Booking" picks a date and loads that day's free slots, first when its dialog opens.
        window.Alpine.data('ownerWalkInSlots', (url, today) => ({
            today,
            date: today,
            slots: [],
            loading: false,
            loaded: false,
            failed: false,
            requestNumber: 0,

            loadWhenOpened(event) {
                const id = event.detail && event.detail.id;
                if (!this.loaded && id && this.$el.closest('#' + CSS.escape(id))) this.load();
            },

            async load() {
                if (!this.date) return;
                const requestNumber = ++this.requestNumber;
                this.loading = true;
                this.failed = false;
                try {
                    const target = new URL(url, window.location.origin);
                    target.searchParams.set('date', this.date);
                    const response = await fetch(target, { headers: jsonHeaders });
                    if (requestNumber !== this.requestNumber) return;
                    if (!response.ok) throw new Error('slots failed');
                    this.slots = (await response.json()).slots || [];
                    this.loaded = true;
                } catch (error) {
                    if (requestNumber === this.requestNumber) {
                        this.slots = [];
                        this.failed = true;
                    }
                } finally {
                    if (requestNumber === this.requestNumber) this.loading = false;
                }
            },
        }));

        window.Alpine.data('ownerToasts', (initial) => ({
            toasts: [],
            nextId: 0,

            init() {
                (initial || []).forEach((toast) => this.add(toast.type, toast.message));
            },

            add(type, message) {
                if (!message) return;
                const id = ++this.nextId;
                this.toasts.push({ id, type: type || 'success', message: String(message) });
                setTimeout(() => this.remove(id), 5000);
            },

            remove(id) {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            },
        }));
    });
})();
