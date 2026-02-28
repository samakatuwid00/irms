// Print Resources Main Entry Point
import { initLevel3TabSwitching } from './resource-table-modules/level3-tabs.js';
import { initLevel3DistrictSchoolCascade } from './resource-table-modules/level3-cascade.js';
import { initLevel4Cascade } from './resource-table-modules/level4-cascade.js';
import { initResetFilters } from './resource-table-modules/reset-filters.js';

// Get level from the page
const levelElement = document.querySelector('[data-user-level]');
const level = levelElement ? parseInt(levelElement.dataset.userLevel) : 0;

// ─────────────────────────────────────────────
// AJAX Table Loader
// ─────────────────────────────────────────────

async function loadTableAjax(url, containerId = 'table-results-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.style.opacity = '0.5';
    container.style.pointerEvents = 'none';

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        });

        if (!response.ok) throw new Error(`Server error: ${response.status}`);

        const html = await response.text();

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContainer = doc.getElementById(containerId);

        if (newContainer) {
            container.innerHTML = newContainer.innerHTML;
        } else {
            console.error('AJAX partial not found in response. Full page was returned instead.');
            container.innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-6 text-center mt-4">
                    Failed to load data. Please try again or refresh the page.
                </div>`;
        }

        // Update browser URL bar without a full page reload
        history.pushState({ url, containerId }, '', url);

        // Re-initialize Alpine.js on the newly injected content
        if (window.Alpine) {
            window.Alpine.initTree(container);
        }

        // Re-attach cascade/filter listeners since the DOM was replaced
        reinitModules();

    } catch (err) {
        console.error('Failed to load table data:', err);
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-6 text-center mt-4">
                Failed to load data. Please try again or refresh the page.
            </div>`;
    } finally {
        container.style.opacity = '';
        container.style.pointerEvents = '';
    }
}

// ─────────────────────────────────────────────
// Form Interception
// ─────────────────────────────────────────────

function interceptForms() {
    const wrapper = document.getElementById('nonprint-resources-wrapper');
    if (!wrapper) return;

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#nonprint-resources-wrapper form[data-ajax]');
        if (!form) return;

        e.preventDefault();

        const params = new URLSearchParams(new FormData(form));

        // ✅ Always use window.location.pathname — never form.action or window.location.href
        // This prevents the previous query string from being appended to the new one
        const url = `${window.location.pathname}?${params.toString()}`;

        const tabInput = form.querySelector('input[name="tab"]');
        const tab = tabInput ? tabInput.value : null;

        let containerId = 'table-results-container';
        if (tab === 'division') containerId = 'division-results-container';
        if (tab === 'school')   containerId = 'school-results-container';

        loadTableAjax(url, containerId);
    }, true);

    // Intercept pagination links inside any results container
    document.addEventListener('click', (e) => {
        const link = e.target.closest(
            '#table-results-container a[href],' +
            '#division-results-container a[href],' +
            '#school-results-container a[href]'
        );
        if (!link) return;

        // Don't intercept export links
        if (link.closest('.export-btn-wrapper')) return;

        e.preventDefault();

        let containerId = 'table-results-container';
        if (link.closest('#division-results-container')) containerId = 'division-results-container';
        if (link.closest('#school-results-container'))   containerId = 'school-results-container';

        // Pagination links are absolute URLs — safe to use directly
        loadTableAjax(link.getAttribute('href'), containerId);
    });
}

// Handle browser back/forward navigation
window.addEventListener('popstate', (e) => {
    if (e.state && e.state.url) {
        loadTableAjax(e.state.url, e.state.containerId || 'table-results-container');
    }
});

// ─────────────────────────────────────────────
// Module Initialisation
// ─────────────────────────────────────────────

function reinitModules() {
    if (level === 3) {
        initLevel3TabSwitching();
        initLevel3DistrictSchoolCascade();
    }
    if (level === 4) {
        initLevel4Cascade();
    }
    initResetFilters();
}

document.addEventListener('DOMContentLoaded', () => {
    reinitModules();
    interceptForms();

    history.replaceState({ url: window.location.href }, '', window.location.href);
});
