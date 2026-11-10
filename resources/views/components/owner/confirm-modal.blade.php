{{--
    Confirm dialog (BQ-129, owner-portal.md §9): replaces native confirm(). Open with
    $dispatch('open-confirm', { title, message, formId | actionUrl + method, confirmText, cancelText, tone })
    `tone` is 'danger' (default, for destructive actions) or 'primary'. Accessible like x-owner.dialog.
--}}
<div
    id="owner-confirm-dialog"
    x-data="{
        ...ownerDialogBehaviour('owner-confirm-dialog'),
        title: '',
        message: '',
        formId: null,
        actionUrl: null,
        method: 'POST',
        confirmText: 'Ya, Hapus',
        cancelText: 'Batal',
        tone: 'danger',
        accept() {
            if (this.formId) {
                const form = document.getElementById(this.formId);
                if (form && form.reportValidity()) {
                    this.returnTo = null;
                    this.hide();
                    form.requestSubmit();
                }
                return;
            }
            if (this.actionUrl) {
                this.returnTo = null;
                this.$refs.actionForm.submit();
            }
        },
    }"
    x-on:open-confirm.window="
        title = $event.detail.title || 'Konfirmasi tindakan';
        message = $event.detail.message || 'Apakah Anda yakin ingin melanjutkan?';
        formId = $event.detail.formId || null;
        actionUrl = $event.detail.actionUrl || null;
        method = $event.detail.method || 'POST';
        tone = $event.detail.tone === 'primary' ? 'primary' : 'danger';
        confirmText = $event.detail.confirmText || (tone === 'danger' ? 'Ya, Hapus' : 'Lanjutkan');
        cancelText = $event.detail.cancelText || 'Batal';
        show($event.detail.trigger);
    "
>
    <div x-show="open" x-cloak class="fixed inset-0 z-[100] bg-bq-ink/40" x-on:click="hide()" aria-hidden="true"></div>

    <div x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 pointer-events-none">
        <div
            x-ref="panel"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="owner-confirm-dialog-title"
            aria-describedby="owner-confirm-dialog-message"
            tabindex="-1"
            x-on:keydown="trap($event)"
            x-on:keydown.escape.stop="hide()"
            class="pointer-events-auto w-full max-w-sm rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xl focus:outline-none"
        >
            <h2 id="owner-confirm-dialog-title" class="text-base font-semibold text-bq-text" x-text="title"></h2>
            <p id="owner-confirm-dialog-message" class="mt-2 text-sm text-bq-text-muted" x-text="message"></p>

            <form x-ref="actionForm" method="POST" x-bind:action="actionUrl" class="hidden">
                @csrf
                <input type="hidden" name="_method" x-bind:value="method">
            </form>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="hide()" class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2 text-sm font-medium text-bq-text hover:bg-bq-background" x-text="cancelText"></button>
                <button
                    type="button"
                    x-on:click="accept()"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-white"
                    x-bind:class="tone === 'danger' ? 'bg-bq-danger hover:opacity-90' : 'bg-bq-primary hover:bg-bq-primary-hover'"
                    x-text="confirmText"
                ></button>
            </div>
        </div>
    </div>
</div>
