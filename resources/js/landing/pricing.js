document.addEventListener('DOMContentLoaded', () => {
    const pricingContainer = document.getElementById('pricing-grid');
    if (!pricingContainer) {
        return;
    }

    const cards = pricingContainer.querySelectorAll('.pricing-card');
    if (!cards.length) {
        return;
    }

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
});
