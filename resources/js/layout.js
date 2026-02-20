function initMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuOpenIcon = document.getElementById('menu-open-icon');
    const menuCloseIcon = document.getElementById('menu-close-icon');

    if (!mobileMenuToggle || !mobileMenu) return;

    mobileMenuToggle.addEventListener('click', () => {
        const isOpen = mobileMenu.classList.contains('open');

        if (isOpen) {
            mobileMenu.classList.remove('open');
            menuOpenIcon.classList.remove('hidden');
            menuCloseIcon.classList.add('hidden');
        } else {
            mobileMenu.classList.add('open');
            menuOpenIcon.classList.add('hidden');
            menuCloseIcon.classList.remove('hidden');
        }
    });

    document.addEventListener('click', (e) => {
        if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
            if (mobileMenu.classList.contains('open')) {
                mobileMenu.classList.remove('open');
                menuOpenIcon.classList.remove('hidden');
                menuCloseIcon.classList.add('hidden');
            }
        }
    });

    const mobileLinks = mobileMenu.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
            menuOpenIcon.classList.remove('hidden');
            menuCloseIcon.classList.add('hidden');
        });
    });
}

/**
 * Initialize mobile resources submenu toggle
 */
function initMobileResourcesSubmenu() {
    const mobileResourcesToggle = document.getElementById('mobile-resources-toggle');
    const mobileResourcesSubmenu = document.getElementById('mobile-resources-submenu');
    const mobileResourcesChevron = document.getElementById('mobile-resources-chevron');

    if (!mobileResourcesToggle || !mobileResourcesSubmenu) return;

    mobileResourcesToggle.addEventListener('click', (e) => {
        e.preventDefault();
        mobileResourcesSubmenu.classList.toggle('hidden');
        mobileResourcesChevron.classList.toggle('rotate-180');
    });
}

/**
 * Initialize mobile add resource submenu toggle
 */
function initMobileAddResourceSubmenu() {
    const toggle = document.getElementById('mobile-add-resource-toggle');
    const submenu = document.getElementById('mobile-add-resource-submenu');
    const chevron = document.getElementById('mobile-add-resource-chevron');

    if (!toggle || !submenu) return;

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        submenu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    });
}

/**
 * Initialize desktop FAB (Floating Action Button) menu
 */
function initFabMenu() {
    const fabButton = document.getElementById('fabButton');
    const menuItems = document.getElementById('menuItems');
    const fabIcon = document.getElementById('fabIcon');

    if (!fabButton || !menuItems || !fabIcon) return;

    fabButton.addEventListener('click', () => {
        const isOpen = menuItems.classList.contains('opacity-100');

        if (isOpen) {
            menuItems.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
            menuItems.classList.add('opacity-0', 'scale-0', 'pointer-events-none');
            fabIcon.classList.remove('fa-times');
            fabIcon.classList.add('fa-bars');
        } else {
            menuItems.classList.remove('opacity-0', 'scale-0', 'pointer-events-none');
            menuItems.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
            fabIcon.classList.remove('fa-bars');
            fabIcon.classList.add('fa-times');
        }
    });

    document.addEventListener('click', (e) => {
        if (!fabButton.contains(e.target) && !menuItems.contains(e.target)) {
            if (menuItems.classList.contains('opacity-100')) {
                menuItems.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
                menuItems.classList.add('opacity-0', 'scale-0', 'pointer-events-none');
                fabIcon.classList.remove('fa-times');
                fabIcon.classList.add('fa-bars');
            }
        }
    });

    initFabTooltips(menuItems);
}

/**
 * Initialize tooltip hover effects for FAB menu items
 * @param {HTMLElement} menuItems - The menu items container
 */
function initFabTooltips(menuItems) {
    const fabMenuLinks = menuItems.querySelectorAll('a, button');

    fabMenuLinks.forEach(link => {
        const tooltip = link.querySelector('span');
        if (!tooltip) return;

        link.addEventListener('mouseenter', () => {
            tooltip.classList.remove('opacity-0');
            tooltip.classList.add('opacity-100');
        });

        link.addEventListener('mouseleave', () => {
            tooltip.classList.remove('opacity-100');
            tooltip.classList.add('opacity-0');
        });
    });
}

/**
 * Initialize desktop account dropdown menu
 */
function initAccountDropdown() {
    const toggle = document.getElementById('accountToggle');
    const menu = document.getElementById('accountMenu');
    const chevron = document.getElementById('accountChevron');

    if (!toggle || !menu) return;

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = !menu.classList.contains('hidden');

        menu.classList.add('hidden', 'opacity-0');
        chevron.classList.remove('rotate-180');

        if (!isOpen) {
            menu.classList.remove('hidden');
            requestAnimationFrame(() => {
                menu.classList.remove('opacity-0');
            });
            chevron.classList.add('rotate-180');
        }
    });

    menu.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    document.addEventListener('click', () => {
        menu.classList.add('hidden', 'opacity-0');
        chevron.classList.remove('rotate-180');
    });
}

/**
 * Initialize Preline components (if available)
 */
function initPreline() {
    if (window.preline) {
        preline.autoInit();
    }
}

/**
 * Keep resources accordion open on relevant pages
 */
function initResourcesAccordion() {
    const resourceAccordion = document.getElementById('resource-accordion-collapse');
    if (!resourceAccordion) return;

    const isResourcesPage =
        window.location.pathname.includes('print-resources') ||
        window.location.pathname.includes('nonprint-resources');

    if (isResourcesPage) {
        resourceAccordion.classList.remove('hidden');
    }
}

/**
 * Keep add resource accordion open on relevant pages
 */
function initAddResourceAccordion() {
    const el = document.getElementById('add-resource-accordion-collapse');
    if (!el) return;

    const isAddResourcePage =
        window.location.pathname.includes('add-print-resource') ||
        window.location.pathname.includes('add-nonprint-resource');

    if (isAddResourcePage) {
        el.classList.remove('hidden');
    }
}

/**
 * Initialize all layout components
 */
export function initLayout() {
    initMobileMenu();
    initMobileResourcesSubmenu();
    initMobileAddResourceSubmenu();
    initFabMenu();
    initAccountDropdown();
    initPreline();
    initResourcesAccordion();
    initAddResourceAccordion();
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initLayout);
