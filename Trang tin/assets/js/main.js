document.addEventListener('DOMContentLoaded', () => {
    const fallbackImage = './assets/img/product-placeholder.svg';

    document.querySelectorAll('.product-card__image').forEach((image) => {
        image.addEventListener('error', () => {
            if (!image.src.endsWith('product-placeholder.svg')) {
                image.src = fallbackImage;
            }
        });
    });

    const stickyHeader = document.querySelector('.site-header-sticky');
    const menuToggle = document.querySelector('#category-menu-toggle');
    const megaMenu = document.querySelector('#category-mega-menu');
    const megaBackdrop = document.querySelector('#mega-menu-backdrop');
    const categoryLinks = document.querySelectorAll('[data-mega-target]');
    const menuPanels = document.querySelectorAll('.mega-menu__panel');

    if (!stickyHeader || !menuToggle || !megaMenu || !megaBackdrop) {
        return;
    }

    const updateMenuPosition = () => {
        const headerBottom = stickyHeader.getBoundingClientRect().bottom;
        document.documentElement.style.setProperty('--mega-menu-top', `${Math.max(0, headerBottom)}px`);
    };

    const showPanel = (targetId) => {
        categoryLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.megaTarget === targetId);
        });

        menuPanels.forEach((panel) => {
            const isActive = panel.id === targetId;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);
        });
    };

    const openMenu = () => {
        updateMenuPosition();
        megaMenu.hidden = false;
        megaBackdrop.hidden = false;
        menuToggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mega-menu-open');
    };

    const closeMenu = () => {
        megaMenu.hidden = true;
        megaBackdrop.hidden = true;
        menuToggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mega-menu-open');
    };

    menuToggle.addEventListener('click', () => {
        if (menuToggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
        } else {
            openMenu();
        }
    });

    categoryLinks.forEach((link) => {
        const activatePanel = () => showPanel(link.dataset.megaTarget);
        link.addEventListener('mouseenter', activatePanel);
        link.addEventListener('focus', activatePanel);
    });

    megaBackdrop.addEventListener('click', closeMenu);
    window.addEventListener('resize', updateMenuPosition);
    window.addEventListener('scroll', updateMenuPosition, { passive: true });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
            menuToggle.focus();
        }
    });
});
