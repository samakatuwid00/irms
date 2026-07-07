// Print Resources Main Entry Point
import { initLevel3TabSwitching } from './resource-table-modules/level3-tabs.js';
import { initLevel3DistrictSchoolCascade } from './resource-table-modules/level3-cascade.js';
import { initLevel4Cascade } from './resource-table-modules/level4-cascade.js';
import { initResetFilters } from './resource-table-modules/reset-filters.js';

// Get level from the page
const levelElement = document.querySelector('[data-user-level]');
const level = levelElement ? parseInt(levelElement.dataset.userLevel) : 0;

function setDataLoading(container, loading) {
    const skeleton = window.ResourceLoadingSkeleton;
    if (!skeleton) return;

    if (loading) {
        skeleton.show(container);
    } else {
        skeleton.hide(container);
    }
}

// ─────────────────────────────────────────────
// AJAX Table Loader
// ─────────────────────────────────────────────

async function loadTableAjax(url, containerId = 'table-results-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    setDataLoading(container, true);
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
        setDataLoading(container, false);
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

        const href = link.getAttribute('href');

        // Don't intercept links that navigate to a different page (e.g. Edit button).
        // Only AJAX-load links that stay on the same path (pagination, filters).
        if (href) {
            try {
                const linkPath = new URL(href, window.location.origin).pathname;
                if (linkPath !== window.location.pathname) {
                    // Different page — let the browser navigate normally
                    window.location.href = href;
                    return;
                }
            } catch (_) {
                // Malformed URL — fall through to normal navigation
                window.location.href = href;
                return;
            }
        }

        e.preventDefault();

        let containerId = 'table-results-container';
        if (link.closest('#division-results-container')) containerId = 'division-results-container';
        if (link.closest('#school-results-container'))   containerId = 'school-results-container';

        // Pagination links are absolute URLs — safe to use directly
        loadTableAjax(href, containerId);
    });
}

// Handle browser back/forward navigation.
// Only reload via AJAX if we're still on the same page — if the state URL
// belongs to a different page (e.g. the user navigated to edit-resource and
// pressed Back), let the browser handle it as a normal navigation.
window.addEventListener('popstate', (e) => {
    if (e.state && e.state.url) {
        const statePath = new URL(e.state.url, window.location.origin).pathname;
        if (statePath !== window.location.pathname) {
            // Different page — let the browser do a full load
            window.location.href = e.state.url;
            return;
        }
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
