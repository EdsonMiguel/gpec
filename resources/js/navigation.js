const toggle = document.querySelector('[data-nav-toggle]');
const drawer = document.querySelector('[data-nav-drawer]');

if (toggle && drawer) {
    const desktop = window.matchMedia('(min-width: 64rem)');

    toggle.addEventListener('click', () => {
        drawer.showModal();
        toggle.setAttribute('aria-expanded', 'true');
    });

    drawer.addEventListener('close', () => {
        toggle.setAttribute('aria-expanded', 'false');
        if (!desktop.matches) {
            toggle.focus({ preventScroll: true });
        }
    });

    drawer.addEventListener('click', (event) => {
        if (event.target === drawer) {
            drawer.close();
        }
    });

    desktop.addEventListener('change', ({ matches }) => {
        if (matches && drawer.open) {
            drawer.close();
        }
    });
}
