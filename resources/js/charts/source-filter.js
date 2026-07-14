const SCHOOL_ONLY_TOGGLE_SELECTOR = '[data-school-only-toggle]';
const divisionHubToggleCallbacks = new Set();

function getDivisionHubToggles() {
    return [...document.querySelectorAll(SCHOOL_ONLY_TOGGLE_SELECTOR)];
}

export function isDivisionHubHidden() {
    return Boolean(getDivisionHubToggles()[0]?.checked);
}

export function applySchoolOnlyParam(url) {
    if (isDivisionHubHidden()) {
        url.searchParams.set('school_only', '1');
    }

    return url;
}

export function bindDivisionHubToggle(callback) {
    divisionHubToggleCallbacks.add(callback);

    const toggles = getDivisionHubToggles();
    if (!toggles.length) return;

    toggles.forEach(toggle => {
        if (toggle.dataset.schoolOnlyBound === '1') return;
        toggle.dataset.schoolOnlyBound = '1';

        toggle.addEventListener('change', () => {
            const checked = toggle.checked;
            getDivisionHubToggles().forEach(otherToggle => {
                otherToggle.checked = checked;
                const icon = otherToggle.parentElement.querySelector('span');
                if (icon) {
                    icon.classList.remove('hub-toggle-animate');
                    void icon.offsetWidth;
                    icon.classList.add('hub-toggle-animate');
                }
            });
            divisionHubToggleCallbacks.forEach(fn => fn(checked));
        });
    });
}
