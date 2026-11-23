/**
 * Scroll reveal (BQ-175): adds `is-revealed` to every `[data-reveal]` element whenever it enters the
 * viewport and removes it once the element is fully out of view, so the animation replays on every
 * pass; `resources/css/landing/reveal.css` animates the change. `data-reveal-delay`
 * (milliseconds) staggers neighbours. Without IntersectionObserver, or when the visitor prefers
 * reduced motion, everything is shown at once.
 */
const revealAll = (elements) => elements.forEach((element) => element.classList.add('is-revealed'));

const initReveal = () => {
    const elements = [...document.querySelectorAll('[data-reveal]')];
    if (!elements.length) {
        return;
    }

    elements.forEach((element) => {
        const delay = Number.parseInt(element.dataset.revealDelay ?? '', 10);
        if (Number.isFinite(delay) && delay > 0) {
            element.style.setProperty('--reveal-delay', `${delay}ms`);
        }
    });

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealAll(elements);
        return;
    }

    // Plays in once the element is a little inside the viewport…
    const showObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
    );

    // …and resets only when it has left the viewport completely, so the reset is never seen and
    // the animation plays again the next time the visitor scrolls back to it.
    const resetObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    entry.target.classList.remove('is-revealed');
                }
            });
        },
        { threshold: 0 },
    );

    elements.forEach((element) => {
        showObserver.observe(element);
        resetObserver.observe(element);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReveal);
} else {
    initReveal();
}
