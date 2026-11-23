/**
 * Scroll animations with AOS (BQ-175; dependency approved by the Tech Lead on 2026-11-23).
 * Only pages that mark elements with `data-aos` start it. `once: false` replays an animation each time
 * the visitor scrolls back down to an element; visitors who prefer reduced motion get none (AOS then
 * removes its attributes, so nothing stays hidden).
 */
import AOS from 'aos';
import 'aos/dist/aos.css';

if (document.querySelector('[data-aos]')) {
    AOS.init({
        duration: 900,
        easing: 'ease-out-quart',
        offset: 60,
        once: false,
        mirror: false,
        anchorPlacement: 'top-bottom',
        disable: () => window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    });

    // Fonts and images change heights after load; recalculate the trigger points once they settle.
    window.addEventListener('load', () => AOS.refresh());
}
