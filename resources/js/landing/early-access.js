/**
 * Early access success popup (BQ-175): the server renders `<dialog data-early-access-dialog>` only
 * after a successful sign-up. Opened as a modal (focus moves inside, Esc closes, the page behind is
 * inert); closing plays the exit animation first.
 */
const initSuccessDialog = () => {
    const dialog = document.querySelector('[data-early-access-dialog]');
    if (!(dialog instanceof HTMLDialogElement) || typeof dialog.showModal !== 'function') {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const close = () => {
        if (!dialog.open || dialog.classList.contains('is-closing')) {
            return;
        }
        if (prefersReducedMotion) {
            dialog.close();
            return;
        }
        dialog.classList.add('is-closing');
        dialog.addEventListener(
            'animationend',
            () => {
                dialog.classList.remove('is-closing');
                dialog.close();
            },
            { once: true },
        );
    };

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });
    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', close));

    dialog.showModal();
    dialog.querySelector('[autofocus]')?.focus({ preventScroll: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSuccessDialog);
} else {
    initSuccessDialog();
}
