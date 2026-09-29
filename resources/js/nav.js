/**
 * Vaste navigatie: krijgt een achtergrond na 60 px scrollen en een
 * mobiel menu via de toggle-knop.
 */
export function initNav() {
    const nav = document.querySelector('[data-nav]');

    if (!nav) {
        return;
    }

    const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 60);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const toggle = nav.querySelector('[data-nav-toggle]');

    if (toggle) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        nav.querySelectorAll('[data-nav-menu] a').forEach((link) => {
            link.addEventListener('click', () => {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }
}
