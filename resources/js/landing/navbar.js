document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('.landing-navbar');
    if (!navbar) {
        return;
    }

    const navLinks = navbar.querySelectorAll('[data-scroll-target]');

    const scrollToTarget = (targetId) => {
        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }

        const offset = navbar.offsetHeight + 8;
        const targetTop = target.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({
            top: targetTop,
            behavior: 'smooth',
        });
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const targetId = link.getAttribute('data-scroll-target');
            if (!targetId) {
                return;
            }

            event.preventDefault();
            scrollToTarget(targetId);
        });
    });

    const toggleNavbar = () => {
        navbar.classList.toggle('is-scrolled', window.scrollY > 8);
    };

    toggleNavbar();
    window.addEventListener('scroll', toggleNavbar, { passive: true });
});
