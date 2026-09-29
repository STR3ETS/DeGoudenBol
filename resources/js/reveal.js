/**
 * Laat elementen met de klasse `reveal` verschijnen zodra ze in beeld komen.
 * Zonder JavaScript (geen `html.js`) is alles gewoon zichtbaar; met
 * `prefers-reduced-motion` verschijnt alles direct.
 */
export function initReveal(root = document) {
    const items = root.querySelectorAll('.reveal:not(.is-visible)');

    if (!items.length) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
    );

    items.forEach((el) => {
        const delay = el.dataset.revealDelay;

        if (delay) {
            el.style.transitionDelay = `${delay}ms`;
        }

        // Wat al in beeld staat (bijvoorbeeld na een ankerlink) hoeft niet op de observer te wachten.
        if (el.getBoundingClientRect().top < window.innerHeight) {
            el.classList.add('is-visible');

            return;
        }

        observer.observe(el);
    });
}
