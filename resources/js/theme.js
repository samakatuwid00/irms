const STORAGE_KEY = 'lrmis.theme';
const DARK_CLASS = 'dark';
const THEME_CHANGE_EVENT = 'lrmis:themechange';
const media = window.matchMedia?.('(prefers-color-scheme: dark)');
const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)');
let fallbackTimer = null;

function storedTheme() {
    try {
        const value = localStorage.getItem(STORAGE_KEY);
        return value === 'dark' || value === 'light' ? value : null;
    } catch (error) {
        return null;
    }
}

function systemTheme() {
    return media?.matches ? 'dark' : 'light';
}

function effectiveTheme() {
    return storedTheme() || systemTheme();
}

function applyTheme(theme, emit = true) {
    const isDark = theme === 'dark';
    document.documentElement.classList.toggle(DARK_CLASS, isDark);
    document.documentElement.dataset.theme = theme;
    syncToggles(theme);

    if (emit) {
        window.dispatchEvent(new CustomEvent(THEME_CHANGE_EVENT, {
            detail: { theme, isDark },
        }));
    }
}

function persistTheme(theme) {
    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch (error) {
        // Storage can be unavailable in private or restricted contexts.
    }
}

function toggleOrigin(event) {
    const target = event?.currentTarget;
    const rect = target?.getBoundingClientRect?.();
    const hasPointerPosition = Number.isFinite(event?.clientX)
        && Number.isFinite(event?.clientY)
        && (event.clientX !== 0 || event.clientY !== 0);

    return {
        x: hasPointerPosition ? event.clientX : (rect ? rect.left + rect.width / 2 : window.innerWidth / 2),
        y: hasPointerPosition ? event.clientY : (rect ? rect.top + rect.height / 2 : window.innerHeight / 2),
    };
}

function rippleRadius(x, y) {
    const farthestX = Math.max(x, window.innerWidth - x);
    const farthestY = Math.max(y, window.innerHeight - y);
    return Math.hypot(farthestX, farthestY);
}

function fallbackRipple(theme, origin) {
    if (!document.body || reducedMotion?.matches) return;

    const point = origin || { x: window.innerWidth / 2, y: window.innerHeight / 2 };
    const diameter = rippleRadius(point.x, point.y) * 2;
    const ripple = document.createElement('span');
    ripple.className = 'theme-ripple-fallback';
    ripple.setAttribute('aria-hidden', 'true');
    ripple.style.left = `${point.x}px`;
    ripple.style.top = `${point.y}px`;
    ripple.style.width = `${diameter}px`;
    ripple.style.height = `${diameter}px`;
    ripple.style.setProperty('--theme-ripple-color', theme === 'dark' ? '#0f172a' : '#f8fafc');
    ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
    document.body.appendChild(ripple);
}

function applyFallback(update, theme, origin) {
    const root = document.documentElement;

    window.clearTimeout(fallbackTimer);
    root.classList.add('theme-fallback-transition');
    void root.offsetWidth;
    fallbackRipple(theme, origin);
    update();

    fallbackTimer = window.setTimeout(() => {
        root.classList.remove('theme-fallback-transition');
    }, 280);
}

function transitionTheme(theme, { persistChoice = true, origin = null } = {}) {
    const next = theme === 'dark' ? 'dark' : 'light';
    const update = () => {
        if (persistChoice) persistTheme(next);
        applyTheme(next);
    };

    if (reducedMotion?.matches || typeof document.startViewTransition !== 'function') {
        if (reducedMotion?.matches) update();
        else applyFallback(update, next, origin);
        return next;
    }

    const root = document.documentElement;
    const point = origin || { x: window.innerWidth / 2, y: window.innerHeight / 2 };
    root.style.setProperty('--theme-ripple-x', `${point.x}px`);
    root.style.setProperty('--theme-ripple-y', `${point.y}px`);
    root.style.setProperty('--theme-ripple-radius', `${rippleRadius(point.x, point.y)}px`);
    root.classList.add('theme-ripple-active');

    try {
        const transition = document.startViewTransition(update);
        transition.finished.finally(() => {
            root.classList.remove('theme-ripple-active');
        });
    } catch (error) {
        root.classList.remove('theme-ripple-active');
        applyFallback(update, next, origin);
    }

    return next;
}

function syncToggles(theme = effectiveTheme()) {
    const isDark = theme === 'dark';

    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-pressed', String(isDark));
        toggle.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');

        toggle.querySelectorAll('[data-theme-toggle-label]').forEach((label) => {
            label.textContent = isDark ? 'Light mode' : 'Dark mode';
        });
    });
}

function setTheme(theme, origin = null) {
    return transitionTheme(theme, { origin });
}

function toggleTheme(event) {
    return setTheme(
        effectiveTheme() === 'dark' ? 'light' : 'dark',
        toggleOrigin(event),
    );
}

function bindToggles(root = document) {
    root.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        if (toggle.dataset.themeToggleBound === 'true') return;
        toggle.dataset.themeToggleBound = 'true';
        toggle.addEventListener('click', toggleTheme);
    });
    syncToggles();
}

window.LRMISTheme = {
    getTheme: effectiveTheme,
    setTheme,
    toggle: toggleTheme,
    isDark: () => effectiveTheme() === 'dark',
};

applyTheme(effectiveTheme(), false);

document.addEventListener('DOMContentLoaded', () => bindToggles());
document.addEventListener('htmx:afterSettle', (event) => bindToggles(event.target || document));

media?.addEventListener?.('change', () => {
    if (!storedTheme()) transitionTheme(systemTheme(), { persistChoice: false });
});
