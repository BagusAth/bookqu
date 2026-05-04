document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('.landing-navbar');
    if (!navbar) {
        return;
    }

    const navLinks = Array.from(navbar.querySelectorAll('[data-scroll-target]'));
    const sections = navLinks
        .map((link) => {
            const targetId = link.getAttribute('data-scroll-target');
            return {
                link,
                targetId,
                element: targetId ? document.getElementById(targetId) : null,
            };
        })
        .filter((item) => item.element);

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

    const setActiveLink = (targetId) => {
        navLinks.forEach((link) => {
            link.classList.toggle('is-active', link.getAttribute('data-scroll-target') === targetId);
        });
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const targetId = link.getAttribute('data-scroll-target');
            if (!targetId) {
                return;
            }

            event.preventDefault();
            setActiveLink(targetId);
            scrollToTarget(targetId);
        });
    });

    const toggleNavbar = () => {
        navbar.classList.toggle('is-scrolled', window.scrollY > 8);
    };

    const getActiveSectionId = () => {
        if (!sections.length) {
            return null;
        }

        const offset = navbar.offsetHeight + 24;
        let activeId = sections[0].targetId;

        sections.forEach((section) => {
            if (!section.element) {
                return;
            }

            const top = section.element.getBoundingClientRect().top - offset;
            if (top <= 0) {
                activeId = section.targetId;
            }
        });

        return activeId;
    };

    let ticking = false;
    const handleScroll = () => {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(() => {
            toggleNavbar();
            const activeId = getActiveSectionId();
            if (activeId) {
                setActiveLink(activeId);
            }
            ticking = false;
        });
    };

    toggleNavbar();
    const initialActiveId = getActiveSectionId();
    if (initialActiveId) {
        setActiveLink(initialActiveId);
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleScroll);
});
