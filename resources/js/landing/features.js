document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('features-container');
    if (!container) {
        return;
    }

    const cards = container.querySelectorAll('.feature-card');
    if (!cards.length) {
        return;
    }

    let activeIndex = 0;
    let autoPlayInterval;
    let resumeTimeout;
    const AUTO_PLAY_DELAY = 6000;
    const RESUME_DELAY = 1000;

    const activateCard = (indexToActivate) => {
        cards.forEach((card, index) => {
            const progressBar = card.querySelector('.progress-bar');

            if (index === indexToActivate) {
                card.classList.add('is-expanded');
                if (progressBar) {
                    progressBar.classList.remove('animating');
                    void progressBar.offsetWidth;
                    progressBar.classList.add('animating');
                }
            } else {
                card.classList.remove('is-expanded');
                if (progressBar) {
                    progressBar.classList.remove('animating');
                }
            }
        });
        activeIndex = indexToActivate;
    };

    const startAutoPlay = () => {
        clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(() => {
            const nextIndex = (activeIndex + 1) % cards.length;
            activateCard(nextIndex);
        }, AUTO_PLAY_DELAY);
    };

    const stopAutoPlay = () => {
        clearInterval(autoPlayInterval);
        clearTimeout(resumeTimeout);
        cards.forEach((card) => {
            const progressBar = card.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.display = 'none';
            }
        });
    };

    cards.forEach((card) => {
        const progressBar = card.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.display = 'block';
        }
    });

    activateCard(0);
    startAutoPlay();

    cards.forEach((card, index) => {
        card.addEventListener('mouseenter', () => {
            stopAutoPlay();
            if (!card.classList.contains('is-expanded')) {
                activateCard(index);
            }
        });

        card.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                stopAutoPlay();
                if (!card.classList.contains('is-expanded')) {
                    activateCard(index);
                }
            }
        });
    });

    container.addEventListener('mouseleave', () => {
        clearTimeout(resumeTimeout);

        resumeTimeout = setTimeout(() => {
            cards.forEach((card) => {
                const progressBar = card.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.display = 'block';
                }
            });

            activateCard(activeIndex);
            startAutoPlay();
        }, RESUME_DELAY);
    });
});
