document.addEventListener('DOMContentLoaded', () => {
    const pricingContainer = document.getElementById('pricing-grid');
    if (!pricingContainer) {
        return;
    }

    const cards = pricingContainer.querySelectorAll('.pricing-card');
    if (!cards.length) {
        return;
    }

    const isTouchDevice = !window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const DEFAULT_ACTIVE_INDEX = Math.min(1, cards.length - 1);

    const setActiveCard = (activeIndex) => {
        cards.forEach((card, index) => {
            if (index === activeIndex) {
                card.classList.add('is-active');
            } else {
                card.classList.remove('is-active');
            }
        });
    };

    setActiveCard(DEFAULT_ACTIVE_INDEX);

    // Register mouse hover events only on devices with fine pointer (mouse/trackpad)
    if (!isTouchDevice) {
        cards.forEach((card, index) => {
            card.addEventListener('mouseenter', () => {
                pricingContainer.classList.add('has-hover');
                setActiveCard(index);
            });
        });

        pricingContainer.addEventListener('mouseleave', () => {
            pricingContainer.classList.remove('has-hover');
            setActiveCard(DEFAULT_ACTIVE_INDEX);
        });
    }

    // Sync active card state when mobile segmented tab changes
    window.addEventListener('set-active-pricing', (event) => {
        const targetIndex = typeof event.detail === 'number'
            ? event.detail
            : parseInt(event.detail, 10);

        if (!isNaN(targetIndex) && targetIndex >= 0 && targetIndex < cards.length) {
            setActiveCard(targetIndex);
        }
    });
});
