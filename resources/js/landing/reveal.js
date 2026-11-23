/**
 * Scroll reveal (BQ-175): adds `is-revealed` to every `[data-reveal]` element the first time it
 * enters the viewport; `resources/css/landing/reveal.css` animates the change. `data-reveal-delay`
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

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
    );

    elements.forEach((element) => observer.observe(element));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReveal);
} else {
    initReveal();
}
